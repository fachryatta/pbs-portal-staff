<?php

namespace App\Models;

use CodeIgniter\Model;

class UserModel extends Model
{
    protected $table = 'users';
    protected $primaryKey = 'id';

    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;

    protected $protectFields = true;

    protected $allowedFields = [
    'full_name',
    'profile_photo',
    'username',
    'email',
    'password_hash',
    'access_role',
    'email_verified_at',
    'is_active',
];

    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
}