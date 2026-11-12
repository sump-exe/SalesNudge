<?php
require_once __DIR__ . '/../_session.php';
requireRole('manager');

require_once __DIR__ . '/../_conn.php';

if (empty($_SESSION['manager_csrf_token'])) {
    $_SESSION['manager_csrf_token'] = bin2hex(random_bytes(32));
}

$periodOptions = [
    '7' => 'Last 7 days',
    '30' => 'Last 30 days',
    '90' => 'Last 90 days',
];
$requestedPeriod = $_GET['period'] ?? '30';
$period = is_string($requestedPeriod) && isset($periodOptions[$requestedPeriod]) ? $requestedPeriod : '30';
$periodDays = (int) $period;
$periodDaysMinusOne = $periodDays - 1;
$previousPeriodStart = ($periodDays * 2) - 1;

$error = '';
$summary = null;
$dailySales = [];
$topItems = [];
$cashierSales = [];
$forecastByWeekday = [];
$latestForecastSaleDate = null;

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

if ($error === '') {
    $currentDateFilter = "`created_at` >= DATE_SUB(CURRENT_DATE, INTERVAL {$periodDaysMinusOne} DAY)
        AND `created_at` < DATE_ADD(CURRENT_DATE, INTERVAL 1 DAY)";

    try {
        $summaryResult = $conn->query(
            "SELECT
                COUNT(*) AS `sale_count`,
                COALESCE(SUM(`total`), 0) AS `revenue`,
                COALESCE((
                    SELECT SUM(items.`quantity`)
                    FROM `pos_sale_items` AS items
                    INNER JOIN `pos_sales` AS item_sales ON item_sales.`id` = items.`sale_id`
                    WHERE item_sales.`created_at` >= DATE_SUB(CURRENT_DATE, INTERVAL {$periodDaysMinusOne} DAY)
                        AND item_sales.`created_at` < DATE_ADD(CURRENT_DATE, INTERVAL 1 DAY)
                ), 0) AS `units_sold`,
                COALESCE((
                    SELECT SUM(previous_sales.`total`)
                    FROM `pos_sales` AS previous_sales
                    WHERE previous_sales.`created_at` >= DATE_SUB(CURRENT_DATE, INTERVAL {$previousPeriodStart} DAY)
                        AND previous_sales.`created_at` < DATE_SUB(CURRENT_DATE, INTERVAL {$periodDaysMinusOne} DAY)
                ), 0) AS `previous_revenue`
            FROM `pos_sales`
            WHERE {$currentDateFilter}"
        );
        $summary = $summaryResult->fetch_assoc();

        $dailySalesResult = $conn->query(
            "SELECT
                DATE(`created_at`) AS `sales_date`,
                COUNT(*) AS `sale_count`,
                COALESCE(SUM(`total`), 0) AS `revenue`
            FROM `pos_sales`
            WHERE {$currentDateFilter}
            GROUP BY DATE(`created_at`)
            ORDER BY `sales_date`"
        );
        foreach ($dailySalesResult->fetch_all(MYSQLI_ASSOC) as $day) {
            $dailySales[$day['sales_date']] = $day;
        }

        $topItemsResult = $conn->query(
            "SELECT
                items.`item_name`,
                SUM(items.`quantity`) AS `quantity_sold`,
                SUM(items.`line_total`) AS `income`
            FROM `pos_sale_items` AS items
            INNER JOIN `pos_sales` AS sales ON sales.`id` = items.`sale_id`
            WHERE sales.`created_at` >= DATE_SUB(CURRENT_DATE, INTERVAL {$periodDaysMinusOne} DAY)
                AND sales.`created_at` < DATE_ADD(CURRENT_DATE, INTERVAL 1 DAY)
            GROUP BY items.`menu_item_id`, items.`item_name`
            ORDER BY `income` DESC, `quantity_sold` DESC, items.`item_name` ASC
            LIMIT 10"
        );
        $topItems = $topItemsResult->fetch_all(MYSQLI_ASSOC);

        $cashierSalesResult = $conn->query(
            "SELECT
                `cashier_username`,
                COUNT(*) AS `sale_count`,
                SUM(`total`) AS `revenue`
            FROM `pos_sales`
            WHERE {$currentDateFilter}
            GROUP BY `cashier_username`
            ORDER BY `revenue` DESC, `sale_count` DESC, `cashier_username` ASC
            LIMIT 10"
        );
        $cashierSales = $cashierSalesResult->fetch_all(MYSQLI_ASSOC);

        $forecastHistoryResult = $conn->query(
            'SELECT
                DAYOFWEEK(`created_at`) AS `weekday_number`,
                COALESCE(SUM(`total`), 0) AS `revenue`,
                COUNT(*) AS `sale_count`,
                MAX(DATE(`created_at`)) AS `latest_sale_date`
            FROM `pos_sales`
            WHERE `created_at` >= DATE_SUB(CURRENT_DATE, INTERVAL 56 DAY)
                AND `created_at` < CURRENT_DATE
            GROUP BY DAYOFWEEK(`created_at`)'
        );
        foreach ($forecastHistoryResult->fetch_all(MYSQLI_ASSOC) as $weekdaySales) {
            $weekdayNumber = (int) $weekdaySales['weekday_number'];
            $forecastByWeekday[$weekdayNumber] = [
                'revenue' => (float) $weekdaySales['revenue'] / 8,
                'sale_count' => (float) $weekdaySales['sale_count'] / 8,
            ];
            if ($latestForecastSaleDate === null || $weekdaySales['latest_sale_date'] > $latestForecastSaleDate) {
                $latestForecastSaleDate = $weekdaySales['latest_sale_date'];
            }
        }
    } catch (mysqli_sql_exception $exception) {
        error_log('Unable to load manager sales analytics: ' . $exception->getMessage());
        $error = 'Sales analytics are currently unavailable. Ensure the point-of-sale tables have been initialized, then try again.';
    }
}

