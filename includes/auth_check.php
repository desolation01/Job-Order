<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/functions.php';

if (!current_user()) {
    redirect('/job-order-system/auth/login');
}

function require_admin(): void
{
    if (!is_admin()) {
        http_response_code(403);
        exit('Admin access required.');
    }
}

