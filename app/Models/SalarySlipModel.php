<?php

namespace App\Models;

use CodeIgniter\Model;

class SalarySlipModel extends Model
{
    protected $table = 'salary_slips';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;

    protected $allowedFields = [
        'user_id',
        'period_month',
        'period_year',
        'slip_number',
        'staff_name',
        'staff_username',
        'staff_category',
        'basic_salary',
        'notes',
        'status',
        'pdf_filename',
        'created_by',
        'published_by',
        'published_at',
    ];

    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
}
