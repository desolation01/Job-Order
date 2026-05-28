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

$stmt = $pdo->prepare("SELECT h.field_changed, h.old_value, h.new_value, h.edited_at, u.full_name AS edited_by
    FROM job_order_history h
    INNER JOIN users u ON u.id = h.edited_by
    WHERE h.job_order_id = ?
    ORDER BY h.edited_at DESC, h.id DESC");
$stmt->execute([$id]);
$history = $stmt->fetchAll();

$title = 'Version History';
require_once __DIR__ . '/../includes/header.php';
?>
<h1>Version History: <?= e($order['jo_number']) ?></h1>
<div class="table-wrap">
    <table>
        <thead><tr><th>Field Changed</th><th>Old Value</th><th>New Value</th><th>Edited By</th><th>Date Edited</th></tr></thead>
        <tbody>
        <?php foreach ($history as $row): ?>
            <tr>
                <td><?= e(ucwords(str_replace('_', ' ', strtolower((string) $row['field_changed'])))) ?></td>
                <td><?= nl2br(e($row['old_value'])) ?></td>
                <td><?= nl2br(e($row['new_value'])) ?></td>
                <td><?= e($row['edited_by']) ?></td>
                <td><?= e($row['edited_at']) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
