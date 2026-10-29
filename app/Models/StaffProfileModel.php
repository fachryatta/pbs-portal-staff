<?php

namespace App\Models;

use CodeIgniter\Model;

class StaffProfileModel extends Model
{
    protected $table = 'staff_profiles';
    protected $primaryKey = 'id';

    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;

    protected $protectFields = true;

    protected $allowedFields = [
    'user_id',
    'staff_category',
    'gender',
    'religion',
    'birth_city',
    'birth_date',
    'address',
    'phone',
];

    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    public function isComplete(?array $profile): bool
{
    if (! $profile) {
        return false;
    }

    $requiredFields = [
        'staff_category',
        'gender',
        'religion',
        'birth_city',
        'birth_date',
        'address',
        'phone',
    ];

    foreach ($requiredFields as $field) {
        if (trim((string) ($profile[$field] ?? '')) === '') {
            return false;
        }
    }

    return true;
}
}