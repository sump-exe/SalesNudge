<?php
session_start();

if (($_SESSION['role'] ?? '') !== 'manager') {
    header('Location: ../index.php');
    exit;
}

if (empty($_SESSION['manager_csrf_token'])) {
    $_SESSION['manager_csrf_token'] = bin2hex(random_bytes(32));
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submittedToken = $_POST['csrf_token'] ?? '';

    if (!is_string($submittedToken) || !hash_equals($_SESSION['manager_csrf_token'], $submittedToken)) {
        $error = 'Your session has expired. Refresh the page and try again.';
    } elseif (($_POST['action'] ?? '') === 'logout') {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $cookie = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $cookie['path'], $cookie['domain'], $cookie['secure'], $cookie['httponly']);
        }
        session_destroy();
        header('Location: ../index.php');
        exit;
    } else {
        $error = 'Invalid action.';
    }
}

$username = htmlspecialchars((string) ($_SESSION['username'] ?? ''), ENT_QUOTES, 'UTF-8');
$csrfToken = $_SESSION['manager_csrf_token'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <title>Manager</title>
</head>
<body>
    <main class="container py-4">
        <div class="d-flex justify-content-between align-items-center">
            <h1 class="h3 mb-0">Logged in as MANAGER<?= $username !== '' ? ': ' . $username : '' ?></h1>
            <form method="POST" class="m-0">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="action" value="logout">
                <button type="submit" class="btn btn-outline-danger">Log out</button>
            </form>
        </div>
        <?php if ($error !== ''): ?>
            <div class="alert alert-danger mt-3" role="alert"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>
    </main>
</body>
</html>
