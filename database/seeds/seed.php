<?php

declare(strict_types=1);

$dbPath = dirname(__DIR__) . '/database.sqlite';
$pdo    = new PDO('sqlite:' . $dbPath, options: [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

// Пользователи
$users = [
    ['Иван Диспетчеров', 'dispatcher1', password_hash('dispatcher1', PASSWORD_BCRYPT), 'dispatcher'],
    ['Алексей Мастеров',  'master1',     password_hash('master1',     PASSWORD_BCRYPT), 'master'],
    ['Сергей Ремонтов',   'master2',     password_hash('master2',     PASSWORD_BCRYPT), 'master'],
];

$stmt = $pdo->prepare(
    'INSERT OR IGNORE INTO users (name, username, password_hash, role) VALUES (?, ?, ?, ?)'
);
foreach ($users as $user) {
    $stmt->execute($user);
}

$master1Id     = (int) $pdo->query("SELECT id FROM users WHERE username = 'master1'")->fetchColumn();
$master2Id     = (int) $pdo->query("SELECT id FROM users WHERE username = 'master2'")->fetchColumn();
$dispatcherId  = (int) $pdo->query("SELECT id FROM users WHERE username = 'dispatcher1'")->fetchColumn();

// Тестовые заявки
$requests = [
    ['Петров Иван',     '+7 900 123-45-67', 'ул. Ленина, 10, кв. 5',   'Течёт кран на кухне',       'new',         null],
    ['Сидорова Мария',  '+7 911 234-56-78', 'пр. Мира, 25, кв. 12',    'Не работает розетка',       'assigned',    $master1Id],
    ['Козлов Дмитрий',  '+7 922 345-67-89', 'ул. Гагарина, 5, кв. 3',  'Засорилась канализация',    'in_progress', $master2Id],
    ['Новикова Анна',   '+7 933 456-78-90', 'ул. Советская, 1, кв. 8', 'Нет горячей воды',          'done',        $master1Id],
    ['Морозов Виктор',  '+7 944 567-89-01', 'пр. Победы, 15, кв. 2',   'Сломан замок входной двери','canceled',    null],
    ['Волкова Елена',   '+7 955 678-90-12', 'ул. Пушкина, 7, кв. 14',  'Не включается свет в ванной','new',        null],
];

$stmt = $pdo->prepare(
    'INSERT OR IGNORE INTO service_requests (client_name, phone, address, problem_text, status, assigned_to)
     VALUES (?, ?, ?, ?, ?, ?)'
);
foreach ($requests as $req) {
    $stmt->execute($req);
}

// Audit log для существующих заявок
$reqIds = $pdo->query('SELECT id, status, assigned_to FROM service_requests')->fetchAll(PDO::FETCH_ASSOC);
$auditStmt = $pdo->prepare(
    "INSERT OR IGNORE INTO audit_log (request_id, user_id, action, old_status, new_status, comment, created_at)
     VALUES (?, ?, ?, ?, ?, ?, datetime('now', '-' || ? || ' minutes'))"
);
foreach ($reqIds as $i => $row) {
    $auditStmt->execute([$row['id'], $dispatcherId, 'created', null, 'new', null, (count($reqIds) - $i) * 10]);
    if ($row['status'] === 'assigned' || $row['status'] === 'in_progress' || $row['status'] === 'done') {
        $auditStmt->execute([$row['id'], $dispatcherId, 'assigned', 'new', 'assigned', 'Назначен мастер', (count($reqIds) - $i) * 8]);
    }
    if ($row['status'] === 'in_progress' || $row['status'] === 'done') {
        $auditStmt->execute([$row['id'], $row['assigned_to'], 'taken_in_progress', 'assigned', 'in_progress', null, (count($reqIds) - $i) * 5]);
    }
    if ($row['status'] === 'done') {
        $auditStmt->execute([$row['id'], $row['assigned_to'], 'completed', 'in_progress', 'done', null, (count($reqIds) - $i) * 2]);
    }
    if ($row['status'] === 'canceled') {
        $auditStmt->execute([$row['id'], $dispatcherId, 'canceled', 'new', 'canceled', null, (count($reqIds) - $i) * 3]);
    }
}

echo "Сиды выполнены успешно.\n";
echo "Тестовые пользователи:\n";
echo "  dispatcher1 / dispatcher1\n";
echo "  master1     / master1\n";
echo "  master2     / master2\n";
