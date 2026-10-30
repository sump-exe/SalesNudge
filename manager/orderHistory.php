<?php
session_start();

if (($_SESSION['role'] ?? '') !== 'manager') {
    header('Location: ../index.php');
    exit;
}

require_once __DIR__ . '/../_conn.php';

$error = '';
$sales = [];

try {
    $salesResult = $conn->query(
        'SELECT
            sales.`id` AS `sale_id`,
            sales.`cashier_username`,
            sales.`total`,
            sales.`amount_paid`,
            sales.`change_due`,
            sales.`created_at`,
            items.`item_name`,
            items.`quantity`,
            items.`unit_price`,
            items.`line_total`
        FROM `pos_sales` AS sales
        LEFT JOIN `pos_sale_items` AS items ON items.`sale_id` = sales.`id`
        ORDER BY sales.`created_at` DESC, sales.`id` DESC, items.`id`'
    );
    $sales = $salesResult->fetch_all(MYSQLI_ASSOC);
} catch (mysqli_sql_exception $exception) {
    error_log('Unable to load manager order history: ' . $exception->getMessage());
    $error = 'Order history is currently unavailable. Ensure the point-of-sale tables have been initialized, then try again.';
}

function orderHistoryEscape(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manager | Order History</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
</head>
<body>
    <main class="container py-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h2 mb-0">Order History</h1>
            <a href="index.php" class="btn btn-outline-secondary">Back to Manager</a>
        </div>

        <?php if ($error !== ''): ?>
            <div class="alert alert-danger" role="alert"><?= orderHistoryEscape($error) ?></div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped table-bordered align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th scope="col">Sale ID</th>
                            <th scope="col">Date</th>
                            <th scope="col">Cashier</th>
                            <th scope="col">Item</th>
                            <th scope="col">Quantity</th>
                            <th scope="col">Unit Price (PHP)</th>
                            <th scope="col">Line Total (PHP)</th>
                            <th scope="col">Sale Total (PHP)</th>
                            <th scope="col">Amount Paid (PHP)</th>
                            <th scope="col">Change (PHP)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($sales === []): ?>
                            <tr>
                                <td colspan="10" class="text-center">No orders found.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($sales as $sale): ?>
                                <tr>
                                    <td><?= (int) $sale['sale_id'] ?></td>
                                    <td><?= orderHistoryEscape($sale['created_at']) ?></td>
                                    <td><?= orderHistoryEscape($sale['cashier_username']) ?></td>
                                    <td><?= $sale['item_name'] === null ? '—' : orderHistoryEscape($sale['item_name']) ?></td>
                                    <td><?= $sale['quantity'] === null ? '—' : (int) $sale['quantity'] ?></td>
                                    <td><?= $sale['unit_price'] === null ? '—' : '₱' . orderHistoryEscape(number_format((float) $sale['unit_price'], 2)) ?></td>
                                    <td><?= $sale['line_total'] === null ? '—' : '₱' . orderHistoryEscape(number_format((float) $sale['line_total'], 2)) ?></td>
                                    <td>₱<?= orderHistoryEscape(number_format((float) $sale['total'], 2)) ?></td>
                                    <td>₱<?= orderHistoryEscape(number_format((float) $sale['amount_paid'], 2)) ?></td>
                                    <td>₱<?= orderHistoryEscape(number_format((float) $sale['change_due'], 2)) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </main>
</body>
</html>
