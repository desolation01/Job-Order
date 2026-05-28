<?php
declare(strict_types=1);

$title = $title ?? 'Job Order Management System';
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title) ?></title>
    <link rel="stylesheet" href="/job-order-system/assets/css/style.css?v=15">
</head>
<body>
<header class="topbar">
    <div>
        <strong>Skyline Hospital and Medical Center</strong>
        <span>Business Development and Marketing Communications</span>
    </div>
    <?php if (current_user()): ?>
        <div class="account">
            <?= e(current_user()['full_name']) ?> (<?= e(current_user()['role']) ?>)
            <a href="/job-order-system/auth/logout">Logout</a>
        </div>
    <?php endif; ?>
</header>
<div class="shell">
    <?php if (current_user()) require __DIR__ . '/sidebar.php'; ?>
    <main class="content">
