<?php
declare(strict_types=1);

session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

if (current_user()) {
    redirect(is_admin() ? '/job-order-system/admin/dashboard' : '/job-order-system/user/dashboard');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $email = trim((string) ($_POST['email'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        session_regenerate_id(true);
        $_SESSION['user'] = [
            'id' => (int) $user['id'],
            'full_name' => $user['full_name'],
            'email' => $user['email'],
            'role' => $user['role'],
            'department' => $user['department'],
        ];
        redirect($user['role'] === 'admin' ? '/job-order-system/admin/dashboard' : '/job-order-system/user/dashboard');
    }

    $error = 'Invalid email or password.';
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login - Job Order System</title>
    <link rel="stylesheet" href="/job-order-system/assets/css/style.css">
</head>
<body class="auth-page">
    <form class="auth-card" method="post">
        <?= csrf_field() ?>
        <h1>Job Order Login</h1>
      
        <?php if ($error): ?><p class="error"><?= e($error) ?></p><?php endif; ?>
        <label>Email
            <input type="email" name="email" required autofocus>
        </label>
        <label>Password
            <input type="password" name="password" required>
        </label>
        <button type="submit">Login</button>
        <p><a href="/job-order-system/auth/register">Create a user account</a></p>
    </form>
</body>
</html>

