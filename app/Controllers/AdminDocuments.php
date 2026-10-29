<?php

namespace App\Controllers;

use App\Models\DocumentModel;

class AdminDocuments extends BaseController
{
    private const TYPES = [
        'diploma' => 'Ijazah',
        'competency_certificate' => 'Sertifikat Kompetensi',
        'competition_certificate' => 'Sertifikat Kompetisi',
    ];

    private function documentQuery(): DocumentModel
    {
        return (new DocumentModel())
            ->join('users', 'users.id = documents.user_id')
            ->join(
                'staff_profiles',
                'staff_profiles.user_id = users.id',
                'left'
            )
            ->groupStart()
                ->where('users.access_role', 'user')
                ->orGroupStart()
                    ->where('users.access_role', 'admin')
                    ->where(
                        'staff_profiles.user_id IS NOT NULL',
                        null,
                        false
                    )
                ->groupEnd()
            ->groupEnd();
    }

    public function index()
    {
        helper(['url', 'form']);

        // Ringkasan seluruh arsip; gunakan query baru sebelum filter daftar.
        $summaryRows = $this->documentQuery()
            ->select('documents.document_type, COUNT(documents.id) AS total')
            ->groupBy('documents.document_type')
            ->findAll();

        $documentSummary = [
            'total' => 0,
            'diploma' => 0,
            'competency_certificate' => 0,
            'competition_certificate' => 0,
        ];

        foreach ($summaryRows as $row) {
            $total = (int) $row['total'];
            $documentSummary['total'] += $total;
            $type = $row['document_type'];

            if (array_key_exists($type, self::TYPES)) {
                $documentSummary[$type] += $total;
            }
        }

        $read = function (string $name): string {
            $value = $this->request->getGet($name);

            return is_string($value) ? trim($value) : '';
        };

        $search = mb_substr($read('q'), 0, 100);
        $category = $read('category');
        $staffCategory = $read('staff_category');
        $year = $read('year');

        if (! array_key_exists($category, self::TYPES)) {
            $category = '';
        }

        if (! in_array(
            $staffCategory,
            ['', 'academic', 'academic_support'],
            true
        )) {
            $staffCategory = '';
        }

        $yearRows = $this->documentQuery()
            ->select('documents.issue_year')
            ->distinct()
            ->where('documents.issue_year IS NOT NULL', null, false)
            ->orderBy('documents.issue_year', 'DESC')
            ->findAll();

        $years = array_map(
            static fn ($row) => (string) $row['issue_year'],
            $yearRows
        );

        if (! in_array($year, $years, true)) {
            $year = '';
        }

        $model = $this->documentQuery();

        $model->select(
            'documents.id, documents.user_id,
             documents.document_type, documents.title,
             documents.issuer, documents.issue_year,
             documents.original_name, documents.file_size,
             documents.created_at,
             users.full_name AS staff_name,
             staff_profiles.staff_category'
        );

        if ($search !== '') {
            $model->groupStart()
                ->like('documents.title', $search)
                ->orLike('documents.original_name', $search)
                ->orLike('users.full_name', $search)
                ->groupEnd();
        }

        if ($category !== '') {
            $model->where('documents.document_type', $category);
        }

        if ($staffCategory !== '') {
            $model->where('staff_profiles.staff_category', $staffCategory);
        }

        if ($year !== '') {
            $model->where('documents.issue_year', (int) $year);
        }

        $documents = $model
            ->orderBy('documents.created_at', 'DESC')
            ->orderBy('documents.id', 'DESC')
            ->paginate(10);

        return view('admin/documents/index', [
            'documentSummary' => $documentSummary,
            'title'         => 'Semua Dokumen',
            'activePage'    => 'all-documents',
            'documents'     => $documents,
            'types'         => self::TYPES,
            'pager'         => $model->pager,
            'search'        => $search,
            'category'      => $category,
            'staffCategory' => $staffCategory,
            'year'          => $year,
            'years'         => $years,
        ]);
    }
}