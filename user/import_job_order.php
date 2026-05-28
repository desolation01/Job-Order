<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/job_order_repository.php';

$title = 'Import Job Order';
$message = '';
$draft = null;
$categories = category_options($pdo);
$order = [];
$errors = [];
$chosenCategoryIds = [];
$selectedCategoryIds = $chosenCategoryIds;
$otherText = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    if (($_POST['action'] ?? '') === 'save_import') {
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
    } else {
        $file = $_FILES['job_order_image'] ?? null;
        $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png'];

        if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
            $message = 'Upload failed.';
        } elseif ($file['size'] > 5 * 1024 * 1024) {
            $message = 'Image must be 5MB or smaller.';
        } else {
            $mime = mime_content_type($file['tmp_name']);
            if (!isset($allowed[$mime])) {
                $message = 'Only JPG and PNG images are allowed.';
            } else {
                $name = bin2hex(random_bytes(16)) . '.' . $allowed[$mime];
                $target = __DIR__ . '/../assets/uploads/job-order-images/' . $name;
                move_uploaded_file($file['tmp_name'], $target);
                $detectedText = "OCR preview draft\nProject Name: Sample detected project\nRequesting Department: Marketing";
                $stmt = $pdo->prepare('INSERT INTO job_order_imports (user_id, image_path, detected_text, import_status) VALUES (?, ?, ?, "Pending Review")');
                $stmt->execute([(int) current_user()['id'], 'assets/uploads/job-order-images/' . $name, $detectedText]);
                $draft = $detectedText;
                $order = [
                    'requesting_department' => current_user()['department'] ?? '',
                    'project_name' => 'Sample detected project',
                    'date_filed' => date('Y-m-d'),
                    'date_needed' => '',
                    'job_description' => $detectedText,
                    'urgency' => 'Low',
                    'requested_by' => current_user()['full_name'] ?? '',
                    'requested_by_date' => date('Y-m-d'),
                    'noted_by' => '',
                ];
            }
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
?>
<h1>Import Job Order Using Image</h1>
<section class="panel">
    <form method="post" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <p class="muted">OCR/image text detection is a draft-only preview. Review and correct values before saving an official job order.</p>
        <?php if ($message): ?><p class="error"><?= e($message) ?></p><?php endif; ?>
        <label>Job Order Image
            <input type="file" name="job_order_image" accept="image/png,image/jpeg" data-preview="#image-preview" required>
        </label>
        <img id="image-preview" hidden alt="Upload preview" style="max-width: 320px;">
        <button type="submit">Upload and Preview OCR Draft</button>
    </form>
</section>
<?php if ($draft): ?>
    <section class="panel">
        <h2>Pending Review Preview</h2>
        <textarea rows="8"><?= e($draft) ?></textarea>
        <p class="muted">Correct the fields below before saving. The OCR draft is never saved directly as an official job order.</p>
    </section>
    <form method="post">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="save_import">
        <?php require __DIR__ . '/../includes/job_order_form.php'; ?>
        <button type="submit">Save Reviewed Import</button>
    </form>
<?php endif; ?>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
