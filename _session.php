<?php
date_default_timezone_set('Asia/Manila');

// Simple session handling:
// - auto log out if wala nmn activity na ginagawa si manager at cashier

require_once __DIR__ . '/_conn.php';

const IDLE_LIMIT = 300; // 5 minutes (admin is not limited)

const ROLE_PAGES = [
    'admin'   => 'admin/index.php',
    'manager' => 'manager/index.php',
    'cashier' => 'cashier/index.php',
];

function startAppSession(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
        // Back button must not show a cached dashboard after logout
        header('Cache-Control: no-store, no-cache, must-revalidate');
    }
}

function isLoggedIn(): bool
{
    return isset($_SESSION['username'], $_SESSION['role']) && isset(ROLE_PAGES[$_SESSION['role']]);
}

function isIdleTooLong(): bool
{
    return $_SESSION['role'] !== 'admin'
        && time() - ($_SESSION['last_activity'] ?? time()) > IDLE_LIMIT;
}

function logSessionStart(string $username, string $role): int
{
    global $conn;
    $conn->query('CREATE TABLE IF NOT EXISTS `Session` (
        `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
        `username` VARCHAR(50) NOT NULL,
        `role` VARCHAR(20) NOT NULL,
        `timein` DATETIME NOT NULL,
        `timeout` DATETIME NULL DEFAULT NULL,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');

    $now = date('Y-m-d H:i:s');
    $stmt = $conn->prepare('INSERT INTO `Session` (`username`, `role`, `timein`) VALUES (?, ?, ?)');
    $stmt->bind_param('sss', $username, $role, $now);
    $stmt->execute();
    $stmt->close();
    return $conn->insert_id;
}

// Save logout time, then clear the session
function destroyAppSession(?int $endedAt = null): void
{
    global $conn;
    if (isset($_SESSION['session_id'])) {
        $out = date('Y-m-d H:i:s', $endedAt ?? time());
        $stmt = $conn->prepare('UPDATE `Session` SET `timeout` = ? WHERE `id` = ?');
        $stmt->bind_param('si', $out, $_SESSION['session_id']);
        $stmt->execute();
        $stmt->close();
    }
    $_SESSION = [];
    session_destroy();
}

function requireRole(string $role): void
{
    startAppSession();

    if (!isLoggedIn()) {
        header('Location: ../index.php');
        exit;
    }
    if (isIdleTooLong()) {
        destroyAppSession($_SESSION['last_activity']);
        header('Location: ../index.php?expired=1');
        exit;
    }
    if ($_SESSION['role'] !== $role) { // wrong role -> own dashboard
        header('Location: ../' . ROLE_PAGES[$_SESSION['role']]);
        exit;
    }

    if (in_array($role, ['manager', 'cashier'], true) && empty($_SESSION['idle_csrf_token'])) {
        $_SESSION['idle_csrf_token'] = bin2hex(random_bytes(32));
    }

    $_SESSION['last_activity'] = time();
}

function redirectIfLoggedIn(): void
{
    if (!isLoggedIn()) {
        return;
    }
    if (isIdleTooLong()) {
        destroyAppSession($_SESSION['last_activity']);
        startAppSession();
        return;
    }
    header('Location: ' . ROLE_PAGES[$_SESSION['role']]);
    exit;
}