<?php

namespace App\Controllers;

use App\Models\DocumentModel;
use CodeIgniter\Exceptions\PageNotFoundException;
use RuntimeException;
use Throwable;

class Documents extends BaseController
{
    private const TYPES = [
        'diploma' => 'Ijazah',
        'competency_certificate' => 'Sertifikat Kompetensi',
        'competition_certificate' => 'Sertifikat Kompetisi',
    ];

    public function index()
{
    helper(['url', 'form']);

    $userId = (int) session()->get('user_id');

    $read = function (string $name): string {
        $value = $this->request->getGet($name);

        return is_string($value) ? trim($value) : '';
    };

    $search = mb_substr($read('q'), 0, 100);
    $category = $read('category');
    $year = $read('year');

    if (! array_key_exists($category, self::TYPES)) {
        $category = '';
    }

    // Pilihan tahun berasal dari seluruh dokumen akun ini.
    $yearRows = (new DocumentModel())
        ->select('issue_year')
        ->distinct()
        ->where('user_id', $userId)
        ->where('issue_year IS NOT NULL', null, false)
        ->orderBy('issue_year', 'DESC')
        ->findAll();

    $years = array_map(
        static fn ($row) => (string) $row['issue_year'],
        $yearRows
    );

    if (! in_array($year, $years, true)) {
        $year = '';
    }

    $model = new DocumentModel();

    $model->where('user_id', $userId);

    if ($search !== '') {
        // Kurung grup menjaga pembatas kepemilikan tetap berlaku.
        $model->groupStart()
            ->like('title', $search)
            ->orLike('original_name', $search)
            ->groupEnd();
    }

    if ($category !== '') {
        $model->where('document_type', $category);
    }

    if ($year !== '') {
        $model->where('issue_year', (int) $year);
    }

    $documents = $model
        ->orderBy('created_at', 'DESC')
        ->orderBy('id', 'DESC')
        ->paginate(10);

    return view('documents/index', [
        'title'      => 'Dokumen Saya',
        'activePage' => 'documents',
        'documents'  => $documents,
        'types'      => self::TYPES,
        'pager'      => $model->pager,
        'search'     => $search,
        'category'   => $category,
        'year'       => $year,
        'years'      => $years,
    ]);
}

    public function create()
    {
        helper(['url', 'form']);

        return view('documents/create', [
            'title'      => 'Upload Dokumen',
            'activePage' => 'documents',
            'types'      => self::TYPES,
            'values'     => session()->getFlashdata('document_values') ?? [],
        ]);
    }

