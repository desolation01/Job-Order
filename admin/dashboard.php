<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth_check.php';
require_admin();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/job_order_repository.php';

$title = 'Admin Dashboard';
$filters = job_order_filters($_GET);
$orders = list_job_orders($pdo, null, $filters);
$counts = dashboard_counts($pdo, null);
$categories = category_options($pdo);
$cardFilterBase = [
    'search' => $filters['search'],
    'category' => $filters['category'],
    'urgency' => $filters['urgency'],
    'sort' => $filters['sort'],
    'direction' => $filters['direction'],
];
$urgencyFilterBase = [
    'search' => $filters['search'],
    'category' => $filters['category'],
    'status' => $filters['status'],
    'sort' => $filters['sort'],
    'direction' => $filters['direction'],
];
require_once __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-title-row">
    <h1>Admin Dashboard</h1>
    <div class="urgency-buttons" aria-label="Urgency levels">
        <a class="urgency-button urgency-low<?= $filters['urgency'] === 'Low' ? ' active' : '' ?>" href="/job-order-system/admin/dashboard?<?= e(http_build_query(array_merge($urgencyFilterBase, ['urgency' => 'Low']))) ?>">Low</a>
        <a class="urgency-button urgency-medium<?= $filters['urgency'] === 'Medium' ? ' active' : '' ?>" href="/job-order-system/admin/dashboard?<?= e(http_build_query(array_merge($urgencyFilterBase, ['urgency' => 'Medium']))) ?>">Medium</a>
        <a class="urgency-button urgency-high<?= $filters['urgency'] === 'High' ? ' active' : '' ?>" href="/job-order-system/admin/dashboard?<?= e(http_build_query(array_merge($urgencyFilterBase, ['urgency' => 'High']))) ?>">High</a>
        <a class="urgency-button urgency-critical<?= $filters['urgency'] === 'Critical' ? ' active' : '' ?>" href="/job-order-system/admin/dashboard?<?= e(http_build_query(array_merge($urgencyFilterBase, ['urgency' => 'Critical']))) ?>">Critical</a>
    </div>
</div>
<section class="cards">
    <a class="card card-link<?= $filters['status'] === '' ? ' active' : '' ?>" href="/job-order-system/admin/dashboard?<?= e(http_build_query($cardFilterBase)) ?>">
        <span class="muted">Total Job Orders</span><strong><?= (int) $counts['total'] ?></strong>
    </a>
    <a class="card card-link<?= $filters['status'] === 'Pending' ? ' active' : '' ?>" href="/job-order-system/admin/dashboard?<?= e(http_build_query(array_merge($cardFilterBase, ['status' => 'Pending']))) ?>">
        <span class="muted">Pending</span><strong><?= (int) $counts['Pending'] ?></strong>
    </a>
    <a class="card card-link<?= $filters['status'] === 'Approved' ? ' active' : '' ?>" href="/job-order-system/admin/dashboard?<?= e(http_build_query(array_merge($cardFilterBase, ['status' => 'Approved']))) ?>">
        <span class="muted">Approved</span><strong><?= (int) $counts['Approved'] ?></strong>
    </a>
    <a class="card card-link<?= $filters['status'] === 'Denied' ? ' active' : '' ?>" href="/job-order-system/admin/dashboard?<?= e(http_build_query(array_merge($cardFilterBase, ['status' => 'Denied']))) ?>">
        <span class="muted">Denied</span><strong><?= (int) $counts['Denied'] ?></strong>
    </a>
</section>
<form class="panel toolbar" method="get">
    <input name="search" placeholder="Search all job orders" value="<?= e($filters['search']) ?>">
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
        <?php foreach (['date_filed' => 'Date Filed', 'date_needed' => 'Date Needed', 'project_name' => 'Project Name', 'requesting_department' => 'Requesting Department'] as $sortValue => $sortLabel): ?>
            <option value="<?= e($sortValue) ?>" <?= selected($filters['sort'], $sortValue) ?>><?= e($sortLabel) ?></option>
        <?php endforeach; ?>
    </select>
    <select name="direction">
        <option value="DESC" <?= selected($filters['direction'], 'DESC') ?>>Descending</option>
        <option value="ASC" <?= selected($filters['direction'], 'ASC') ?>>Ascending</option>
    </select>
    <button type="submit">Filter</button>
</form>
<div class="table-wrap">
    <table>
        <thead><tr><th>J.O No.</th><th>Project Name</th><th>Department</th><th>Category</th><th>Urgency</th><th>Status</th><th>Date Filed</th><th>Date Needed</th><th>Submitted By</th><th>Actions</th></tr></thead>
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
                <td><?= e($order['submitted_by']) ?></td>
                <td class="actions">
                    <?php if ($order['status'] !== 'Approved'): ?>
                        <form method="post" action="/job-order-system/admin/status_action" class="inline-form">
                            <?= csrf_field() ?>
                            <input type="hidden" name="id" value="<?= (int) $order['id'] ?>">
                            <input type="hidden" name="status" value="Approved">
                            <button type="submit" class="compact">Approve</button>
                        </form>
                    <?php endif; ?>
                    <?php if ($order['status'] !== 'Denied'): ?>
                        <form method="post" action="/job-order-system/admin/status_action" class="inline-form">
                            <?= csrf_field() ?>
                            <input type="hidden" name="id" value="<?= (int) $order['id'] ?>">
                            <input type="hidden" name="status" value="Denied">
                            <button type="submit" class="compact danger" data-confirm="Deny this job order?">Deny</button>
                        </form>
                    <?php endif; ?>
                    <a href="/job-order-system/admin/view_job_order?id=<?= (int) $order['id'] ?>">View</a>
                    <a href="/job-order-system/admin/edit_job_order?id=<?= (int) $order['id'] ?>">Edit</a>
                    <a href="/job-order-system/admin/export_pdf?id=<?= (int) $order['id'] ?>">Export PDF</a>
                    <a href="/job-order-system/admin/version_history?id=<?= (int) $order['id'] ?>">History</a>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
