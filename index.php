<?php
declare(strict_types=1);

session_start();
require_once __DIR__ . '/includes/functions.php';

if (!current_user()) {
    redirect('/job-order-system/auth/login');
}

redirect(is_admin() ? '/job-order-system/admin/dashboard' : '/job-order-system/user/dashboard');

