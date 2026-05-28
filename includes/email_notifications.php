<?php
declare(strict_types=1);

function notify_job_order_created(PDO $pdo, int $jobOrderId): void
{
    $emailConfig = require __DIR__ . '/../config/email.php';
    if (empty($emailConfig['enabled'])) {
        return;
    }

    $recipients = notification_recipients($pdo, $emailConfig['notify_to'] ?? '');
    if (!$recipients) {
        return;
    }

    $order = find_job_order($pdo, $jobOrderId, null);
    if (!$order) {
        return;
    }

    $categories = array_map(
        static fn(array $category): string => $category['other_category_text'] ?: $category['category_name'],
        job_order_categories($pdo, $jobOrderId)
    );

    $subject = 'New Job Order Submitted: ' . $order['jo_number'];
    $body = implode("\r\n", [
        'A new job order has been submitted.',
        '',
        'J.O No.: ' . $order['jo_number'],
        'Project Name: ' . $order['project_name'],
        'Requesting Department: ' . $order['requesting_department'],
        'Urgency: ' . $order['urgency'],
        'Status: ' . $order['status'],
        'Date Filed: ' . format_display_date($order['date_filed']),
        'Date Needed: ' . format_display_date($order['date_needed']),
        'Submitted By: ' . $order['submitted_by'],
        'Categories: ' . implode(', ', $categories),
        '',
        'Open locally: http://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . '/job-order-system/admin/view_job_order?id=' . $jobOrderId,
    ]);

    try {
        foreach ($recipients as $recipient) {
            smtp_send_email($emailConfig, $recipient, $subject, $body);
        }
    } catch (Throwable $exception) {
        error_log('Job order notification email failed: ' . $exception->getMessage());
    }
}

function ensure_notification_recipient_table(PDO $pdo): void
{
    $pdo->exec('CREATE TABLE IF NOT EXISTS notification_recipients (
        id INT AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(190) NOT NULL UNIQUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )');
}

function notification_recipients(PDO $pdo, string|array $fallback = ''): array
{
    ensure_notification_recipient_table($pdo);
    $rows = $pdo->query('SELECT email FROM notification_recipients ORDER BY email')->fetchAll();
    $emails = array_map(static fn(array $row): string => (string) $row['email'], $rows);

    if (!$emails) {
        $emails = normalize_notification_recipients($fallback);
    }

    return $emails;
}

function save_notification_recipients(PDO $pdo, array $emails): void
{
    ensure_notification_recipient_table($pdo);
    $emails = normalize_notification_recipients($emails);

    $pdo->beginTransaction();
    $pdo->exec('DELETE FROM notification_recipients');
    $stmt = $pdo->prepare('INSERT INTO notification_recipients (email) VALUES (?)');
    foreach ($emails as $email) {
        $stmt->execute([$email]);
    }
    $pdo->commit();
}

function normalize_notification_recipients(string|array $rawEmails): array
{
    if (is_string($rawEmails)) {
        $rawEmails = preg_split('/[\r\n,;]+/', $rawEmails) ?: [];
    }

    $emails = [];
    foreach ($rawEmails as $rawEmail) {
        $email = strtolower(trim((string) $rawEmail));
        if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $emails[$email] = $email;
        }
    }

    return array_values($emails);
}

function smtp_send_email(array $config, string $to, string $subject, string $body): void
{
    foreach (['host', 'username', 'password', 'from_email'] as $requiredKey) {
        if (empty($config[$requiredKey])) {
            return;
        }
    }

    $host = (string) $config['host'];
    $port = (int) ($config['port'] ?? 587);
    $socket = stream_socket_client("tcp://{$host}:{$port}", $errno, $errstr, 10);
    if (!$socket) {
        throw new RuntimeException($errstr ?: 'Unable to connect to SMTP server.');
    }

    stream_set_timeout($socket, 10);
    smtp_expect($socket, [220]);
    smtp_command($socket, 'EHLO localhost', [250]);
    smtp_command($socket, 'STARTTLS', [220]);

    if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
        fclose($socket);
        throw new RuntimeException('Unable to enable SMTP TLS.');
    }

    smtp_command($socket, 'EHLO localhost', [250]);
    smtp_command($socket, 'AUTH LOGIN', [334]);
    smtp_command($socket, base64_encode((string) $config['username']), [334]);
    smtp_command($socket, base64_encode((string) $config['password']), [235]);
    smtp_command($socket, 'MAIL FROM:<' . $config['from_email'] . '>', [250]);
    smtp_command($socket, 'RCPT TO:<' . $to . '>', [250, 251]);
    smtp_command($socket, 'DATA', [354]);

    $headers = [
        'From: ' . smtp_header_address((string) $config['from_name'], (string) $config['from_email']),
        'To: ' . $to,
        'Subject: ' . $subject,
        'MIME-Version: 1.0',
        'Content-Type: text/plain; charset=UTF-8',
    ];
    $message = implode("\r\n", $headers) . "\r\n\r\n" . str_replace("\n.", "\n..", $body);
    smtp_command($socket, $message . "\r\n.", [250]);
    smtp_command($socket, 'QUIT', [221]);
    fclose($socket);
}

function smtp_header_address(string $name, string $email): string
{
    return sprintf('"%s" <%s>', addcslashes($name, '"\\'), $email);
}

function smtp_command($socket, string $command, array $expectedCodes): string
{
    fwrite($socket, $command . "\r\n");
    return smtp_expect($socket, $expectedCodes);
}

function smtp_expect($socket, array $expectedCodes): string
{
    $response = '';
    do {
        $line = fgets($socket, 515);
        if ($line === false) {
            throw new RuntimeException('No SMTP response.');
        }

        $response .= $line;
    } while (isset($line[3]) && $line[3] === '-');

    $code = (int) substr($response, 0, 3);
    if (!in_array($code, $expectedCodes, true)) {
        throw new RuntimeException(trim($response));
    }

    return $response;
}
