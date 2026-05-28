<?php
declare(strict_types=1);

require_once __DIR__ . '/email_notifications.php';

function category_options(PDO $pdo): array
{
    return $pdo->query('SELECT id, category_name FROM categories ORDER BY id')->fetchAll();
}

function generate_jo_number(PDO $pdo): string
{
    $datePart = date('mdY');
    $stmt = $pdo->query("SELECT MAX(CAST(RIGHT(jo_number, 5) AS UNSIGNED)) FROM job_orders WHERE jo_number REGEXP '[0-9]{5}$'");
    $lastSequence = (int) $stmt->fetchColumn();
    $nextSequence = $lastSequence + 1;

    return $datePart . str_pad((string) $nextSequence, 5, '0', STR_PAD_LEFT);
}

function job_order_filters(array $input): array
{
    $allowedSorts = ['date_filed', 'date_needed', 'project_name', 'requesting_department'];
    $allowedGroupSorts = ['', 'category', 'status', 'urgency'];
    $requestedGroupSort = (string) ($input['group_sort'] ?? '');
    $sort = in_array($input['sort'] ?? '', $allowedSorts, true) ? $input['sort'] : 'date_filed';
    $groupSort = in_array($requestedGroupSort, $allowedGroupSorts, true) ? $requestedGroupSort : '';
    $direction = strtoupper($input['direction'] ?? 'DESC') === 'ASC' ? 'ASC' : 'DESC';

    return [
        'search' => trim((string) ($input['search'] ?? '')),
        'category' => trim((string) ($input['category'] ?? '')),
        'status' => trim((string) ($input['status'] ?? '')),
        'urgency' => trim((string) ($input['urgency'] ?? '')),
        'sort' => $sort,
        'group_sort' => $groupSort,
        'direction' => $direction,
    ];
}

function list_job_orders(PDO $pdo, ?int $userId, array $filters): array
{
    $filters = array_merge([
        'search' => '',
        'category' => '',
        'status' => '',
        'urgency' => '',
        'sort' => 'date_filed',
        'group_sort' => '',
        'direction' => 'DESC',
    ], $filters);

    $where = [];
    $params = [];

    if ($userId !== null) {
        $where[] = 'jo.user_id = ?';
        $params[] = $userId;
    }

    if ($filters['search'] !== '') {
        $where[] = '(jo.project_name LIKE ? OR jo.job_description LIKE ? OR jo.jo_number LIKE ? OR jo.requesting_department LIKE ?)';
        $keyword = '%' . $filters['search'] . '%';
        array_push($params, $keyword, $keyword, $keyword, $keyword);
    }

    if ($filters['status'] !== '') {
        $where[] = 'jo.status = ?';
        $params[] = $filters['status'];
    } else {
        $where[] = 'jo.status <> ?';
        $params[] = 'Archived';
    }

    if ($filters['urgency'] !== '') {
        $where[] = 'jo.urgency = ?';
        $params[] = $filters['urgency'];
    }

    if ($filters['category'] !== '') {
        $where[] = 'EXISTS (
            SELECT 1 FROM job_order_categories joc
            INNER JOIN categories c ON c.id = joc.category_id
            WHERE joc.job_order_id = jo.id AND c.category_name = ?
        )';
        $params[] = $filters['category'];
    }

    $sql = "SELECT jo.*, u.full_name AS submitted_by,
            GROUP_CONCAT(COALESCE(NULLIF(joc.other_category_text, ''), c.category_name) ORDER BY c.id SEPARATOR ', ') AS categories
        FROM job_orders jo
        INNER JOIN users u ON u.id = jo.user_id
        LEFT JOIN job_order_categories joc ON joc.job_order_id = jo.id
        LEFT JOIN categories c ON c.id = joc.category_id";

    if ($where) {
        $sql .= ' WHERE ' . implode(' AND ', $where);
    }

    $orderParts = [];
    if ($filters['group_sort'] === 'category') {
        $orderParts[] = 'categories ' . $filters['direction'];
    } elseif (in_array($filters['group_sort'], ['status', 'urgency'], true)) {
        $orderParts[] = 'jo.' . $filters['group_sort'] . ' ' . $filters['direction'];
    }
    $orderParts[] = 'jo.' . $filters['sort'] . ' ' . $filters['direction'];

    $sql .= ' GROUP BY jo.id ORDER BY ' . implode(', ', $orderParts);
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function dashboard_counts(PDO $pdo, ?int $userId = null): array
{
    $where = $userId === null ? '' : 'WHERE user_id = ?';
    $params = $userId === null ? [] : [$userId];
    $stmt = $pdo->prepare("SELECT status, COUNT(*) AS total FROM job_orders {$where} GROUP BY status");
    $stmt->execute($params);

    $counts = ['total' => 0, 'Pending' => 0, 'Approved' => 0, 'Denied' => 0, 'Archived' => 0];
    foreach ($stmt->fetchAll() as $row) {
        $total = (int) $row['total'];
        $status = canonical_job_order_status((string) $row['status']);
        if (array_key_exists($status, $counts)) {
            $counts[$status] += $total;
        }
        if ($status !== 'Archived') {
            $counts['total'] += $total;
        }
    }

    return $counts;
}

