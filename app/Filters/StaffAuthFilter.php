<?php

namespace App\Filters;

use App\Models\UserModel;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class StaffAuthFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        helper('url');

        $session = session();

        if (! $session->get('logged_in') || ! $session->get('user_id')) {
            return redirect()->to(site_url('login'))
                ->with('error', 'Silakan login terlebih dahulu.');
        }

        // Baca kondisi terbaru dari database pada setiap akses.
        $user = (new UserModel())->find($session->get('user_id'));

        if (
    ! $user
    || (int) $user['is_active'] !== 1
    || $user['email_verified_at'] === null
    || ! in_array($user['access_role'], ['admin', 'user'], true)
    || (int) $session->get('auth_version')
        !== (int) $user['auth_version']
) {
    $session->destroy();

    return redirect()->to(site_url('login'));
}

        // Perbarui session jika data akun berubah.
        $session->set([
            'full_name'   => $user['full_name'],
            'email'       => $user['email'],
            'access_role' => $user['access_role'],
        ]);

        // Contoh arguments: ['admin'].
        if (
            ! empty($arguments)
            && ! in_array($user['access_role'], $arguments, true)
        ) {
            return service('response')
                ->setStatusCode(403)
                ->setContentType('text/plain')
                ->setBody('Akses ditolak. Akun Anda tidak memiliki izin untuk halaman ini.');
        }
    }

    public function after(
        RequestInterface $request,
        ResponseInterface $response,
        $arguments = null
    ) {
        $response->setHeader('Cache-Control', 'no-store, private');
    }
}