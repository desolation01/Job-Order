<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/job_order_repository.php';

$title = 'Manage Job Orders';
$filters = job_order_filters($_GET);
$orders = list_job_orders($pdo, (int) current_user()['id'], $filters);
$categories = category_options($pdo);
require_once __DIR__ . '/../includes/header.php';
?>
<h1>Manage Job Orders</h1>
<form class="panel toolbar" method="get">
    <input name="search" placeholder="Search project, description, J.O no., department" value="<?= e($filters['search']) ?>">
    <select name="category">
        <option value="">All categories</option>
        <?php foreach ($categories as $category): ?>
            <option value="<?= e($category['category_name']) ?>" <?= selected($filters['category'], $category['category_name']) ?>><?= e($category['category_name']) ?></option>
        <?php endforeach; ?>
    </select>
    <select name="status">
        <option value="">All statuses</option>
        <?php foreach (['Pending', 'Approved', 'Denied', 'Archived'] as $status): ?>
            <option <?= selected($filters['status'], $status) ?>><?= e($status) ?></option>
        <?php endforeach; ?>
    </select>
    <select name="urgency">
        <option value="">All urgency</option>
        <?php foreach (['Low', 'Medium', 'High', 'Critical'] as $urgency): ?>
            <option <?= selected($filters['urgency'], $urgency) ?>><?= e($urgency) ?></option>
        <?php endforeach; ?>
    </select>
    <select name="sort">
        <?php foreach (['date_filed' => 'Date Filed', 'date_needed' => 'Date Needed'] as $sortValue => $sortLabel): ?>
            <option value="<?= e($sortValue) ?>" <?= selected($filters['sort'], $sortValue) ?>><?= e($sortLabel) ?></option>
        <?php endforeach; ?>
    </select>
    <select name="direction">
        <option value="DESC" <?= selected($filters['direction'], 'DESC') ?>>Descending</option>
        <option value="ASC" <?= selected($filters['direction'], 'ASC') ?>>Ascending</option>
    </select>
    <button type="submit">Filter</button>
    <a class="button secondary" href="/job-order-system/user/import_job_order">Import Job Order</a>
</form>
<div class="table-wrap">
    <table>
        <thead><tr><th>J.O No.</th><th>Project Name</th><th>Requesting Department</th><th>Category</th><th>Urgency</th><th>Status</th><th>Date Filed</th><th>Date Needed</th><th>Actions</th></tr></thead>
        <tbody>
        <?php foreach ($orders as $order): ?>
            <tr>
                <td><?= e($order['jo_number']) ?></td>
                <td><?= e($order['project_name']) ?></td>
                <td><?= e($order['requesting_department']) ?></td>
                <td><?= e($order['categories'] ?? '') ?></td>
                <td><span class="badge <?= e(badge_class($order['urgency'])) ?>"><?= e($order['urgency']) ?></span></td>
                <td><span class="badge <?= e(badge_class($order['status'])) ?>"><?= e($order['status']) ?></span></td>
                <td><?= e(format_display_date($order['date_filed'])) ?></td>
                <td><?= e(format_display_date($order['date_needed'])) ?></td>
                <td class="actions">
                    <a href="/job-order-system/user/view_job_order?id=<?= (int) $order['id'] ?>">View</a>
                    <a href="/job-order-system/user/version_history?id=<?= (int) $order['id'] ?>">Edit History</a>
                    <a href="/job-order-system/user/export_pdf?id=<?= (int) $order['id'] ?>">Export PDF</a>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
