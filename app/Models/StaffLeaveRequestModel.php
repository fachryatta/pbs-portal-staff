<?php

namespace App\Models;

use CodeIgniter\Model;

class StaffLeaveRequestModel extends Model
{
    protected $table = 'staff_leave_requests';
    protected $primaryKey = 'id';

    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;

    protected $allowedFields = [
        'user_id',
        'request_type',
        'leave_category',
        'start_date',
        'end_date',
        'reason',
        'status',
        'admin_note',
        'rejection_reason',
        'reviewed_by',
        'reviewed_at',
    ];

    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    /**
     * Kategori cuti yang tersedia.
     */
    public const LEAVE_CATEGORIES = [
        'hamil' => 'Cuti Hamil',
        'menikah' => 'Cuti Menikah',
        'menikahkan_anak' => 'Cuti Menikahkan Anak',
        'duka' => 'Cuti Duka',
    ];

    /**
     * Status pengajuan.
     */
    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    /**
     * Memeriksa jenis pengajuan dan kategori cuti.
     */
    public function isValidRequestType(
        string $type,
        ?string $category = null
    ): bool {
        if ($type === 'izin') {
            return $category === null || $category === '';
        }

        if ($type !== 'cuti') {
            return false;
        }

        return $category !== null
            && array_key_exists(
                $category,
                self::LEAVE_CATEGORIES
            );
    }

    /**
     * Mengambil pengajuan milik seorang staf.
     */
    public function getByStaff(int $userId)
    {
        return $this
            ->where('user_id', $userId)
            ->orderBy('created_at', 'DESC')
            ->findAll();
    }

    /**
     * Mengambil pengajuan berdasarkan status.
     */
    public function getByStatus(?string $status = null)
    {
        $builder = $this->orderBy('created_at', 'DESC');

        if ($status !== null && $status !== '') {
            $builder->where('status', $status);
        }

        return $builder->findAll();
    }
}
