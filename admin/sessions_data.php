<?php
require_once __DIR__ . '/../_session.php';

startAppSession();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['status' => 'expired']);
    exit;
}

if ($_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['status' => 'forbidden']);
    exit;
}

try {
    $result = $conn->query(
        'SELECT `id`, `username`, `role`, `timein`, `timeout`
        FROM `Session`
        ORDER BY `timein` DESC, `id` DESC
        LIMIT 200'
    );

    $sessions = [];
    $now = time();
    while ($row = $result->fetch_assoc()) {
        $timeIn = strtotime($row['timein']);
        $timeOut = $row['timeout'] === null ? null : strtotime($row['timeout']);
        $endTime = $timeOut ?? $now;

        $sessions[] = [
            'id' => (int) $row['id'],
            'username' => $row['username'],
            'role' => $row['role'],
            'timein' => $row['timein'],
            'timeout' => $row['timeout'],
            'duration_seconds' => max(0, $endTime - $timeIn),
            'auto_logout_seconds' => $row['role'] === 'admin' ? null : IDLE_LIMIT,
            'state' => $timeOut === null ? 'active' : 'ended',
        ];
    }

    $activeResult = $conn->query('SELECT COUNT(*) AS `active_count` FROM `Session` WHERE `timeout` IS NULL');
    $activeCount = (int) $activeResult->fetch_assoc()['active_count'];

    echo json_encode([
        'status' => 'ok',
        'sessions' => $sessions,
        'active_count' => $activeCount,
        'generated_at' => date('Y-m-d H:i:s'),
    ]);
} catch (mysqli_sql_exception $error) {
    error_log('Unable to load admin sessions: ' . $error->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 'error']);
}
