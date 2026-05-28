<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/job_order_repository.php';

$title = 'Create Job Order';
$categories = category_options($pdo);
$errors = [];
$order = [];
$chosenCategoryIds = [];
$selectedCategoryIds = $chosenCategoryIds;
$otherText = '';
$coreFields = 'requesting_department project_name date_needed job_description urgency';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $order = collect_job_order_input($_POST, false);
    $order['date_filed'] = date('Y-m-d');
    $chosenCategoryIds = array_map('intval', $_POST['categories'] ?? []);
    $selectedCategoryIds = $chosenCategoryIds;
    $otherText = trim((string) ($_POST['other_category_text'] ?? ''));
    $errors = validate_job_order($order);

    if (!$chosenCategoryIds) {
        $errors['categories'] = 'Select at least one category.';
    }

    if (!$errors) {
        create_job_order($pdo, (int) current_user()['id'], $order, $chosenCategoryIds, $otherText);
        redirect('/job-order-system/user/dashboard');
    }
}

require_once __DIR__ . '/../includes/header.php';
?>
<h1>Create Job Order</h1>
<span hidden><?= e($coreFields) ?></span>
<?php if (isset($errors['categories'])): ?><p class="error"><?= e($errors['categories']) ?></p><?php endif; ?>
<form method="post">
    <?= csrf_field() ?>
    <?php require __DIR__ . '/../includes/job_order_form.php'; ?>
    <button type="submit">Submit Job Order</button>
</form>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
