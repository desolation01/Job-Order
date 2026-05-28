<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/job_order_repository.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method not allowed.');
}

verify_csrf();

$jobOrderId = (int) ($_POST['id'] ?? 0);
$doneTasks = (array) ($_POST['done_tasks'] ?? []);
$userId = is_admin() ? null : (int) current_user()['id'];

update_job_order_checklist($pdo, $jobOrderId, $userId, $doneTasks);

$fallback = is_admin() ? '/job-order-system/admin/dashboard' : '/job-order-system/user/dashboard';
redirect($_SERVER['HTTP_REFERER'] ?? $fallback);