function canonical_job_order_status(string $status): string
{
    return match (strtolower(trim($status))) {
        'approved' => 'Approved',
        'denied' => 'Denied',
        'archived' => 'Archived',
        default => 'Pending',
    };
}

function is_valid_job_order_status(string $status): bool
{
    return in_array(strtolower(trim($status)), ['pending', 'approved', 'denied', 'archived'], true);
}

function find_job_order(PDO $pdo, int $id, ?int $userId = null): ?array
{
    $sql = "SELECT jo.*, u.full_name AS submitted_by, u.email AS submitted_email
        FROM job_orders jo
        INNER JOIN users u ON u.id = jo.user_id
        WHERE jo.id = ?";
    $params = [$id];

    if ($userId !== null) {
        $sql .= ' AND jo.user_id = ?';
        $params[] = $userId;
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $order = $stmt->fetch();
    return $order ?: null;
}

function job_order_categories(PDO $pdo, int $jobOrderId): array
{
    $stmt = $pdo->prepare("SELECT c.id, c.category_name, joc.other_category_text
        FROM job_order_categories joc
        INNER JOIN categories c ON c.id = joc.category_id
        WHERE joc.job_order_id = ?
        ORDER BY c.id");
    $stmt->execute([$jobOrderId]);
    return $stmt->fetchAll();
}

function job_order_comments(PDO $pdo, int $jobOrderId): array
{
    $stmt = $pdo->prepare("SELECT joc.comment, joc.created_at, u.full_name
        FROM job_order_comments joc
        INNER JOIN users u ON u.id = joc.admin_id
        WHERE joc.job_order_id = ?
        ORDER BY joc.created_at DESC");
    $stmt->execute([$jobOrderId]);
    return $stmt->fetchAll();
}

function normalize_checklist_tasks(?string $encodedTasks): array
{
    if (!$encodedTasks) {
        return [];
    }

    $decodedTasks = json_decode($encodedTasks, true);
    if (!is_array($decodedTasks)) {
        return [];
    }

    $tasks = [];
    foreach ($decodedTasks as $task) {
        if (is_array($task)) {
            $text = trim((string) ($task['text'] ?? ''));
            $done = !empty($task['done']);
        } else {
            $text = trim((string) $task);
            $done = false;
        }

        if ($text !== '') {
            $tasks[] = ['text' => $text, 'done' => $done];
        }
    }

    return $tasks;
}

function checklist_tasks_from_texts(array $rawTasks, ?string $existingTasks = null): ?string
{
    $existingDoneByText = [];
    foreach (normalize_checklist_tasks($existingTasks) as $task) {
        $existingDoneByText[$task['text']][] = $task['done'];
    }

    $tasks = [];
    foreach ($rawTasks as $rawTask) {
        $text = trim((string) $rawTask);
        if ($text === '') {
            continue;
        }

        $done = false;
        if (!empty($existingDoneByText[$text])) {
            $done = (bool) array_shift($existingDoneByText[$text]);
        }

        $tasks[] = ['text' => $text, 'done' => $done];
    }

    return $tasks ? json_encode($tasks, JSON_UNESCAPED_UNICODE) : null;
}

function checklist_progress(?string $encodedTasks): array
{
    $tasks = normalize_checklist_tasks($encodedTasks);
    $total = count($tasks);
    $done = count(array_filter($tasks, static fn(array $task) => $task['done']));

    return [
        'total' => $total,
        'done' => $done,
        'percent' => $total > 0 ? (int) round(($done / $total) * 100) : 0,
    ];
}

function checklist_progress_class(array $progress): string
{
    if ($progress['total'] === 0) {
        return 'progress-empty';
    }

    if ($progress['percent'] === 100) {
        return 'progress-complete';
    }

    if ($progress['percent'] >= 67) {
        return 'progress-high';
    }

    if ($progress['percent'] >= 34) {
        return 'progress-medium';
    }

    return 'progress-low';
}

function format_history_value(string $field, ?string $value): string
{
    if ($field !== 'checklist_tasks') {
        return (string) $value;
    }

    $tasks = normalize_checklist_tasks($value);
    if (!$tasks) {
        return '';
    }

    return implode("\n", array_map(static function (array $task): string {
        return ($task['done'] ? '[Done] ' : '[Pending] ') . $task['text'];
    }, $tasks));
}

function update_job_order_checklist(PDO $pdo, int $jobOrderId, ?int $userId, array $doneIndexes): void
{
    $order = find_job_order($pdo, $jobOrderId, $userId);
    if (!$order) {
        http_response_code(404);
        exit('Job order not found.');
    }

    $doneIndexes = array_flip(array_map('intval', $doneIndexes));
    $tasks = normalize_checklist_tasks($order['checklist_tasks'] ?? null);
    foreach ($tasks as $index => $task) {
        $tasks[$index]['done'] = isset($doneIndexes[$index]);
    }

    $encodedTasks = $tasks ? json_encode($tasks, JSON_UNESCAPED_UNICODE) : null;
    $pdo->prepare('UPDATE job_orders SET checklist_tasks = ? WHERE id = ?')->execute([$encodedTasks, $jobOrderId]);
}

function collect_job_order_input(array $post, bool $admin): array
{
    $data = [
        'requesting_department' => trim((string) ($post['requesting_department'] ?? '')),
        'project_name' => trim((string) ($post['project_name'] ?? '')),
        'date_filed' => valid_date_or_null($post['date_filed'] ?? ''),
        'date_needed' => valid_date_or_null($post['date_needed'] ?? ''),
        'job_description' => trim((string) ($post['job_description'] ?? '')),
        'checklist_tasks' => checklist_tasks_from_texts((array) ($post['checklist_tasks'] ?? [])),
        'urgency' => in_array($post['urgency'] ?? '', ['Low', 'Medium', 'High', 'Critical'], true) ? $post['urgency'] : 'Low',
        'requested_by' => trim((string) ($post['requested_by'] ?? '')),
        'requested_by_date' => valid_date_or_null($post['requested_by_date'] ?? ''),
        'noted_by' => trim((string) ($post['noted_by'] ?? '')),
        'approved_by' => null,
        'assigned_to' => null,
        'date_received' => null,
        'date_accomplished' => null,
        'admin_comment' => null,
        'status' => 'Pending',
    ];

    if ($admin) {
        $data['approved_by'] = trim((string) ($post['approved_by'] ?? ''));
        $data['assigned_to'] = trim((string) ($post['assigned_to'] ?? ''));
        $data['date_received'] = valid_date_or_null($post['date_received'] ?? '');
        $data['date_accomplished'] = valid_date_or_null($post['date_accomplished'] ?? '');
        $data['admin_comment'] = trim((string) ($post['admin_comment'] ?? ''));
        $postedStatus = (string) ($post['status'] ?? '');
        $data['status'] = is_valid_job_order_status($postedStatus) ? canonical_job_order_status($postedStatus) : 'Pending';
    }

    return $data;
}

function validate_job_order(array $data): array
{
    $errors = [];
    foreach (['requesting_department', 'project_name', 'date_filed', 'date_needed', 'job_description'] as $field) {
        if (empty($data[$field])) {
            $errors[$field] = 'This field is required.';
        }
    }

    if (!empty($data['date_filed']) && !empty($data['date_needed']) && $data['date_needed'] < $data['date_filed']) {
        $errors['date_needed'] = 'Date needed cannot be earlier than date filed.';
    }

    return $errors;
}

function save_categories(PDO $pdo, int $jobOrderId, array $categoryIds, string $otherText): void
{
    $pdo->prepare('DELETE FROM job_order_categories WHERE job_order_id = ?')->execute([$jobOrderId]);
    $stmt = $pdo->prepare('INSERT INTO job_order_categories (job_order_id, category_id, other_category_text) VALUES (?, ?, ?)');
    $otherCategoryId = null;
    foreach (category_options($pdo) as $category) {
        if ($category['category_name'] === 'Others') {
            $otherCategoryId = (int) $category['id'];
            break;
        }
    }

    foreach ($categoryIds as $categoryId) {
        $categoryId = (int) $categoryId;
        $stmt->execute([$jobOrderId, $categoryId, $categoryId === $otherCategoryId ? trim($otherText) : null]);
    }
}

function create_job_order(PDO $pdo, int $userId, array $data, array $categoryIds, string $otherText): int
{
    $dateFiled = date('Y-m-d');
    $stmt = $pdo->prepare("INSERT INTO job_orders (
            user_id, jo_number, requesting_department, project_name, date_filed, date_needed,
            job_description, checklist_tasks, urgency, status, requested_by, requested_by_date, noted_by
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending', ?, ?, ?)");
    $stmt->execute([
        $userId,
        generate_jo_number($pdo),
        $data['requesting_department'],
        $data['project_name'],
        $dateFiled,
        $data['date_needed'],
        $data['job_description'],
        $data['checklist_tasks'],
        $data['urgency'],
        $data['requested_by'],
        $data['requested_by_date'],
        $data['noted_by'],
    ]);

    $jobOrderId = (int) $pdo->lastInsertId();
    save_categories($pdo, $jobOrderId, $categoryIds, $otherText);
    notify_job_order_created($pdo, $jobOrderId);
    return $jobOrderId;
}

function update_job_order_as_admin(PDO $pdo, int $jobOrderId, int $adminId, array $data, array $categoryIds, string $otherText): void
{
    $current = find_job_order($pdo, $jobOrderId, null);
    if (!$current) {
        http_response_code(404);
        exit('Job order not found.');
    }

    $fields = [
        'requesting_department', 'project_name', 'date_needed', 'job_description', 'checklist_tasks',
        'urgency', 'status', 'requested_by', 'requested_by_date', 'noted_by', 'approved_by',
        'assigned_to', 'date_received', 'date_accomplished', 'admin_comment',
    ];

    $sets = [];
    $params = [];
    $history = $pdo->prepare('INSERT INTO job_order_history (job_order_id, edited_by, field_changed, old_value, new_value) VALUES (?, ?, ?, ?, ?)');

    foreach ($fields as $field) {
        $sets[] = "{$field} = ?";
        $params[] = $data[$field];
        if ((string) ($current[$field] ?? '') !== (string) ($data[$field] ?? '')) {
            $history->execute([$jobOrderId, $adminId, $field, (string) ($current[$field] ?? ''), (string) ($data[$field] ?? '')]);
        }
    }

    $params[] = $jobOrderId;
    $pdo->prepare('UPDATE job_orders SET ' . implode(', ', $sets) . ' WHERE id = ?')->execute($params);
    save_categories($pdo, $jobOrderId, $categoryIds, $otherText);

    if (!empty($data['admin_comment'])) {
        $pdo->prepare('INSERT INTO job_order_comments (job_order_id, admin_id, comment) VALUES (?, ?, ?)')
            ->execute([$jobOrderId, $adminId, $data['admin_comment']]);
    }
}

function update_job_order_status(PDO $pdo, int $jobOrderId, int $adminId, string $status, string $comment = ''): void
{
    update_job_order_status_scoped($pdo, $jobOrderId, $adminId, $status, null, $comment);
}

function update_job_order_status_scoped(PDO $pdo, int $jobOrderId, int $editorId, string $status, ?int $userId = null, string $comment = ''): void
{
    if (!is_valid_job_order_status($status)) {
        http_response_code(422);
        exit('Invalid status.');
    }
    $status = canonical_job_order_status($status);

    $current = find_job_order($pdo, $jobOrderId, $userId);
    if (!$current) {
        http_response_code(404);
        exit('Job order not found.');
    }

    if ($current['status'] !== $status) {
        $pdo->prepare('UPDATE job_orders SET status = ? WHERE id = ?')->execute([$status, $jobOrderId]);
        $pdo->prepare('INSERT INTO job_order_history (job_order_id, edited_by, field_changed, old_value, new_value) VALUES (?, ?, "status", ?, ?)')
            ->execute([$jobOrderId, $editorId, $current['status'], $status]);
    }

    if ($comment !== '') {
        $pdo->prepare('UPDATE job_orders SET admin_comment = ? WHERE id = ?')->execute([$comment, $jobOrderId]);
        $pdo->prepare('INSERT INTO job_order_comments (job_order_id, admin_id, comment) VALUES (?, ?, ?)')
            ->execute([$jobOrderId, $editorId, $comment]);
    }
}
