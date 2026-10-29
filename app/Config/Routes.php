<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */
$routes->get('/', 'Auth::login', ['filter' => 'csrf']);
$routes->get('login', 'Auth::login', ['filter' => 'csrf']);

$routes->get('register', 'Auth::register', ['filter' => 'csrf']);
$routes->post('register', 'Auth::store', ['filter' => 'csrf']);

$routes->get(
    'verification',
    'Verification::notice',
    ['filter' => 'csrf']
);

$routes->post(
    'verification/resend',
    'Verification::resend',
    ['filter' => 'csrf']
);

$routes->get(
    'verify-email/(:segment)',
    'Verification::confirm/$1',
    ['filter' => 'csrf']
);

$routes->post(
    'verify-email',
    'Verification::complete',
    ['filter' => 'csrf']
);

$routes->setAutoRoute(false);

$routes->post('login', 'Auth::attemptLogin', ['filter' => 'csrf']);
$routes->post('logout', 'Auth::logout', ['filter' => 'csrf']);

$routes->get(
    'dashboard',
    'Dashboard::index',
    ['filter' => 'staffauth']
);

$routes->get(
    'admin/dashboard',
    'Dashboard::admin',
    ['filter' => ['staffauth:admin', 'csrf']]
);

$routes->group(
    'admin/staff',
    ['filter' => ['staffauth:admin', 'csrf']],
    static function ($routes) {
        $routes->get('/', 'AdminStaff::index');
        $routes->get('(:num)', 'AdminStaff::show/$1');
        $routes->get('(:num)/photo', 'AdminStaff::photo/$1');

        $routes->get(
            '(:num)/documents/(:num)/preview',
            'AdminStaff::previewDocument/$1/$2'
        );

        $routes->get(
            '(:num)/documents/(:num)/view',
            'AdminStaff::viewDocument/$1/$2'
        );

        $routes->get(
            '(:num)/documents/(:num)/download',
            'AdminStaff::downloadDocument/$1/$2'
        );
        $routes->post('(:num)/status', 'AdminStaff::updateStatus/$1');
        $routes->get('(:num)/edit', 'AdminStaff::edit/$1');
        $routes->post('(:num)/promote', 'AdminStaff::promote/$1');
        $routes->post('(:num)/update', 'AdminStaff::update/$1');
    }
);

$routes->group(
    'admin/accounts',
    ['filter' => ['staffauth:admin', 'csrf']],
    static function ($routes) {
        $routes->get('/', 'AdminAccounts::index');
        $routes->get('create', 'AdminAccounts::create');
        $routes->post('/', 'AdminAccounts::store');
        $routes->post('(:num)/manage', 'AdminAccounts::manage/$1');
    }
);

$routes->get(
    'admin/documents',
    'AdminDocuments::index',
    ['filter' => ['staffauth:admin', 'csrf']]
);

$routes->get(
    'staff/dashboard',
    'Dashboard::staff',
    ['filter' => ['staffauth', 'profilecomplete', 'csrf']]
);



$routes->group(
    'staff/leave-requests',
    ['filter' => ['staffauth', 'profilecomplete', 'csrf']],
    static function ($routes) {
        $routes->get('/', 'StaffLeaveRequests::index');
        $routes->get('history', 'StaffLeaveRequests::history');
        $routes->post('/', 'StaffLeaveRequests::store');
    }
);



$routes->get(
    'profile',
    'Profile::index',
    ['filter' => ['staffauth', 'csrf']]
);

$routes->get(
    'profile/edit',
    'Profile::edit',
    ['filter' => ['staffauth', 'csrf']]
);

$routes->post(
    'profile',
    'Profile::update',
    ['filter' => ['staffauth', 'csrf']]
);

$routes->get(
    'profile/photo',
    'ProfilePhoto::show',
    ['filter' => 'staffauth']
);

$routes->post(
    'profile/photo',
    'ProfilePhoto::upload',
    ['filter' => ['staffauth', 'csrf']]
);

$routes->get(
    'profile/password',
    'AccountPassword::edit',
    ['filter' => ['staffauth', 'csrf']]
);

$routes->post(
    'profile/password',
    'AccountPassword::update',
    ['filter' => ['staffauth', 'csrf']]
);

$routes->group(
    'documents',
    ['filter' => ['staffauth', 'profilecomplete', 'csrf']],
    static function ($routes) {
        $routes->get('/', 'Documents::index');
        $routes->get('create', 'Documents::create');
        $routes->post('/', 'Documents::store');

        $routes->get('(:num)/download', 'Documents::download/$1');
        $routes->get('(:num)/preview', 'Documents::preview/$1');
        $routes->get('(:num)/view', 'Documents::viewFile/$1');
        $routes->get('(:num)/edit', 'Documents::edit/$1');
        $routes->post('(:num)/update', 'Documents::update/$1');
        $routes->post('(:num)/delete', 'Documents::delete/$1');
    }
);

$routes->group(
    'admin/mail',
    ['filter' => ['staffauth:admin', 'csrf']],
    static function ($routes) {
        $routes->get('/', 'MicrosoftMail::index');
        $routes->post('connect', 'MicrosoftMail::connect');
        $routes->get('callback', 'MicrosoftMail::callback');
        $routes->post('test', 'MicrosoftMail::test');
    }
);

$routes->get(
    'forgot-password',
    'PasswordRecovery::forgot',
    ['filter' => 'csrf']
);

$routes->post(
    'forgot-password',
    'PasswordRecovery::requestReset',
    ['filter' => 'csrf']
);

$routes->get(
    'reset-password/(:segment)',
    'PasswordRecovery::form/$1',
    ['filter' => 'csrf']
);

$routes->post(
    'reset-password',
    'PasswordRecovery::complete',
    ['filter' => 'csrf']
);

$routes->group(
    'admin/salary-slips',
    ['filter' => ['staffauth:admin', 'csrf']],
    static function ($routes) {
        $routes->get('/', 'AdminSalarySlips::index');
        $routes->get('create', 'AdminSalarySlips::create');
        $routes->post('/', 'AdminSalarySlips::store');

        $routes->get('(:num)', 'AdminSalarySlips::show/$1');
        $routes->get('(:num)/edit', 'AdminSalarySlips::edit/$1');
        $routes->post('(:num)/update', 'AdminSalarySlips::update/$1');
        $routes->post('(:num)/delete', 'AdminSalarySlips::delete/$1');
        $routes->post('(:num)/publish', 'SalarySlipFiles::publish/$1');
    }
);

$routes->group(
    'staff/salary-slips',
    ['filter' => ['staffauth', 'csrf']],
    static function ($routes) {
        $routes->get('/', 'StaffSalarySlips::index');
    }
);

$routes->group(
    'salary-slips',
    ['filter' => ['staffauth', 'csrf']],
    static function ($routes) {
        $routes->get('(:num)/pdf', 'SalarySlipFiles::preview/$1');
        $routes->get('(:num)/download', 'SalarySlipFiles::download/$1');
    }
);

$routes->group(
    'notifications',
    ['filter' => ['staffauth', 'csrf']],
    static function ($routes) {
        $routes->get('/', 'Notifications::index');

        $routes->post('read-all', 'Notifications::readAll');

        $routes->post('(:num)/open', 'Notifications::open/$1');
        $routes->post('(:num)/read', 'Notifications::read/$1');
    }
);

$routes->group(
    'admin/leave-requests',
    ['filter' => ['staffauth:admin', 'csrf']],
    static function ($routes) {
        $routes->get('/', 'AdminLeaveRequests::index');
        $routes->post('(:num)/review', 'AdminLeaveRequests::review/$1');
    }
);
