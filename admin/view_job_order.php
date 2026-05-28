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

$title = 'Admin View Job Order';
$categories = job_order_categories($pdo, $id);
$comments = job_order_comments($pdo, $id);
require_once __DIR__ . '/../includes/header.php';
?>
<h1><?= e($order['project_name']) ?></h1>
<section class="panel actions">
    <?php if ($order['status'] !== 'Approved' && $order['status'] !== 'Archived'): ?>
        <form method="post" action="/job-order-system/admin/status_action" class="inline-form">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= (int) $order['id'] ?>">
            <input type="hidden" name="status" value="Approved">
            <button type="submit" data-confirm="Approve this job order?">Approve</button>
        </form>
    <?php endif; ?>
    <?php if ($order['status'] !== 'Denied' && $order['status'] !== 'Archived'): ?>
        <form method="post" action="/job-order-system/admin/status_action" class="inline-form">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= (int) $order['id'] ?>">
            <input type="hidden" name="status" value="Denied">
            <button type="submit" class="danger" data-confirm="Deny this job order?">Deny</button>
        </form>
    <?php endif; ?>
    <a class="button" href="/job-order-system/admin/edit_job_order?id=<?= $id ?>">Edit</a>
    <a class="button secondary" href="/job-order-system/admin/export_pdf?id=<?= $id ?>">Export PDF</a>
    <a class="button secondary" href="/job-order-system/admin/version_history?id=<?= $id ?>">Version History</a>
</section>
<section class="panel paper-preview">
    <?php require __DIR__ . '/../includes/paper_job_order.php'; ?>
</section>
<section class="panel">
    <h2>Comments</h2>
    <?php foreach ($comments as $comment): ?>
        <p><strong><?= e($comment['full_name']) ?>:</strong> <?= nl2br(e($comment['comment'])) ?> <span class="muted"><?= e($comment['created_at']) ?></span></p>
    <?php endforeach; ?>
</section>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
