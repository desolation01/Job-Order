<?php
declare(strict_types=1);

session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $fullName = trim((string) ($_POST['full_name'] ?? ''));
    $email = trim((string) ($_POST['email'] ?? ''));
    $department = trim((string) ($_POST['department'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    if ($fullName === '') $errors[] = 'Full name is required.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email is required.';
    if (strlen($password) < 8) $errors[] = 'Password must be at least 8 characters.';

    if (!$errors) {
        try {
            $stmt = $pdo->prepare('INSERT INTO users (full_name, email, password, role, department) VALUES (?, ?, ?, "user", ?)');
            $stmt->execute([$fullName, $email, password_hash($password, PASSWORD_DEFAULT), $department]);
            redirect('/job-order-system/auth/login');
        } catch (PDOException $exception) {
            $errors[] = 'That email is already registered.';
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Register - Job Order System</title>
    <link rel="stylesheet" href="/job-order-system/assets/css/style.css">
</head>
<body class="auth-page">
    <form class="auth-card" method="post">
        <?= csrf_field() ?>
        <h1>Create Account</h1>
        <?php foreach ($errors as $error): ?><p class="error"><?= e($error) ?></p><?php endforeach; ?>
        <label>Full Name <input name="full_name" required></label>
        <label>Email <input type="email" name="email" required></label>
        <label>Department <input name="department"></label>
        <label>Password <input type="password" name="password" minlength="8" required></label>
        <button type="submit">Register</button>
        <p><a href="/job-order-system/auth/login">Back to login</a></p>
    </form>
</body>
</html>