$today = new DateTimeImmutable('today', new DateTimeZone('Asia/Manila'));
$trend = [];
$bestDay = null;
$maxRevenue = 0.0;
for ($daysAgo = $periodDaysMinusOne; $daysAgo >= 0; $daysAgo--) {
    $day = $today->modify("-{$daysAgo} days");
    $dateKey = $day->format('Y-m-d');
    $revenue = isset($dailySales[$dateKey]) ? (float) $dailySales[$dateKey]['revenue'] : 0.0;
    $trend[] = [
        'date' => $day,
        'revenue' => $revenue,
        'sale_count' => isset($dailySales[$dateKey]) ? (int) $dailySales[$dateKey]['sale_count'] : 0,
    ];

    if ($revenue > $maxRevenue) {
        $maxRevenue = $revenue;
        $bestDay = $day;
    }
}

$chartScale = max($maxRevenue, 1.0);
$chartLabelInterval = $periodDays <= 7 ? 1 : ($periodDays <= 30 ? 5 : 14);
$forecast = [];
$forecastRevenue = 0.0;
$forecastSaleCount = 0.0;
for ($dayOffset = 1; $dayOffset <= 7; $dayOffset++) {
    $forecastDate = $today->modify("+{$dayOffset} days");
    $weekdayNumber = (int) $forecastDate->format('w') + 1;
    $weekdayForecast = $forecastByWeekday[$weekdayNumber] ?? ['revenue' => 0.0, 'sale_count' => 0.0];
    $forecast[] = [
        'date' => $forecastDate,
        'revenue' => $weekdayForecast['revenue'],
        'sale_count' => $weekdayForecast['sale_count'],
    ];
    $forecastRevenue += $weekdayForecast['revenue'];
    $forecastSaleCount += $weekdayForecast['sale_count'];
}

$forecastHistoryStale = false;
if ($latestForecastSaleDate !== null) {
    $latestForecastDate = new DateTimeImmutable($latestForecastSaleDate, new DateTimeZone('Asia/Manila'));
    $forecastHistoryStale = (int) $latestForecastDate->diff($today)->days > 7;
}

