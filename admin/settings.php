<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth_check.php';
require_admin();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/job_order_repository.php';

$title = 'Settings';
$message = '';
$errors = [];
$emailConfig = require __DIR__ . '/../config/email.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $rawEmails = preg_split('/[\r\n,;]+/', (string) ($_POST['notification_emails'] ?? '')) ?: [];
    $emails = normalize_notification_recipients($rawEmails);

    if (!$emails) {
        $errors[] = 'Enter at least one valid notification email.';
    }

    if (!$errors) {
        save_notification_recipients($pdo, $emails);
        $message = 'Notification emails updated.';
    }
}

$notificationEmails = notification_recipients($pdo, $emailConfig['notify_to'] ?? '');

require_once __DIR__ . '/../includes/header.php';
?>
<h1>Settings</h1>
<?php if ($message): ?><p class="panel success-message"><?= e($message) ?></p><?php endif; ?>
<?php foreach ($errors as $error): ?><p class="error"><?= e($error) ?></p><?php endforeach; ?>
<section class="panel">
    <h2>Email Notifications</h2>
    <p class="muted">These emails are notified every time a new job order is submitted.</p>
    <form method="post">
        <?= csrf_field() ?>
        <label>Notification Emails
            <textarea name="notification_emails" rows="7" placeholder="one@email.com&#10;another@email.com"><?= e(implode("\n", $notificationEmails)) ?></textarea>
        </label>
        <button type="submit">Save Settings</button>
    </form>
</section>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
