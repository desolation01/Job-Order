<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth_check.php';
require_admin();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/job_order_repository.php';
require_once __DIR__ . '/../includes/simple_pdf.php';

$id = (int) ($_GET['id'] ?? 0);
$order = find_job_order($pdo, $id, null);
if (!$order) {
    http_response_code(404);
    exit('Job order not found.');
}

output_job_order_pdf($order, job_order_categories($pdo, $id));