$revenue = (float) ($summary['revenue'] ?? 0);
$saleCount = (int) ($summary['sale_count'] ?? 0);
$unitsSold = (int) ($summary['units_sold'] ?? 0);
$previousRevenue = (float) ($summary['previous_revenue'] ?? 0);
$averageSale = $saleCount > 0 ? $revenue / $saleCount : 0;

if ($previousRevenue > 0) {
    $revenueChange = (($revenue - $previousRevenue) / $previousRevenue) * 100;
    if ($revenueChange > 0) {
        $comparisonText = number_format($revenueChange, 1) . '% higher than the previous ' . $periodDays . ' days';
    } elseif ($revenueChange < 0) {
        $comparisonText = number_format(abs($revenueChange), 1) . '% lower than the previous ' . $periodDays . ' days';
    } else {
        $comparisonText = 'Same as the previous ' . $periodDays . ' days';
    }
} elseif ($revenue > 0) {
    $comparisonText = 'Sales recorded; none in the previous ' . $periodDays . ' days';
} else {
    $comparisonText = 'No revenue in either period';
}

function analyticsEscape(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function analyticsMoney($value): string
{
    return number_format((float) $value, 2);
}

$csrfToken = $_SESSION['manager_csrf_token'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="idle-csrf-token" content="<?= analyticsEscape((string) $_SESSION['idle_csrf_token']) ?>">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link rel="stylesheet" href="../styles.css">
    <script src="../_idle_timeout.js" defer></script>
    <title>Manager | Sales Analytics</title>
</head>
<body>
    <main class="container py-5">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
            <div>
                <a href="index.php" class="link-secondary text-decoration-none">&larr; Sales Summary</a>
                <h1 class="h2 mt-2 mb-0">Sales Analytics</h1>
            </div>
            <div class="d-flex flex-wrap align-items-center gap-2">
                <a href="menu.php" class="btn btn-outline-secondary">Menu Items</a>
                <a href="orderHistory.php" class="btn btn-outline-secondary">Order History</a>
                <form method="POST" class="m-0">
                    <input type="hidden" name="csrf_token" value="<?= analyticsEscape($csrfToken) ?>">
                    <input type="hidden" name="action" value="logout">
                    <button type="submit" class="btn btn-outline-danger">Log out</button>
                </form>
            </div>
        </div>

        <section class="card mb-4" aria-label="Analytics reporting period">
            <div class="card-body">
                <form method="GET" class="row g-2 align-items-end">
                    <div class="col-12 col-sm-6 col-md-4">
                        <label for="analytics-period" class="form-label">Reporting period</label>
                        <select id="analytics-period" name="period" class="form-select">
                            <?php foreach ($periodOptions as $days => $label): ?>
                                <option value="<?= analyticsEscape($days) ?>"<?= $period === $days ? ' selected' : '' ?>><?= analyticsEscape($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-auto">
                        <button type="submit" class="btn btn-primary">Update report</button>
                    </div>
                    <p class="col-12 mb-0 text-secondary small">
                        <?= analyticsEscape($today->modify("-{$periodDaysMinusOne} days")->format('M j, Y')) ?>
                        through <?= analyticsEscape($today->format('M j, Y')) ?>
                    </p>
                </form>
            </div>
        </section>

        <?php if ($error !== ''): ?>
            <div class="alert alert-danger" role="alert"><?= analyticsEscape($error) ?></div>
        <?php else: ?>
            <div class="row g-3 mb-4">
                <div class="col-12 col-sm-6 col-xl-3">
                    <section class="card h-100">
                        <div class="card-body">
                            <h2 class="h6 text-secondary">Total Revenue</h2>
                            <p class="h3 mb-1">₱<?= analyticsMoney($revenue) ?></p>
                            <p class="small text-secondary mb-0"><?= analyticsEscape($comparisonText) ?></p>
                        </div>
                    </section>
                </div>
                <div class="col-12 col-sm-6 col-xl-3">
                    <section class="card h-100">
                        <div class="card-body">
                            <h2 class="h6 text-secondary">Completed Sales</h2>
                            <p class="h3 mb-0"><?= number_format($saleCount) ?></p>
                        </div>
                    </section>
                </div>
                <div class="col-12 col-sm-6 col-xl-3">
                    <section class="card h-100">
                        <div class="card-body">
                            <h2 class="h6 text-secondary">Average Sale</h2>
                            <p class="h3 mb-0">₱<?= analyticsMoney($averageSale) ?></p>
                        </div>
                    </section>
                </div>
                <div class="col-12 col-sm-6 col-xl-3">
                    <section class="card h-100">
                        <div class="card-body">
                            <h2 class="h6 text-secondary">Items Sold</h2>
                            <p class="h3 mb-0"><?= number_format($unitsSold) ?></p>
                        </div>
                    </section>
                </div>
            </div>

            <section class="card mb-4" aria-labelledby="sales-forecast-heading">
                <div class="card-body">
                    <div class="d-flex flex-wrap justify-content-between align-items-baseline gap-2 mb-3">
                        <div>
                            <h2 id="sales-forecast-heading" class="h4 mb-1">7-Day Sales Forecast</h2>
                            <p class="text-secondary mb-0">Estimates use the average for each weekday over the previous 8 complete weeks.</p>
                        </div>
                        <?php if ($latestForecastSaleDate !== null): ?>
                            <span class="small text-secondary">Latest sale in baseline: <?= analyticsEscape((new DateTimeImmutable($latestForecastSaleDate))->format('M j, Y')) ?></span>
                        <?php endif; ?>
                    </div>
                    <?php if ($forecastByWeekday === []): ?>
                        <div class="alert alert-info mb-0" role="status">There is not enough recent sales history to calculate a forecast.</div>
                    <?php else: ?>
                        <?php if ($forecastHistoryStale): ?>
                            <div class="alert alert-info" role="status">The latest sale in the forecast baseline is more than 7 days old. Treat these estimates cautiously; they may not reflect current trading.</div>
                        <?php endif; ?>
                        <div class="row g-3 mb-3">
                            <div class="col-12 col-md-6">
                                <div class="analytics-forecast-summary h-100">
                                    <h3 class="h6 text-secondary">Expected Revenue</h3>
                                    <p class="h3 mb-0">₱<?= analyticsMoney($forecastRevenue) ?></p>
                                    <span class="small text-secondary">Next 7 days</span>
                                </div>
                            </div>
                            <div class="col-12 col-md-6">
                                <div class="analytics-forecast-summary h-100">
                                    <h3 class="h6 text-secondary">Expected Transactions</h3>
                                    <p class="h3 mb-0"><?= number_format($forecastSaleCount, 1) ?></p>
                                    <span class="small text-secondary">Next 7 days</span>
                                </div>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-striped align-middle">
                                <thead class="table-dark">
                                    <tr>
                                        <th scope="col">Forecast Date</th>
                                        <th scope="col" class="text-end">Expected Transactions</th>
                                        <th scope="col" class="text-end">Expected Revenue</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($forecast as $forecastDay): ?>
                                        <tr>
                                            <th scope="row"><?= analyticsEscape($forecastDay['date']->format('D, M j')) ?></th>
                                            <td class="text-end"><?= number_format($forecastDay['sale_count'], 1) ?></td>
                                            <td class="text-end">₱<?= analyticsMoney($forecastDay['revenue']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <p class="small text-secondary mt-2 mb-0">This is a historical weekday-average baseline, not a guarantee. It includes zero-sale days in the 8-week average.</p>
                    <?php endif; ?>
                </div>
            </section>

            <section class="card mb-4" aria-labelledby="daily-trend-heading">
                <div class="card-body">
                    <div class="d-flex flex-wrap justify-content-between align-items-baseline gap-2 mb-3">
                        <div>
                            <h2 id="daily-trend-heading" class="h4 mb-1">Daily Revenue Trend</h2>
                            <p class="text-secondary mb-0">Sales revenue by day, including days with no sales.</p>
                        </div>
                        <span class="small text-secondary">Peak: ₱<?= analyticsMoney($maxRevenue) ?></span>
                    </div>
                    <div class="analytics-chart-scroll">
                        <div class="analytics-chart" role="img" aria-label="Daily sales revenue over the selected reporting period">
                            <?php foreach ($trend as $index => $day): ?>
                                <?php
                                $barHeight = $day['revenue'] > 0 ? max(2, (int) round(($day['revenue'] / $chartScale) * 100)) : 0;
                                $showDateLabel = $index % $chartLabelInterval === 0 || $index === $periodDays - 1;
                                ?>
                                <div class="analytics-chart-column" title="<?= analyticsEscape($day['date']->format('M j, Y')) ?>: ₱<?= analyticsMoney($day['revenue']) ?> revenue, <?= number_format($day['sale_count']) ?> sales">
                                    <div class="analytics-chart-plot">
                                        <div class="analytics-chart-bar" style="height: <?= $barHeight ?>%" aria-hidden="true"></div>
                                    </div>
                                    <span class="analytics-chart-label"><?= $showDateLabel ? analyticsEscape($day['date']->format('M j')) : '&nbsp;' ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php if ($bestDay !== null): ?>
                        <p class="small text-secondary mt-2 mb-0">Highest revenue day: <?= analyticsEscape($bestDay->format('M j, Y')) ?> (₱<?= analyticsMoney($maxRevenue) ?>).</p>
                    <?php else: ?>
                        <p class="small text-secondary mt-2 mb-0">No sales recorded in this period.</p>
                    <?php endif; ?>
                </div>
            </section>

            <div class="row g-4">
                <div class="col-12 col-xl-7">
                    <section aria-labelledby="top-items-heading">
                        <h2 id="top-items-heading" class="h4 mb-3">Top Items by Revenue</h2>
                        <div class="table-responsive">
                            <table class="table table-striped align-middle">
                                <thead class="table-dark">
                                    <tr>
                                        <th scope="col">Item</th>
                                        <th scope="col" class="text-end">Units Sold</th>
                                        <th scope="col" class="text-end">Revenue</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($topItems === []): ?>
                                        <tr><td colspan="3" class="text-center">No item sales in this period.</td></tr>
                                    <?php else: ?>
                                        <?php foreach ($topItems as $item): ?>
                                            <tr>
                                                <td><?= analyticsEscape($item['item_name']) ?></td>
                                                <td class="text-end"><?= number_format((int) $item['quantity_sold']) ?></td>
                                                <td class="text-end">₱<?= analyticsMoney($item['income']) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </section>
                </div>
                <div class="col-12 col-xl-5">
                    <section aria-labelledby="cashier-performance-heading">
                        <h2 id="cashier-performance-heading" class="h4 mb-3">Sales by Cashier</h2>
                        <div class="table-responsive">
                            <table class="table table-striped align-middle">
                                <thead class="table-dark">
                                    <tr>
                                        <th scope="col">Cashier</th>
                                        <th scope="col" class="text-end">Sales</th>
                                        <th scope="col" class="text-end">Revenue</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($cashierSales === []): ?>
                                        <tr><td colspan="3" class="text-center">No cashier sales in this period.</td></tr>
                                    <?php else: ?>
                                        <?php foreach ($cashierSales as $cashier): ?>
                                            <tr>
                                                <td><?= analyticsEscape($cashier['cashier_username']) ?></td>
                                                <td class="text-end"><?= number_format((int) $cashier['sale_count']) ?></td>
                                                <td class="text-end">₱<?= analyticsMoney($cashier['revenue']) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </section>
                </div>
            </div>
        <?php endif; ?>
    </main>
</body>
</html>
