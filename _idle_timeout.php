<?php
require_once __DIR__ . '/_session.php';

startAppSession();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'invalid_request']);
    exit;
}

if (
    !isLoggedIn()
    || !in_array($_SESSION['role'], ['manager', 'cashier'], true)
    || !isset($_SESSION['idle_csrf_token'])
) {
    http_response_code(401);
    echo json_encode(['status' => 'expired']);
    exit;
}

$submittedToken = $_POST['csrf_token'] ?? '';
if (!is_string($submittedToken) || !hash_equals($_SESSION['idle_csrf_token'], $submittedToken)) {
    http_response_code(403);
    echo json_encode(['status' => 'invalid_token']);
    exit;
}

if (isIdleTooLong()) {
    destroyAppSession($_SESSION['last_activity']);
    http_response_code(401);
    echo json_encode(['status' => 'expired']);
    exit;
}

$action = $_POST['action'] ?? '';
if ($action === 'activity') {
    $_SESSION['last_activity'] = time();
    echo json_encode(['status' => 'active']);
    exit;
}

if ($action === 'logout') {
    destroyAppSession();
    echo json_encode(['status' => 'logged_out']);
    exit;
}

http_response_code(400);
echo json_encode(['status' => 'invalid_action']);
