<?php

namespace App\Controllers;

use App\Models\UserModel;
use App\Models\StaffProfileModel;
use Config\Database;
use RuntimeException;
use Throwable;

class Profile extends BaseController
{
    public function index()
    {
        helper(['url', 'form']);

        $userId = (int) session()->get('user_id');
        $profileModel = new StaffProfileModel();

        $user = (new UserModel())->find($userId);
        $profile = $profileModel->where('user_id', $userId)->first();

        return view('profile/index', [
            'title'       => 'Profil Saya',
            'activePage'  => 'profile',
            'user'        => $user,
            'profile'     => $profile ?? [],
            'isComplete'  => $profileModel->isComplete($profile),
        ]);
    }

    public function edit()
    {
        helper(['url', 'form']);

        $userId = (int) session()->get('user_id');
        $profileModel = new StaffProfileModel();

        $user = (new UserModel())->find($userId);
        $profile = $profileModel->where('user_id', $userId)->first();

        $values = array_merge(
            $profile ?? [],
            ['full_name' => $user['full_name']],
            session()->getFlashdata('profile_values') ?? []
        );

        return view('profile/edit', [
            'title'      => 'Edit Profil',
            'activePage' => 'profile',
            'user'       => $user,
            'values'     => $values,
            'isComplete' => $profileModel->isComplete($profile),
        ]);
    }

    public function update()
    {
        helper('url');

        // Identitas pemilik diambil dari session, bukan formulir.
        $userId = (int) session()->get('user_id');

        $fields = [
            'full_name',
            'staff_category',
            'gender',
            'religion',
            'birth_city',
            'birth_date',
            'address',
            'phone',
        ];

        $data = [];

        foreach ($fields as $field) {
            $value = $this->request->getPost($field);
            $data[$field] = is_string($value) ? trim($value) : '';
        }

        $rules = [
            'full_name' => [
                'label' => 'Nama lengkap',
                'rules' => 'required|max_length[200]',
            ],
            'staff_category' => [
                'label' => 'Kategori staff',
                'rules' => 'required|in_list[academic,academic_support]',
            ],
            'gender' => [
                'label' => 'Jenis kelamin',
                'rules' => 'required|in_list[male,female]',
            ],
            'religion' => [
                'label' => 'Agama / kepercayaan',
                'rules' => 'required|max_length[50]',
            ],
            'birth_city' => [
                'label' => 'Kota lahir',
                'rules' => 'required|max_length[100]',
            ],
            'birth_date' => [
                'label' => 'Tanggal lahir',
                'rules' => 'required|valid_date[Y-m-d]',
            ],
            'address' => [
                'label' => 'Alamat',
                'rules' => 'required|max_length[2000]',
            ],
            'phone' => [
                'label' => 'Nomor telepon',
                'rules' => [
                    'required',
                    'max_length[25]',
                    'regex_match[/^\+?[0-9]{8,15}$/]',
                ],
                'errors' => [
                    'regex_match' => 'Nomor telepon harus berisi 8–15 angka, boleh diawali +, tanpa spasi atau tanda hubung.',
                ],
            ],
        ];

        if (! $this->validateData($data, $rules)) {
            return redirect()->to(site_url('profile/edit'))
                ->with('errors', $this->validator->getErrors())
                ->with('profile_values', $data);
        }

        if ($data['birth_date'] > date('Y-m-d')) {
            return redirect()->to(site_url('profile/edit'))
                ->with('errors', [
                    'birth_date' => 'Tanggal lahir tidak boleh melebihi hari ini.',
                ])
                ->with('profile_values', $data);
        }

        $db = Database::connect();
        $userModel = new UserModel($db);
        $profileModel = new StaffProfileModel($db);

        try {
            if (! $db->transBegin()) {
                throw new RuntimeException('Transaksi gagal dimulai.');
            }

            $savedUser = $userModel->update($userId, [
                'full_name' => $data['full_name'],
            ]);

            $profileData = $data;
            unset($profileData['full_name']);

            $existing = $profileModel
                ->where('user_id', $userId)
                ->first();

            if ($existing) {
                $savedProfile = $profileModel->update(
                    $existing['id'],
                    $profileData
                );
            } else {
                $profileData['user_id'] = $userId;
                $savedProfile = $profileModel->insert($profileData);
            }

            if (
                $savedUser === false
                || $savedProfile === false
                || $db->transStatus() === false
            ) {
                throw new RuntimeException('Profil gagal disimpan.');
            }

            if (! $db->transCommit()) {
                throw new RuntimeException('Transaksi gagal diselesaikan.');
            }
        } catch (Throwable $exception) {
            $db->transRollback();

            log_message(
                'error',
                'Penyimpanan profil gagal: ' . get_class($exception)
            );

            return redirect()->to(site_url('profile/edit'))
                ->with('error', 'Profil belum berhasil disimpan. Silakan coba lagi.')
                ->with('profile_values', $data);
        }

        session()->set('full_name', $data['full_name']);

        return redirect()->to(site_url('profile'))
            ->with('success', 'Profil berhasil disimpan.');
    }
}