    public function store()
    {
        helper('url');

        $data = [];

        foreach (['document_type', 'title', 'issuer', 'issue_year'] as $field) {
            $value = $this->request->getPost($field);
            $data[$field] = is_string($value) ? trim($value) : '';
        }

        $rules = [
            'document_type' => [
                'label' => 'Kategori dokumen',
                'rules' => 'required|in_list[diploma,competency_certificate,competition_certificate]',
            ],
            'title' => [
                'label' => 'Judul dokumen',
                'rules' => 'required|max_length[200]',
            ],
            'issuer' => [
                'label' => 'Instansi penerbit',
                'rules' => 'permit_empty|max_length[200]',
            ],
            'issue_year' => [
                'label' => 'Tahun terbit',
                'rules' => 'permit_empty|integer'
                    . '|greater_than_equal_to[1900]'
                    . '|less_than_equal_to[' . date('Y') . ']',
            ],
            'document' => [
                'label' => 'File dokumen',
                'rules' => [
                    'uploaded[document]',
                    'max_size[document,5120]',
                    'ext_in[document,pdf,jpg,jpeg,png]',
                    'mime_in[document,application/pdf,image/jpeg,image/png]',
                ],
                'errors' => [
                    'uploaded' => 'Pilih file dokumen terlebih dahulu.',
                    'max_size' => 'Ukuran dokumen maksimal 5 MB.',
                    'ext_in' => 'File harus berformat PDF, JPG, JPEG, atau PNG.',
                    'mime_in' => 'Isi file harus berupa PDF, JPG, atau PNG.',
                ],
            ],
        ];

        if (! $this->validateData($data, $rules)) {
            return redirect()->to(site_url('documents/create'))
                ->with('errors', $this->validator->getErrors())
                ->with('document_values', $data);
        }

        $file = $this->request->getFile('document');
        $directory = WRITEPATH . 'uploads/documents/';
        $savedPath = null;

        try {
            if (! $file->isValid() || $file->hasMoved()) {
                throw new RuntimeException('File upload tidak valid.');
            }

            if (
                ! is_dir($directory)
                && ! mkdir($directory, 0750, true)
                && ! is_dir($directory)
            ) {
                throw new RuntimeException('Folder dokumen gagal dibuat.');
            }

            // Tentukan ekstensi dari MIME yang diperiksa server.
            $mime = $file->getMimeType();

            $extensions = [
                'application/pdf' => 'pdf',
                'image/jpeg' => 'jpg',
                'image/png' => 'png',
            ];

            if (! isset($extensions[$mime])) {
                throw new RuntimeException('Jenis file tidak didukung.');
            }

            $storedName = bin2hex(random_bytes(16))
                . '.' . $extensions[$mime];

            $originalName = basename(
                str_replace('\\', '/', $file->getClientName())
            );

            $originalName = preg_replace(
                '/[\x00-\x1F\x7F]/',
                '',
                $originalName
            );

            $originalName = mb_substr($originalName, 0, 240);

            if ($originalName === '') {
                $originalName = 'dokumen.' . $extensions[$mime];
            }

            $fileSize = $file->getSize();

            $file->move($directory, $storedName);

            // Gunakan nama aktual setelah file dipindahkan.
            $storedName = $file->getName();
            $savedPath = $directory . $storedName;

            $documentId = (new DocumentModel())->insert([
                'user_id' => (int) session()->get('user_id'),
                'document_type' => $data['document_type'],
                'title' => $data['title'],
                'issuer' => $data['issuer'] !== '' ? $data['issuer'] : null,
                'issue_year' => $data['issue_year'] !== ''
                    ? (int) $data['issue_year']
                    : null,
                'original_name' => $originalName,
                'stored_name' => $storedName,
                'mime_type' => $mime,
                'file_size' => $fileSize,
            ]);

            if ($documentId === false) {
                throw new RuntimeException('Informasi dokumen gagal disimpan.');
            }
        } catch (Throwable $exception) {
            // Bersihkan file jika informasi dokumen gagal disimpan.
            if ($savedPath !== null && is_file($savedPath)) {
                if (! @unlink($savedPath)) {
                    log_message('error', 'File upload yang gagal belum dapat dibersihkan.');
                }
            }

            log_message(
                'error',
                'Upload dokumen gagal: ' . get_class($exception)
            );

            return redirect()->to(site_url('documents/create'))
                ->with('error', 'Dokumen belum berhasil disimpan. Silakan coba lagi.')
                ->with('document_values', $data);
        }

        return redirect()->to(site_url('documents'))
            ->with('success', 'Dokumen berhasil diunggah.');
    }

    public function download($id)
    {
        // Cari berdasarkan ID dokumen DAN pemilik yang sedang login.
        $document = (new DocumentModel())
            ->where('id', $id)
            ->where('user_id', (int) session()->get('user_id'))
            ->first();

        if (! $document) {
            throw PageNotFoundException::forPageNotFound();
        }

        $storedName = $document['stored_name'];

        if (! preg_match('/^[a-f0-9]{32}\.(pdf|jpg|png)$/', $storedName)) {
            throw PageNotFoundException::forPageNotFound();
        }

        $path = WRITEPATH . 'uploads/documents/' . $storedName;

        if (! is_file($path) || ! is_readable($path)) {
            throw PageNotFoundException::forPageNotFound();
        }

        return $this->response
            ->download($path, null)
            ->setFileName($document['original_name'])
            ->setHeader('X-Content-Type-Options', 'nosniff')
            ->setHeader('Cache-Control', 'no-store, private');
    }

