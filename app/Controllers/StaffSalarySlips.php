<?php

namespace App\Controllers;

use App\Models\SalarySlipModel;

class StaffSalarySlips extends BaseController
{
    public function index()
    {
        helper('url');

        $model = new SalarySlipModel();

        $slips = $model
            ->where('user_id', (int) session()->get('user_id'))
            ->where('status', 'published')
            ->orderBy('period_year', 'DESC')
            ->orderBy('period_month', 'DESC')
            ->orderBy('id', 'DESC')
            ->paginate(10);

        return view('salary_slips/index', [
            'title' => 'Slip Gaji Saya',
            'activePage' => 'my-salary-slips',
            'slips' => $slips,
            'pager' => $model->pager,
        ]);
    }
}