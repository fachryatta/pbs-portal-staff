<?php

namespace App\Controllers;

use App\Models\UserModel;
use App\Models\DocumentModel;
use App\Models\StaffProfileModel;

class Dashboard extends BaseController
{
   
public function index()
{
    helper('url');

    if (session()->get('access_role') === 'admin') {
        $mode = session()->get('dashboard_mode');

        $destination = $mode === 'staff'
            ? 'staff/dashboard'
            : 'admin/dashboard';
    } else {
        $destination = 'staff/dashboard';
    }

    return redirect()->to(site_url($destination));
}


    public function admin()
{
    helper(['url', 'form']);
    // Simpan mode navigasi yang sedang aktif.
session()->set('dashboard_mode', 'admin');

    $ownProfile = (new StaffProfileModel())
    ->where('user_id', (int) session()->get('user_id'))
    ->first();

    // Setiap pemanggilan membuat query baru.
    $staffQuery = static function () {
        return (new UserModel())
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
    };

    $totalStaff = $staffQuery()
        ->countAllResults();

    $activeStaff = $staffQuery()
        ->where('users.is_active', 1)
        ->countAllResults();

    $unverifiedStaff = $staffQuery()
        ->where('users.email_verified_at', null)
        ->countAllResults();

    $totalDocuments = (new DocumentModel())
        ->join(
            'users',
            'users.id = documents.user_id'
        )
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
        ->groupEnd()
        ->countAllResults();

    return view('dashboard/admin', [
        'title'           => 'Dashboard Admin',
        'activePage'      => 'dashboard',
        'totalStaff'      => $totalStaff,
        'activeStaff'     => $activeStaff,
        'unverifiedStaff' => $unverifiedStaff,
        'totalDocuments'  => $totalDocuments,
        'dashboardMode'      => 'admin',
'canSwitchDashboard' => ! empty($ownProfile),
    ]);
}

    public function staff()
{
    helper(['url', 'form']);

    $userId = (int) session()->get('user_id');

$user = (new UserModel())->find($userId);

$profile = (new StaffProfileModel())
    ->where('user_id', $userId)
    ->first();

// Admin hanya dapat berpindah ke dashboard staff
// jika akun tersebut memiliki profil staff.
if (($user['access_role'] ?? '') === 'admin') {
    if (empty($profile)) {
        return redirect()->to(site_url('admin/dashboard'));
    }

    session()->set('dashboard_mode', 'staff');
}

    $documentModel = new DocumentModel();

    $groupedDocuments = $documentModel
        ->select('document_type, COUNT(*) AS total')
        ->where('user_id', $userId)
        ->groupBy('document_type')
        ->findAll();

    $counts = [
        'diploma'                 => 0,
        'competency_certificate'  => 0,
        'competition_certificate' => 0,
    ];

    foreach ($groupedDocuments as $row) {
        if (array_key_exists($row['document_type'], $counts)) {
            $counts[$row['document_type']] = (int) $row['total'];
        }
    }

    $recentDocuments = $documentModel
        ->select('id, document_type, title, issuer, created_at')
        ->where('user_id', $userId)
        ->orderBy('created_at', 'DESC')
        ->orderBy('id', 'DESC')
        ->findAll(5);

    return view('dashboard/staff', [
    'dashboardMode'      => 'staff',
    'canSwitchDashboard' => ($user['access_role'] ?? '') === 'admin'
        && ! empty($profile),
    'title'              => 'Dashboard Staff',
    'activePage'         => 'dashboard',
    'user'               => $user,
    'profile'            => $profile ?? [],
    'counts'             => $counts,
    'totalDocuments'     => array_sum($counts),
    'recentDocuments'    => $recentDocuments,
]);
}
}