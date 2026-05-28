<?php
declare(strict_types=1);
?>
<nav class="sidebar">
    <?php if (is_admin()): ?>
        <a href="/job-order-system/admin/dashboard">Admin Dashboard</a>
        <a href="/job-order-system/user/create_job_order">Create Job Order</a>
        <a href="/job-order-system/admin/settings">Settings</a>
    <?php else: ?>
        <a href="/job-order-system/user/dashboard">Dashboard</a>
        <a href="/job-order-system/user/create_job_order">Create Job Order</a>
    <?php endif; ?>
</nav>
