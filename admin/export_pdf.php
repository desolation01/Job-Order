<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth_check.php';
require_admin();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/job_order_repository.php';

$id = (int) ($_GET['id'] ?? 0);
$order = find_job_order($pdo, $id, null);
if (!$order) {
    http_response_code(404);
    exit('Job order not found.');
}

$categories = job_order_categories($pdo, $id);
header('Content-Type: text/html; charset=utf-8');
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Admin Export PDF - <?= e($order['jo_number']) ?></title>
    <link rel="stylesheet" href="/job-order-system/assets/css/style.css?v=15">
</head>
<body>
<main class="content">
    <div class="export-actions no-print">
        <button type="button" onclick="window.print()">Print Form</button>
        <button type="button" class="secondary" onclick="savePdfFile('/job-order-system/admin/download_pdf?id=<?= $id ?>', '<?= e($order['jo_number']) ?>.pdf')">Save as PDF</button>
    </div>
    <?php require __DIR__ . '/../includes/paper_job_order.php'; ?>
    <p class="muted no-print">TCPDF/FPDF can replace this HTML job order form exporter for fixed-coordinate PDF Output().</p>
</main>
<script src="/job-order-system/assets/js/main.js"></script>
</body>
</html>
