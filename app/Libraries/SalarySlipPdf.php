<?php

namespace App\Libraries;

use App\Models\SalarySlipItemModel;
use Dompdf\Dompdf;
use Dompdf\Options;

class SalarySlipPdf
{
    public function render(array $slip): string
    {
        $items = (new SalarySlipItemModel())
            ->where('salary_slip_id', (int) $slip['id'])
            ->orderBy('sort_order', 'ASC')
            ->orderBy('id', 'ASC')
            ->findAll();

        $earnings = [];
        $deductions = [];

        $totalEarnings = (int) $slip['basic_salary'];
        $totalDeductions = 0;

        foreach ($items as $item) {
            if ($item['item_type'] === 'earning') {
                $earnings[] = $item;
                $totalEarnings += (int) $item['amount'];
            } else {
                $deductions[] = $item;
                $totalDeductions += (int) $item['amount'];
            }
        }

        $cssPath = FCPATH . 'assets/css/salary-pdf.css';

        if (!is_readable($cssPath)) {
            throw new \RuntimeException('CSS PDF tidak ditemukan.');
        }

        $css = file_get_contents($cssPath);

        if ($css === false) {
            throw new \RuntimeException('CSS PDF tidak dapat dibaca.');
        }

        $logoData = null;
        $logoPath = FCPATH . 'assets/images/logo110.gif';

        if (is_readable($logoPath)) {
            $logo = file_get_contents($logoPath);

            if ($logo !== false) {
                $logoData = 'data:image/gif;base64,' . base64_encode($logo);
            }
        }

        $html = view('salary_slips/pdf', [
            'slip' => $slip,
            'earnings' => $earnings,
            'deductions' => $deductions,
            'totalEarnings' => $totalEarnings,
            'totalDeductions' => $totalDeductions,
            'netSalary' => $totalEarnings - $totalDeductions,
            'css' => $css,
            'logoData' => $logoData,
        ]);

        $options = new Options();

        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('isRemoteEnabled', false);
        $options->set('isPhpEnabled', false);
        $options->set('isJavascriptEnabled', false);
        $options->set('chroot', FCPATH . 'assets');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return $dompdf->output();
    }

    public function directory(): string
    {
        return WRITEPATH . 'uploads/salary_slips/';
    }

    public function store(string $pdf): string
    {
        $directory = $this->directory();

        if (
            !is_dir($directory)
            && !mkdir($directory, 0700, true)
            && !is_dir($directory)
        ) {
            throw new \RuntimeException('Folder PDF tidak dapat dibuat.');
        }

        $filename = bin2hex(random_bytes(16)) . '.pdf';
        $path = $directory . $filename;

        $written = file_put_contents($path, $pdf, LOCK_EX);

        if ($written === false || $written !== strlen($pdf)) {
            if (is_file($path)) {
                unlink($path);
            }

            throw new \RuntimeException('PDF tidak dapat disimpan.');
        }

        return $filename;
    }

    public function read(string $filename): string
    {
        if (!preg_match('/\A[a-f0-9]{32}\.pdf\z/', $filename)) {
            throw new \RuntimeException('Nama file PDF tidak valid.');
        }

        $path = $this->directory() . $filename;

        if (!is_file($path) || !is_readable($path)) {
            throw new \RuntimeException('File PDF tidak tersedia.');
        }

        $pdf = file_get_contents($path);

        if ($pdf === false) {
            throw new \RuntimeException('File PDF tidak dapat dibaca.');
        }

        return $pdf;
    }
}