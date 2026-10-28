<?php
session_start();

if (($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: ../index.php');
    exit;
}

require_once __DIR__ . '/../_conn.php';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$error = '';
$success = $_SESSION['user_management_success'] ?? '';
unset($_SESSION['user_management_success']);
$csrfToken = $_SESSION['csrf_token'];
$allowedRoles = ['admin', 'manager', 'cashier'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submittedToken = $_POST['csrf_token'] ?? '';
    $action = $_POST['action'] ?? '';

    if (!is_string($submittedToken) || !hash_equals($csrfToken, $submittedToken)) {
        $error = 'Your session has expired. Refresh the page and try again.';
    } elseif ($action === 'create') {
        $usernameInput = $_POST['username'] ?? null;
        $passwordInput = $_POST['password'] ?? null;
        $roleInput = $_POST['role'] ?? null;
        $username = is_string($usernameInput) ? trim($usernameInput) : '';
        $password = is_string($passwordInput) ? $passwordInput : '';
        $role = is_string($roleInput) ? $roleInput : '';

        if ($username === '' || $password === '' || !in_array($role, $allowedRoles, true)) {
            $error = 'Enter a username, password, and valid role.';
        } else {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare('INSERT INTO `users` (`username`, `password`, `role`) VALUES (?, ?, ?)');
            $stmt->bind_param('sss', $username, $hashedPassword, $role);
            $stmt->execute();
            $stmt->close();

            $_SESSION['user_management_success'] = 'User created successfully.';
            header('Location: index.php');
            exit;
        }
    } elseif ($action === 'update') {
        $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
        $usernameInput = $_POST['username'] ?? null;
        $passwordInput = $_POST['password'] ?? null;
        $roleInput = $_POST['role'] ?? null;
        $username = is_string($usernameInput) ? trim($usernameInput) : '';
        $password = is_string($passwordInput) ? $passwordInput : '';
        $role = is_string($roleInput) ? $roleInput : '';

        if (!$id || $username === '' || !in_array($role, $allowedRoles, true)) {
            $error = 'Enter a username and valid role.';
        } elseif ($password !== '') {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare('UPDATE `users` SET `username` = ?, `password` = ?, `role` = ? WHERE `id` = ?');
            $stmt->bind_param('sssi', $username, $hashedPassword, $role, $id);
            $stmt->execute();
            $stmt->close();

            $_SESSION['user_management_success'] = 'User updated successfully.';
            header('Location: index.php');
            exit;
        } else {
            $stmt = $conn->prepare('UPDATE `users` SET `username` = ?, `role` = ? WHERE `id` = ?');
            $stmt->bind_param('ssi', $username, $role, $id);
            $stmt->execute();
            $stmt->close();

            $_SESSION['user_management_success'] = 'User updated successfully.';
            header('Location: index.php');
            exit;
        }
    } elseif ($action === 'delete') {
        $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);

        if (!$id) {
            $error = 'Invalid user selected.';
        } else {
            $currentUsername = $_SESSION['username'] ?? '';
            $stmt = $conn->prepare('DELETE FROM `users` WHERE `id` = ? AND `username` <> ?');
            $stmt->bind_param('is', $id, $currentUsername);
            $stmt->execute();
            $deleted = $stmt->affected_rows;
            $stmt->close();

            if ($deleted === 0) {
                $error = 'User was not found or you cannot delete the account you are currently using.';
            } else {
                $_SESSION['user_management_success'] = 'User deleted successfully.';
                header('Location: index.php');
                exit;
            }
        }
    } else {
        $error = 'Invalid action.';
    }
}

$usersResult = $conn->query('SELECT `id`, `username`, `role`, `created_at` FROM `users` ORDER BY `id`');
$users = $usersResult->fetch_all(MYSQLI_ASSOC);

function escapeHtml(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <title>Admin - User Management</title>
</head>
<body>
    <main class="container py-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h2 mb-0">User Management</h1>
            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addUserModal">
                Add New User
            </button>
        </div>

        <?php if ($error !== ''): ?>
            <div class="alert alert-danger" role="alert"><?= escapeHtml($error) ?></div>
        <?php endif; ?>
        <?php if ($success !== ''): ?>
            <div class="alert alert-success" role="alert"><?= escapeHtml($success) ?></div>
        <?php endif; ?>

        <div class="table-responsive">
            <table class="table table-striped table-bordered align-middle">
                <thead class="table-dark">
                    <tr>
                        <th scope="col">ID</th>
                        <th scope="col">Username</th>
                        <th scope="col">Password</th>
                        <th scope="col">Role</th>
                        <th scope="col">Created At</th>
                        <th scope="col">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($users === []): ?>
                        <tr>
                            <td colspan="6" class="text-center">No users found.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?= (int) $user['id'] ?></td>
                                <td><?= escapeHtml($user['username']) ?></td>
                                <td>********</td>
                                <td><?= escapeHtml($user['role']) ?></td>
                                <td><?= escapeHtml($user['created_at']) ?></td>
                                <td>
                                    <button
                                        type="button"
                                        class="btn btn-warning btn-sm"
                                        data-bs-toggle="modal"
                                        data-bs-target="#editUserModal"
                                        data-user-id="<?= (int) $user['id'] ?>"
                                        data-username="<?= escapeHtml($user['username']) ?>"
                                        data-role="<?= escapeHtml($user['role']) ?>"
                                    >Edit</button>
                                    <button
                                        type="button"
                                        class="btn btn-danger btn-sm"
                                        data-bs-toggle="modal"
                                        data-bs-target="#deleteUserModal"
                                        data-user-id="<?= (int) $user['id'] ?>"
                                        data-username="<?= escapeHtml($user['username']) ?>"
                                    >Delete</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>

    <?php require __DIR__ . '/addUser.php'; ?>
    <?php require __DIR__ . '/editUser.php'; ?>
    <?php require __DIR__ . '/deleteUser.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
    <script>
        document.getElementById('editUserModal').addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            document.getElementById('edit-user-id').value = button.getAttribute('data-user-id');
            document.getElementById('edit-username').value = button.getAttribute('data-username');
            document.getElementById('edit-role').value = button.getAttribute('data-role');
            document.getElementById('edit-password').value = '';
        });
        document.getElementById('deleteUserModal').addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            document.getElementById('delete-user-id').value = button.getAttribute('data-user-id');
            document.getElementById('delete-user-name').textContent = button.getAttribute('data-username');
        });
    </script>
</body>
</html>
