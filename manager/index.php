<?php
require_once __DIR__ . '/../_session.php';
requireRole('manager');

require_once __DIR__ . '/../_conn.php';

if (empty($_SESSION['manager_csrf_token'])) {
    $_SESSION['manager_csrf_token'] = bin2hex(random_bytes(32));
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submittedToken = $_POST['csrf_token'] ?? '';

    if (!is_string($submittedToken) || !hash_equals($_SESSION['manager_csrf_token'], $submittedToken)) {
        $error = 'Your session has expired. Refresh the page and try again.';
    } elseif (($_POST['action'] ?? '') === 'logout') {
        destroyAppSession();
        header('Location: ../index.php');
        exit;
    } else {
        $error = 'Invalid action.';
    }
}

$summary = null;
$topItems = [];

try {
    $summaryResult = $conn->query(
        'SELECT
            COUNT(*) AS `sale_count`,
            COALESCE(SUM(`total`), 0) AS `revenue`,
            COALESCE((
                SELECT SUM(items.`quantity`)
                FROM `pos_sale_items` AS items
                INNER JOIN `pos_sales` AS item_sales ON item_sales.`id` = items.`sale_id`
                WHERE item_sales.`created_at` >= DATE_SUB(CURRENT_TIMESTAMP, INTERVAL 31 DAY)
                    AND item_sales.`created_at` <= CURRENT_TIMESTAMP
            ), 0) AS `units_sold`
        FROM `pos_sales`
        WHERE `created_at` >= DATE_SUB(CURRENT_TIMESTAMP, INTERVAL 31 DAY)
            AND `created_at` <= CURRENT_TIMESTAMP'
    );
    $summary = $summaryResult->fetch_assoc();

    $topItemsResult = $conn->query(
        'SELECT
            items.`item_name`,
            SUM(items.`quantity`) AS `quantity_sold`,
            SUM(items.`line_total`) AS `income`
        FROM `pos_sale_items` AS items
        INNER JOIN `pos_sales` AS sales ON sales.`id` = items.`sale_id`
        WHERE sales.`created_at` >= DATE_SUB(CURRENT_TIMESTAMP, INTERVAL 31 DAY)
            AND sales.`created_at` <= CURRENT_TIMESTAMP
        GROUP BY items.`menu_item_id`, items.`item_name`
        ORDER BY `income` DESC, `quantity_sold` DESC, items.`item_name` ASC
        LIMIT 10'
    );
    $topItems = $topItemsResult->fetch_all(MYSQLI_ASSOC);
} catch (mysqli_sql_exception $exception) {
    error_log('Unable to load manager sales summary: ' . $exception->getMessage());
    $error = 'Sales data is currently unavailable. Ensure the point-of-sale tables have been initialized, then try again.';
}

$username = htmlspecialchars((string) ($_SESSION['username'] ?? ''), ENT_QUOTES, 'UTF-8');
$csrfToken = $_SESSION['manager_csrf_token'];

function managerDashboardEscape(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="idle-csrf-token" content="<?= managerDashboardEscape($_SESSION['idle_csrf_token']) ?>">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link rel="stylesheet" href="../styles.css">
    <script src="../_idle_timeout.js" defer></script>
    <title>Manager | Sales Summary</title>
</head>
<body>
    <?php $navActive = 'dashboard'; require __DIR__ . '/_nav.php'; ?>
    <main class="container py-5">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
            <h1 class="h2 mb-0">Sales Summary</h1>
        </div>

        <p class="text-secondary mb-4">Sales and item income from the past 31 days.</p>

        <?php if ($error !== ''): ?>
            <div class="alert alert-danger" role="alert"><?= managerDashboardEscape($error) ?></div>
        <?php else: ?>
            <div class="row g-3 mb-4">
                <div class="col-12 col-md-4">
                    <section class="card h-100">
                        <div class="card-body">
                            <h2 class="h6 text-secondary">Total Revenue</h2>
                            <p class="h3 mb-0">₱<?= managerDashboardEscape(number_format((float) $summary['revenue'], 2)) ?></p>
                        </div>
                    </section>
                </div>
                <div class="col-12 col-md-4">
                    <section class="card h-100">
                        <div class="card-body">
                            <h2 class="h6 text-secondary">Completed Sales</h2>
                            <p class="h3 mb-0"><?= managerDashboardEscape(number_format((int) $summary['sale_count'])) ?></p>
                        </div>
                    </section>
                </div>
                <div class="col-12 col-md-4">
                    <section class="card h-100">
                        <div class="card-body">
                            <h2 class="h6 text-secondary">Items Sold</h2>
                            <p class="h3 mb-0"><?= managerDashboardEscape(number_format((int) $summary['units_sold'])) ?></p>
                        </div>
                    </section>
                </div>
            </div>

            <section aria-labelledby="top-items-heading">
                <h2 id="top-items-heading" class="h4 mb-3">Top Items by Income</h2>
                <div class="table-responsive">
                    <table class="table table-striped table-bordered align-middle">
                        <thead class="table-dark">
                            <tr>
                                <th scope="col">Item</th>
                                <th scope="col">Quantity Sold</th>
                                <th scope="col">Income (PHP)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($topItems === []): ?>
                                <tr>
                                    <td colspan="3" class="text-center">No sales in the past 31 days.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($topItems as $item): ?>
                                    <tr>
                                        <td><?= managerDashboardEscape($item['item_name']) ?></td>
                                        <td><?= managerDashboardEscape(number_format((int) $item['quantity_sold'])) ?></td>
                                        <td>₱<?= managerDashboardEscape(number_format((float) $item['income'], 2)) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        <?php endif; ?>
    </main>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
</body>
</html>
