<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/job_order_repository.php';

$id = (int) ($_GET['id'] ?? 0);
$order = find_job_order($pdo, $id, (int) current_user()['id']);
if (!$order) {
    http_response_code(404);
    exit('Job order not found.');
}

$stmt = $pdo->prepare("SELECT h.field_changed, h.old_value, h.new_value, h.edited_at, u.full_name AS edited_by
    FROM job_order_history h
    INNER JOIN users u ON u.id = h.edited_by
    WHERE h.job_order_id = ?
    ORDER BY h.edited_at DESC, h.id DESC");
$stmt->execute([$id]);
$history = $stmt->fetchAll();

$title = 'Job Order History';
require_once __DIR__ . '/../includes/header.php';
?>
<h1>History: <?= e($order['jo_number']) ?></h1>
<p class="muted">Shows admin edits and status changes for this job order.</p>
<section class="panel actions">
    <a class="button secondary" href="/job-order-system/user/view_job_order?id=<?= $id ?>">Back to Job Order</a>
    <a class="button secondary" href="/job-order-system/user/dashboard">User Dashboard</a>
</section>
<div class="table-wrap">
    <table>
        <thead><tr><th>Field Changed</th><th>Old Value</th><th>New Value</th><th>Edited By</th><th>Date Edited</th></tr></thead>
        <tbody>
        <?php if (!$history): ?>
            <tr><td colspan="5">No edit history yet.</td></tr>
        <?php endif; ?>
        <?php foreach ($history as $row): ?>
            <tr>
                <td><?= e(ucwords(str_replace('_', ' ', strtolower((string) $row['field_changed'])))) ?></td>
                <td><?= nl2br(e(format_history_value((string) $row['field_changed'], $row['old_value']))) ?></td>
                <td><?= nl2br(e(format_history_value((string) $row['field_changed'], $row['new_value']))) ?></td>
                <td><?= e($row['edited_by']) ?></td>
                <td><?= e($row['edited_at']) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
