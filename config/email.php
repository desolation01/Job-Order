<?php
declare(strict_types=1);

$config = [
    'enabled' => (bool) getenv('SMTP_ENABLED'),
    'host' => getenv('SMTP_HOST') ?: '',
    'port' => (int) (getenv('SMTP_PORT') ?: 587),
    'username' => getenv('SMTP_USER') ?: '',
    'password' => getenv('SMTP_PASS') ?: '',
    'from_email' => getenv('SMTP_FROM_EMAIL') ?: '',
    'from_name' => getenv('SMTP_FROM_NAME') ?: 'Job Order System',
    'notify_to' => getenv('JOB_ORDER_NOTIFY_TO') ?: '',
];

$localConfig = __DIR__ . '/email.local.php';
if (is_file($localConfig)) {
    $config = array_merge($config, require $localConfig);
}

return $config;