    public function preview($id)
{
    helper(['url', 'form']);

    $document = (new DocumentModel())
        ->where('id', $id)
        ->where('user_id', (int) session()->get('user_id'))
        ->first();

    if (! $document) {
        throw PageNotFoundException::forPageNotFound();
    }

    return view('documents/preview', [
        'title'      => 'Pratinjau Dokumen',
        'activePage' => 'documents',
        'document'   => $document,
        'types'      => self::TYPES,
    ]);
}

public function viewFile($id)
{
    $document = (new DocumentModel())
        ->where('id', $id)
        ->where('user_id', (int) session()->get('user_id'))
        ->first();

    if (! $document) {
        throw PageNotFoundException::forPageNotFound();
    }

    $storedName = $document['stored_name'];

    if (! preg_match('/^[a-f0-9]{32}\.(pdf|jpg|png)$/', $storedName)) {
        throw PageNotFoundException::forPageNotFound();
    }

    $path = WRITEPATH . 'uploads/documents/' . $storedName;

    if (! is_file($path) || ! is_readable($path)) {
        throw PageNotFoundException::forPageNotFound();
    }

    return $this->response
        ->download($path, null, true)
        ->inline()
        ->setHeader('X-Content-Type-Options', 'nosniff')
        ->setHeader('Cache-Control', 'no-store, private');
}

private function findOwnedDocument($id): array
{
    $document = (new DocumentModel())
        ->where('id', $id)
        ->where('user_id', (int) session()->get('user_id'))
        ->first();

    if (! $document) {
        throw PageNotFoundException::forPageNotFound();
    }

    return $document;
}

public function edit($id)
{
    helper(['url', 'form']);

    $document = $this->findOwnedDocument($id);

    return view('documents/edit', [
        'title'      => 'Edit Dokumen',
        'activePage' => 'documents',
        'document'   => $document,
        'types'      => self::TYPES,
        'values'     => array_merge(
            $document,
            session()->getFlashdata('document_edit_values') ?? []
        ),
    ]);
}

public function update($id)
{
    helper('url');

    $document = $this->findOwnedDocument($id);
    $data = [];

    foreach (['document_type', 'title', 'issuer', 'issue_year'] as $field) {
        $value = $this->request->getPost($field);
        $data[$field] = is_string($value) ? trim($value) : '';
    }

    $rules = [
        'document_type' => [
            'label' => 'Kategori dokumen',
            'rules' => 'required|in_list[diploma,competency_certificate,competition_certificate]',
        ],
        'title' => [
            'label' => 'Judul dokumen',
            'rules' => 'required|max_length[200]',
        ],
        'issuer' => [
            'label' => 'Instansi penerbit',
            'rules' => 'permit_empty|max_length[200]',
        ],
        'issue_year' => [
            'label' => 'Tahun terbit',
            'rules' => 'permit_empty|integer'
                . '|greater_than_equal_to[1900]'
                . '|less_than_equal_to[' . date('Y') . ']',
        ],
    ];

    if (! $this->validateData($data, $rules)) {
        return redirect()->to(
            site_url('documents/' . $document['id'] . '/edit')
        )
            ->with('errors', $this->validator->getErrors())
            ->with('document_edit_values', $data);
    }

    try {
        $saved = (new DocumentModel())
            ->where('user_id', (int) session()->get('user_id'))
            ->update($document['id'], [
                'document_type' => $data['document_type'],
                'title' => $data['title'],
                'issuer' => $data['issuer'] !== '' ? $data['issuer'] : null,
                'issue_year' => $data['issue_year'] !== ''
                    ? (int) $data['issue_year']
                    : null,
            ]);

        if ($saved === false) {
            throw new RuntimeException('Informasi dokumen gagal diperbarui.');
        }
    } catch (Throwable $exception) {
        log_message(
            'error',
            'Edit dokumen gagal: ' . get_class($exception)
        );

        return redirect()->to(
            site_url('documents/' . $document['id'] . '/edit')
        )
            ->with('error', 'Perubahan belum berhasil disimpan.')
            ->with('document_edit_values', $data);
    }

    return redirect()->to(site_url('documents'))
        ->with('success', 'Informasi dokumen berhasil diperbarui.');
}

public function delete($id)
{
    helper('url');

    $document = $this->findOwnedDocument($id);
    $storedName = $document['stored_name'];

    if (! preg_match('/^[a-f0-9]{32}\.(pdf|jpg|png)$/', $storedName)) {
        return redirect()->to(site_url('documents'))
            ->with('error', 'Lokasi file tidak valid. Hubungi admin.');
    }

    $path = WRITEPATH . 'uploads/documents/' . $storedName;

    try {
        $deleted = (new DocumentModel())
            ->where('user_id', (int) session()->get('user_id'))
            ->delete($document['id']);

        if ($deleted === false) {
            throw new RuntimeException('Data dokumen gagal dihapus.');
        }
    } catch (Throwable $exception) {
        log_message(
            'error',
            'Penghapusan data dokumen gagal: ' . get_class($exception)
        );

        return redirect()->to(site_url('documents'))
            ->with('error', 'Dokumen belum berhasil dihapus.');
    }

    // Hapus file setelah penghapusan data database berhasil.
    if (is_file($path) && ! @unlink($path)) {
        log_message(
            'error',
            'File dokumen belum dapat dibersihkan: ' . $storedName
        );

        return redirect()->to(site_url('documents'))
            ->with(
                'error',
                'Dokumen sudah dihapus dari daftar, tetapi file di penyimpanan belum berhasil dibersihkan. Hubungi admin.'
            );
    }

    return redirect()->to(site_url('documents'))
        ->with('success', 'Dokumen berhasil dihapus.');
}
}