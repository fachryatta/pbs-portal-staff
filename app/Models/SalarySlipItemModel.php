<?php

namespace App\Models;

use CodeIgniter\Model;

class SalarySlipItemModel extends Model
{
    protected $table = 'salary_slip_items';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;

    protected $allowedFields = [
        'salary_slip_id',
        'item_type',
        'label',
        'amount',
        'sort_order',
    ];

    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
}
