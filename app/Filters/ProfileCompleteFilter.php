<?php

namespace App\Filters;

use App\Models\StaffProfileModel;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class ProfileCompleteFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        helper('url');

        if (! session()->get('logged_in')) {
            return redirect()->to(site_url('login'));
        }


        $model = new StaffProfileModel();

        $profile = $model
            ->where('user_id', session()->get('user_id'))
            ->first();

        if (! $model->isComplete($profile)) {
            return redirect()->to(site_url('profile/edit'))
                ->with(
                    'error',
                    'Lengkapi profil terlebih dahulu untuk melanjutkan.'
                );
        }
    }

    public function after(
        RequestInterface $request,
        ResponseInterface $response,
        $arguments = null
    ) {
        // Tidak ada perubahan pada response.
    }
}