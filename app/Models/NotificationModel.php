<?php

namespace App\Models;

use CodeIgniter\Model;

class NotificationModel extends Model
{
    protected $table = 'notifications';
    protected $primaryKey = 'id';

    protected $returnType = 'array';
    protected $useAutoIncrement = true;
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $useTimestamps = false;

    protected $allowedFields = [
        'user_id',
        'actor_id',
        'type',
        'title',
        'message',
        'target_path',
        'read_at',
        'created_at',
    ];
}