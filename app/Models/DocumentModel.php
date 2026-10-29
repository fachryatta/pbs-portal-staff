<?php

namespace App\Models;

use CodeIgniter\Model;

class DocumentModel extends Model
{
    protected $table = 'documents';
    protected $primaryKey = 'id';

    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;

    protected $protectFields = true;

    protected $allowedFields = [
        'user_id',
        'document_type',
        'title',
        'issuer',
        'issue_year',
        'original_name',
        'stored_name',
        'mime_type',
        'file_size',
    ];

    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
}