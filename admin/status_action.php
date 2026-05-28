<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth_check.php';
require_admin();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/job_order_repository.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method not allowed.');
}

verify_csrf();

$jobOrderId = (int) ($_POST['id'] ?? 0);
$status = (string) ($_POST['status'] ?? '');
$comment = trim((string) ($_POST['comment'] ?? ''));

if ($status === 'Approved' && $comment === '') {
    $comment = 'Approved by BD&MC.';
}

if ($status === 'Denied' && $comment === '') {
    $comment = 'Denied by BD&MC.';
}

update_job_order_status($pdo, $jobOrderId, (int) current_user()['id'], $status, $comment);

$redirect = $_SERVER['HTTP_REFERER'] ?? '/job-order-system/admin/dashboard';
header('Location: ' . $redirect);
exit;

