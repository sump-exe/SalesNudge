<?php
session_start();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once __DIR__ . '/_conn.php';

    $username = trim($_POST['username'] ?? '');
    $submittedPassword = $_POST['password'] ?? '';

    if ($username !== '' && $submittedPassword !== '') {
        $stmt = $conn->prepare('SELECT `password`, `role` FROM `users` WHERE `username` = ? LIMIT 1');
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $stmt->bind_result($storedPassword, $role);

        if ($stmt->fetch()) {
            $stmt->close();

            $passwordInfo = password_get_info($storedPassword);
            $isPasswordHash = $passwordInfo['algo'] !== null;
            $passwordMatches = $isPasswordHash
                ? password_verify($submittedPassword, $storedPassword)
                : hash_equals($storedPassword, $submittedPassword);

            if ($passwordMatches) {
                $role = strtolower(trim($role));
                $rolePages = [
                    'admin' => 'admin/index.php',
                    'manager' => 'manager/index.php',
                    'cashier' => 'cashier/index.php',
                ];

                if (isset($rolePages[$role])) {
                    if (!$isPasswordHash) {
                        $hashedPassword = password_hash($submittedPassword, PASSWORD_DEFAULT);
                        $update = $conn->prepare('UPDATE `users` SET `password` = ? WHERE `username` = ?');
                        $update->bind_param('ss', $hashedPassword, $username);
                        $update->execute();
                        $update->close();
                    }

                    session_regenerate_id(true);
                    $_SESSION['username'] = $username;
                    $_SESSION['role'] = $role;

                    header('Location: ' . $rolePages[$role]);
                    exit;
                }

                $error = 'This account does not have an allowed role.';
            } else {
                $error = 'Invalid username or password.';
            }
        } else {
            $stmt->close();
            $error = 'Invalid username or password.';
        }
    } else {
        $error = 'Enter both your username and password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link rel="stylesheet" href="style.css">
    <title>Login</title>
</head>
<body>
    <div class="log-container d-flex justify-content-center align-items-center vh-100">
        <div class="card p-4" style="width: 400px;">
            <h2 class="text-center mb-4">Login</h2>
            <form method="POST">
                <?php if ($error !== ''): ?>
                    <div class="alert alert-danger" role="alert">
                        <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
                    </div>
                <?php endif; ?>
                <div class="mb-3">
                    <label for="username" class="form-label">Username</label>
                    <input type="text" class="form-control" id="username" name="username" required>
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>
                <button type="submit" class="btn btn-success w-100">Login</button>
            </form>
        </div>
</body>
</html>