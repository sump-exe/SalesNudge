<?php
session_start();

if (($_SESSION['role'] ?? '') !== 'manager') {
    header('Location: ../index.php');
    exit;
}

require_once __DIR__ . '/../_conn.php';

if (empty($_SESSION['manager_csrf_token'])) {
    $_SESSION['manager_csrf_token'] = bin2hex(random_bytes(32));
}

$csrfToken = $_SESSION['manager_csrf_token'];
$error = '';
$success = $_SESSION['menu_management_success'] ?? '';
unset($_SESSION['menu_management_success']);

function managerMenuEscape(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submittedToken = $_POST['csrf_token'] ?? '';
    $action = $_POST['action'] ?? '';

    if (!is_string($submittedToken) || !hash_equals($csrfToken, $submittedToken)) {
        $error = 'Your session has expired. Refresh the page and try again.';
    } elseif ($action === 'update') {
        $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
        $nameInput = $_POST['name'] ?? null;
        $priceInput = $_POST['price'] ?? null;
        $name = is_string($nameInput) ? trim($nameInput) : '';
        $price = is_string($priceInput) ? trim($priceInput) : '';

        if ($id === false || $id < 1 || !preg_match('/^.{1,100}$/us', $name) || !preg_match('/^\d{1,8}(?:\.\d{1,2})?$/', $price)) {
            $error = 'Enter a valid item name and price.';
        } else {
            $stmt = $conn->prepare('UPDATE `menu_items` SET `name` = ?, `price` = ? WHERE `id` = ?');
            $stmt->bind_param('ssi', $name, $price, $id);
            $stmt->execute();
            $stmt->close();

            $_SESSION['menu_management_success'] = 'Menu item updated successfully.';
            header('Location: menu.php');
            exit;
        }
    } elseif ($action === 'delete') {
        $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);

        if ($id === false || $id < 1) {
            $error = 'Invalid menu item selected.';
        } else {
            $stmt = $conn->prepare('DELETE FROM `menu_items` WHERE `id` = ?');
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $deleted = $stmt->affected_rows;
            $stmt->close();

            if ($deleted === 0) {
                $error = 'Menu item was not found.';
            } else {
                $_SESSION['menu_management_success'] = 'Menu item deleted successfully.';
                header('Location: menu.php');
                exit;
            }
        }
    } else {
        $error = 'Invalid action.';
    }
}

$itemsResult = $conn->query('SELECT `id`, `name`, `price` FROM `menu_items` ORDER BY `name`');
$items = $itemsResult->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <title>Manager | Menu Items</title>
</head>
<body>
    <main class="container py-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h2 mb-0">Menu Items</h1>
            <a href="index.php" class="btn btn-outline-secondary">Back to Manager</a>
        </div>

        <?php if ($error !== ''): ?>
            <div class="alert alert-danger" role="alert"><?= managerMenuEscape($error) ?></div>
        <?php endif; ?>
        <?php if ($success !== ''): ?>
            <div class="alert alert-success" role="alert"><?= managerMenuEscape($success) ?></div>
        <?php endif; ?>

        <div class="table-responsive">
            <table class="table table-striped table-bordered align-middle">
                <thead class="table-dark">
                    <tr>
                        <th scope="col">Item Name</th>
                        <th scope="col">Price (PHP)</th>
                        <th scope="col">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($items === []): ?>
                        <tr>
                            <td colspan="3" class="text-center">No menu items found.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($items as $item): ?>
                            <tr>
                                <td><?= managerMenuEscape($item['name']) ?></td>
                                <td>₱<?= managerMenuEscape(number_format((float) $item['price'], 2)) ?></td>
                                <td>
                                    <button
                                        type="button"
                                        class="btn btn-warning btn-sm"
                                        data-bs-toggle="modal"
                                        data-bs-target="#editMenuItemModal"
                                        data-item-id="<?= (int) $item['id'] ?>"
                                        data-item-name="<?= managerMenuEscape($item['name']) ?>"
                                        data-item-price="<?= managerMenuEscape((string) $item['price']) ?>"
                                    >Edit</button>
                                    <button
                                        type="button"
                                        class="btn btn-danger btn-sm"
                                        data-bs-toggle="modal"
                                        data-bs-target="#deleteMenuItemModal"
                                        data-item-id="<?= (int) $item['id'] ?>"
                                        data-item-name="<?= managerMenuEscape($item['name']) ?>"
                                    >Delete</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>

    <div class="modal fade" id="editMenuItemModal" tabindex="-1" aria-labelledby="editMenuItemModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h2 class="modal-title fs-5" id="editMenuItemModalLabel">Edit Menu Item</h2>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="csrf_token" value="<?= managerMenuEscape($csrfToken) ?>">
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="id" id="edit-menu-item-id">
                        <div class="mb-3">
                            <label for="edit-menu-item-name" class="form-label">Item Name</label>
                            <input type="text" class="form-control" id="edit-menu-item-name" name="name" maxlength="100" required>
                        </div>
                        <div>
                            <label for="edit-menu-item-price" class="form-label">Price (PHP)</label>
                            <div class="input-group">
                                <span class="input-group-text">₱</span>
                                <input type="number" class="form-control" id="edit-menu-item-price" name="price" min="0" max="99999999.99" step="0.01" required>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="deleteMenuItemModal" tabindex="-1" aria-labelledby="deleteMenuItemModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h2 class="modal-title fs-5" id="deleteMenuItemModalLabel">Delete Menu Item</h2>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="csrf_token" value="<?= managerMenuEscape($csrfToken) ?>">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" id="delete-menu-item-id">
                        <p class="mb-0">Are you sure you want to delete <strong id="delete-menu-item-name"></strong>?</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">Delete</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
    <script>
        document.getElementById('editMenuItemModal').addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            document.getElementById('edit-menu-item-id').value = button.getAttribute('data-item-id');
            document.getElementById('edit-menu-item-name').value = button.getAttribute('data-item-name');
            document.getElementById('edit-menu-item-price').value = button.getAttribute('data-item-price');
        });

        document.getElementById('deleteMenuItemModal').addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            document.getElementById('delete-menu-item-id').value = button.getAttribute('data-item-id');
            document.getElementById('delete-menu-item-name').textContent = button.getAttribute('data-item-name');
        });
    </script>
</body>
</html>
