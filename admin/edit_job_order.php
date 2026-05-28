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

$title = 'Edit Job Order';
$categories = category_options($pdo);
$existingCategories = job_order_categories($pdo, $id);
$chosenCategoryIds = array_map(fn($category) => (int) $category['id'], $existingCategories);
$selectedCategoryIds = $chosenCategoryIds;
$otherText = '';
foreach ($existingCategories as $category) {
    if ($category['category_name'] === 'Others') {
        $otherText = (string) $category['other_category_text'];
    }
}
$errors = [];
$adminMode = true;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $originalDateFiled = (string) ($order['date_filed'] ?? '');
    $originalChecklistTasks = (string) ($order['checklist_tasks'] ?? '');
    $order = array_merge($order, collect_job_order_input($_POST, true));
    $order['date_filed'] = $originalDateFiled;
    $order['checklist_tasks'] = checklist_tasks_from_texts((array) ($_POST['checklist_tasks'] ?? []), $originalChecklistTasks);
    $chosenCategoryIds = array_map('intval', $_POST['categories'] ?? []);
    $selectedCategoryIds = $chosenCategoryIds;
    $otherText = trim((string) ($_POST['other_category_text'] ?? ''));
    $errors = validate_job_order($order);

    if (!$chosenCategoryIds) {
        $errors['categories'] = 'Select at least one category.';
    }

    if (!$errors) {
        update_job_order_as_admin($pdo, $id, (int) current_user()['id'], $order, $chosenCategoryIds, $otherText);
        redirect('/job-order-system/admin/view_job_order?id=' . $id);
    }
}

require_once __DIR__ . '/../includes/header.php';
?>
<h1>Edit Job Order</h1>
<p class="muted">Changes are written to job_order_history with old_value and new_value for each edited field. Admin comments are visible to the user.</p>
<?php if (isset($errors['categories'])): ?><p class="error"><?= e($errors['categories']) ?></p><?php endif; ?>
<form method="post">
    <?= csrf_field() ?>
    <?php require __DIR__ . '/../includes/job_order_form.php'; ?>
    <button type="submit">Save Changes</button>
    <a class="button secondary" href="/job-order-system/admin/dashboard">Cancel</a>
</form>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
