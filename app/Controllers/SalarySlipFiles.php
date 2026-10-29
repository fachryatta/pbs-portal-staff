<?php

namespace App\Controllers;

use App\Libraries\SalarySlipPdf;
use App\Models\SalarySlipModel;
use CodeIgniter\Exceptions\PageNotFoundException;
use App\Libraries\StaffNotification;

class SalarySlipFiles extends BaseController
{
    public function publish($id)
    {
        helper('url');

        // Pemeriksaan tambahan selain filter route.
        if (session()->get('access_role') !== 'admin') {
            throw PageNotFoundException::forPageNotFound();
        }

        $slipModel = new SalarySlipModel();
        $slip = $slipModel->find($id);

        if (!$slip) {
            throw PageNotFoundException::forPageNotFound(
                'Slip gaji tidak ditemukan.'
            );
        }

        $slipId = (int) $slip['id'];
        $detailUrl = site_url('admin/salary-slips/' . $slipId);

        $db = db_connect();
        $pdfService = new SalarySlipPdf();

        $filename = null;
        $commitAttempted = false;

        try {
            if ($db->transBegin() === false) {
                throw new \RuntimeException('Gagal memulai transaksi.');
            }

            // Sesuai urutan penguncian pada edit dan delete.
            $db->query(
                'SELECT id FROM users WHERE id = ? FOR UPDATE',
                [(int) $slip['user_id']]
            );

            $lockedSlip = $db->query(
                'SELECT *
                 FROM salary_slips
                 WHERE id = ?
                 FOR UPDATE',
                [$slipId]
            )->getRowArray();

            if (!$lockedSlip) {
                throw new \DomainException(
                    'Slip tidak ditemukan atau sudah dihapus.'
                );
            }

            if (
                (int) $lockedSlip['user_id'] !== (int) $slip['user_id']
            ) {
                throw new \DomainException(
                    'Data penerima berubah. Silakan buka kembali slip.'
                );
            }

            if ($lockedSlip['status'] !== 'draft') {
                throw new \DomainException(
                    'Slip ini sudah diterbitkan.'
                );
            }

            $publishedAt = date('Y-m-d H:i:s');

            $slipNumber = sprintf(
                'PBS-%04d%02d-%06d',
                (int) $lockedSlip['period_year'],
                (int) $lockedSlip['period_month'],
                $slipId
            );

            // Data yang akan dicetak menjadi snapshot PDF final.
            $finalSlip = array_merge($lockedSlip, [
                'status' => 'published',
                'slip_number' => $slipNumber,
                'published_at' => $publishedAt,
                'published_by' => (int) session()->get('user_id'),
            ]);

            $pdf = $pdfService->render($finalSlip);
            $filename = $pdfService->store($pdf);

            $transactionModel = new SalarySlipModel($db);

            $updated = $transactionModel->update($slipId, [
                'status' => 'published',
                'slip_number' => $slipNumber,
                'pdf_filename' => $filename,
                'published_at' => $publishedAt,
                'published_by' => (int) session()->get('user_id'),
            ]);

            if ($updated === false || $db->transStatus() === false) {
    throw new \RuntimeException(
        'Data penerbitan tidak dapat disimpan.'
    );
}

$months = [
    1 => 'Januari',
    2 => 'Februari',
    3 => 'Maret',
    4 => 'April',
    5 => 'Mei',
    6 => 'Juni',
    7 => 'Juli',
    8 => 'Agustus',
    9 => 'September',
    10 => 'Oktober',
    11 => 'November',
    12 => 'Desember',
];

$period = $months[(int) $lockedSlip['period_month']]
    . ' ' . $lockedSlip['period_year'];

(new StaffNotification())->send(
    $db,
    (int) $lockedSlip['user_id'],
    (int) session()->get('user_id'),
    'salary_published',
    'Slip gaji sudah tersedia',
    'Slip gaji ' . $period
        . ' sudah diterbitkan. Silakan lihat dan unduh.',
    'staff/salary-slips'
);

if ($db->transStatus() === false) {
    throw new \RuntimeException(
        'Penyimpanan notifikasi gagal.'
    );
}

$commitAttempted = true;

if ($db->transCommit() === false) {
    throw new \RuntimeException('Commit gagal.');
}
        } catch (\DomainException $exception) {
            $db->transRollback();

            return redirect()->to(
                site_url('admin/salary-slips')
            )->with('error', $exception->getMessage());
        } catch (\Throwable $exception) {
            $db->transRollback();

            // Jika commit belum dicoba, file baru aman dibersihkan.
            // Setelah commit dicoba, jangan hapus file karena hasil
            // commit mungkin belum dapat dipastikan.
            if ($filename !== null && !$commitAttempted) {
                $path = $pdfService->directory() . $filename;

                if (is_file($path)) {
                    unlink($path);
                }
            }

            log_message(
                'error',
                'Penerbitan slip gagal. Jenis error: '
                    . get_class($exception)
            );

            return redirect()->to($detailUrl)->with(
                'error',
                'Penerbitan belum dapat dipastikan berhasil. '
                    . 'Periksa status slip sebelum mencoba lagi.'
            );
        }

        return redirect()->to($detailUrl)->with(
            'success',
            'Slip berhasil diterbitkan dan tersedia di akun staff penerima.'
        );
    }

    public function preview($id)
    {
        return $this->sendPdf($id, false);
    }

    public function download($id)
    {
        return $this->sendPdf($id, true);
    }

    private function sendPdf($id, bool $download)
    {
        $model = new SalarySlipModel();

        $isAdmin = session()->get('access_role') === 'admin';

        // Staff hanya boleh mengakses slip terbit miliknya sendiri.
        if (!$isAdmin) {
            $model
                ->where('user_id', (int) session()->get('user_id'))
                ->where('status', 'published');
        }

        $slip = $model->find($id);

        if (!$slip) {
            throw PageNotFoundException::forPageNotFound(
                'Slip gaji tidak ditemukan.'
            );
        }

        $pdfService = new SalarySlipPdf();

        try {
            if ($slip['status'] === 'published') {
                // Gunakan PDF final yang tersimpan, bukan cetak ulang.
                $pdf = $pdfService->read(
                    (string) $slip['pdf_filename']
                );
            } elseif ($isAdmin && $slip['status'] === 'draft') {
                $pdf = $pdfService->render($slip);
            } else {
                throw PageNotFoundException::forPageNotFound();
            }
        } catch (\Throwable $exception) {
            log_message(
                'error',
                'PDF slip tidak dapat dibuka. Jenis error: '
                    . get_class($exception)
            );

            return $this->response
                ->setStatusCode(503)
                ->setHeader('Cache-Control', 'private, no-store')
                ->setContentType('text/plain')
                ->setBody(
                    'PDF belum dapat dibuka. Silakan hubungi admin '
                    . 'atau coba kembali setelah masalah diperbaiki.'
                );
        }

        $filename = sprintf(
            '%sSlip-Gaji-%04d-%02d-%d.pdf',
            $slip['status'] === 'draft' ? 'DRAFT-' : '',
            (int) $slip['period_year'],
            (int) $slip['period_month'],
            (int) $slip['id']
        );

        $disposition = $download ? 'attachment' : 'inline';

        return $this->response
            ->setContentType('application/pdf')
            ->setHeader(
                'Content-Disposition',
                $disposition . '; filename="' . $filename . '"'
            )
            ->setHeader('Cache-Control', 'private, no-store, max-age=0')
            ->setHeader('Pragma', 'no-cache')
            ->setHeader('X-Content-Type-Options', 'nosniff')
            ->setBody($pdf);
    }
}