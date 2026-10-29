<?php

namespace App\Models;

use CodeIgniter\Model;

class EmailVerificationTokenModel extends Model
{
    protected $table = 'email_verification_tokens';
    protected $primaryKey = 'id';

    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;

    protected $protectFields = true;

    protected $allowedFields = [
        'user_id',
        'token_hash',
        'expires_at',
    ];

    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
}