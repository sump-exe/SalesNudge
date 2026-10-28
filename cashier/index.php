<?php
session_start();

if (($_SESSION['role'] ?? '') !== 'cashier') {
    header('Location: ../index.php');
    exit;
}

require_once __DIR__ . '/../_conn.php';

$conn->query(
    'CREATE TABLE IF NOT EXISTS `pos_sales` (
        `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        `cashier_username` VARCHAR(50) NOT NULL,
        `total` DECIMAL(10,2) NOT NULL,
        `amount_paid` DECIMAL(10,2) NOT NULL,
        `change_due` DECIMAL(10,2) NOT NULL,
        `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
);
$conn->query(
    'CREATE TABLE IF NOT EXISTS `pos_sale_items` (
        `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        `sale_id` BIGINT UNSIGNED NOT NULL,
        `menu_item_id` INT NOT NULL,
        `item_name` VARCHAR(100) NOT NULL,
        `quantity` INT UNSIGNED NOT NULL,
        `unit_price` DECIMAL(10,2) NOT NULL,
        `line_total` DECIMAL(10,2) NOT NULL,
        PRIMARY KEY (`id`),
        KEY `idx_pos_sale_items_sale_id` (`sale_id`),
        CONSTRAINT `fk_pos_sale_items_sale`
            FOREIGN KEY (`sale_id`) REFERENCES `pos_sales` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
);

if (empty($_SESSION['pos_csrf_token'])) {
    $_SESSION['pos_csrf_token'] = bin2hex(random_bytes(32));
}

$csrfToken = $_SESSION['pos_csrf_token'];
$error = '';
$receipt = $_SESSION['pos_receipt'] ?? null;
unset($_SESSION['pos_receipt']);

function posEscape(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function posMoneyToCents(string $amount): ?int
{
    if (!preg_match('/^\d{1,8}(?:\.\d{1,2})?$/', $amount)) {
        return null;
    }

    $parts = explode('.', $amount, 2);
    $fraction = str_pad($parts[1] ?? '', 2, '0');

    return ((int) $parts[0] * 100) + (int) $fraction;
}

function posFormatMoney(int $cents): string
{
    return number_format($cents / 100, 2, '.', ',');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submittedToken = $_POST['csrf_token'] ?? '';

    if (!is_string($submittedToken) || !hash_equals($csrfToken, $submittedToken)) {
        $error = 'Your session expired. Refresh the page and try again.';
    } elseif (($_POST['action'] ?? '') === 'logout') {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $cookie = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $cookie['path'], $cookie['domain'], $cookie['secure'], $cookie['httponly']);
        }
        session_destroy();
        header('Location: ../index.php');
        exit;
    } elseif (($_POST['action'] ?? '') !== 'checkout') {
        $error = 'Invalid action.';
    } elseif (!is_string($_POST['cart'] ?? null) || !is_string($_POST['amount_paid'] ?? null)) {
        $error = 'Invalid checkout details.';
    } else {
        $cartInput = $_POST['cart'];
        $amountPaidInput = $_POST['amount_paid'];
        $cart = json_decode($cartInput, true);
        $amountPaidCents = posMoneyToCents($amountPaidInput);

        if (!is_array($cart) || $cart === [] || count($cart) > 100) {
            $error = 'Add at least one item to the cart.';
        } elseif ($amountPaidCents === null) {
            $error = 'Enter a valid payment amount.';
        } else {
            $saleItems = [];
            $totalCents = 0;
            $itemQuery = $conn->prepare('SELECT `name`, `price` FROM `menu_items` WHERE `id` = ?');

            foreach ($cart as $cartLine) {
                if (!is_array($cartLine)) {
                    $error = 'The cart contains an invalid item.';
                    break;
                }

                $itemId = filter_var($cartLine['id'] ?? null, FILTER_VALIDATE_INT);
                $quantity = filter_var($cartLine['quantity'] ?? null, FILTER_VALIDATE_INT);

                if (!$itemId || !$quantity || $quantity > 999) {
                    $error = 'The cart contains an invalid item or quantity.';
                    break;
                }

                $itemQuery->bind_param('i', $itemId);
                $itemQuery->execute();
                $itemQuery->bind_result($itemName, $itemPrice);

                if (!$itemQuery->fetch()) {
                    $itemQuery->free_result();
                    $error = 'A selected menu item could not be found. Refresh and try again.';
                    break;
                }

                $itemQuery->free_result();
                $unitPriceCents = posMoneyToCents((string) $itemPrice);

                if ($unitPriceCents === null) {
                    $error = 'A menu item has an invalid price.';
                    break;
                }

                $lineTotalCents = $unitPriceCents * $quantity;
                if ($lineTotalCents > 9999999999 || $totalCents > 9999999999 - $lineTotalCents) {
                    $error = 'The sale total exceeds the supported amount.';
                    break;
                }

                $totalCents += $lineTotalCents;
                $saleItems[] = [
                    'id' => $itemId,
                    'name' => $itemName,
                    'quantity' => $quantity,
                    'unit_price' => posFormatMoney($unitPriceCents),
                    'line_total' => posFormatMoney($lineTotalCents),
                ];
            }

            $itemQuery->close();

            if ($error === '' && $amountPaidCents < $totalCents) {
                $error = 'The payment amount is less than the total.';
            }

            if ($error === '') {
                $changeCents = $amountPaidCents - $totalCents;
                $total = posFormatMoney($totalCents);
                $amountPaid = posFormatMoney($amountPaidCents);
                $changeDue = posFormatMoney($changeCents);
                $cashierUsername = (string) ($_SESSION['username'] ?? '');

                $conn->begin_transaction();

                $saleInsert = $conn->prepare(
                    'INSERT INTO `pos_sales` (`cashier_username`, `total`, `amount_paid`, `change_due`)
                    VALUES (?, ?, ?, ?)'
                );
                $saleInsert->bind_param('ssss', $cashierUsername, $total, $amountPaid, $changeDue);
                $saleInsert->execute();
                $saleId = $conn->insert_id;
                $saleInsert->close();

                $lineInsert = $conn->prepare(
                    'INSERT INTO `pos_sale_items`
                    (`sale_id`, `menu_item_id`, `item_name`, `quantity`, `unit_price`, `line_total`)
                    VALUES (?, ?, ?, ?, ?, ?)'
                );

                foreach ($saleItems as $saleItem) {
                    $lineInsert->bind_param(
                        'iisiss',
                        $saleId,
                        $saleItem['id'],
                        $saleItem['name'],
                        $saleItem['quantity'],
                        $saleItem['unit_price'],
                        $saleItem['line_total']
                    );
                    $lineInsert->execute();
                }

                $lineInsert->close();
                $conn->commit();

                $_SESSION['pos_receipt'] = [
                    'id' => $saleId,
                    'items' => $saleItems,
                    'total' => $total,
                    'amount_paid' => $amountPaid,
                    'change_due' => $changeDue,
                ];
                header('Location: index.php');
                exit;
            }
        }
    }
}

$productsResult = $conn->query('SELECT `id`, `name`, `price` FROM `menu_items` ORDER BY `name`');
$products = $productsResult->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <title>Point of Sale</title>
    <style>
        body { background: #f5f7fb; }
        .product-card { cursor: pointer; transition: transform .12s ease, box-shadow .12s ease; }
        .product-card:hover { transform: translateY(-2px); box-shadow: 0 .5rem 1rem #00000014; }
        .cart-panel { position: sticky; top: 1rem; }
        .cart-item-name { min-width: 0; overflow-wrap: anywhere; }
        @media print {
            body * { visibility: hidden; }
            #receipt, #receipt * { visibility: visible; }
            #receipt { position: absolute; inset: 0; width: 100%; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>
    <nav class="navbar bg-white border-bottom">
        <div class="container">
            <span class="navbar-brand fw-semibold">Point of Sale</span>
            <div class="d-flex align-items-center gap-3">
                <span class="text-secondary">Cashier: <?= posEscape((string) ($_SESSION['username'] ?? '')) ?></span>
                <form method="POST" class="m-0">
                    <input type="hidden" name="csrf_token" value="<?= posEscape($csrfToken) ?>">
                    <input type="hidden" name="action" value="logout">
                    <button type="submit" class="btn btn-outline-danger btn-sm">Log out</button>
                </form>
            </div>
        </div>
    </nav>

    <main class="container py-4">
        <?php if ($error !== ''): ?>
            <div class="alert alert-danger" role="alert"><?= posEscape($error) ?></div>
        <?php endif; ?>

        <?php if (is_array($receipt)): ?>
            <section id="receipt" class="card mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h2 class="h4">Sale complete</h2>
                            <p class="text-secondary mb-3">Receipt #<?= (int) $receipt['id'] ?></p>
                        </div>
                        <button type="button" class="btn btn-outline-secondary no-print" onclick="window.print()">Print</button>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead><tr><th>Item</th><th>Qty</th><th>Price</th><th class="text-end">Amount</th></tr></thead>
                            <tbody>
                                <?php foreach ($receipt['items'] as $item): ?>
                                    <tr>
                                        <td><?= posEscape($item['name']) ?></td>
                                        <td><?= (int) $item['quantity'] ?></td>
                                        <td>$<?= posEscape($item['unit_price']) ?></td>
                                        <td class="text-end">$<?= posEscape($item['line_total']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="ms-auto" style="max-width: 18rem;">
                        <div class="d-flex justify-content-between"><span>Total</span><strong>₱<?= posEscape($receipt['total']) ?></strong></div>
                        <div class="d-flex justify-content-between"><span>Paid</span><span>₱<?= posEscape($receipt['amount_paid']) ?></span></div>
                        <div class="d-flex justify-content-between"><span>Change</span><span>₱<?= posEscape($receipt['change_due']) ?></span></div>
                    </div>
                </div>
            </section>
        <?php endif; ?>

        <form method="POST" id="checkout-form">
            <input type="hidden" name="csrf_token" value="<?= posEscape($csrfToken) ?>">
            <input type="hidden" name="action" value="checkout">
            <input type="hidden" name="cart" id="cart-data">
            <div class="row g-4">
                <section class="col-lg-7" aria-labelledby="menu-heading">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h1 class="h3 mb-0" id="menu-heading">Menu</h1>
                        <input type="search" class="form-control" id="product-search" placeholder="Search menu" aria-label="Search menu" style="max-width: 16rem;">
                    </div>
                    <?php if ($products === []): ?>
                        <div class="alert alert-info">No menu items are available.</div>
                    <?php else: ?>
                        <div class="row row-cols-1 row-cols-sm-2 row-cols-xl-3 g-3" id="product-list">
                            <?php foreach ($products as $product): ?>
                                <div class="col product-column" data-search="<?= posEscape(strtolower($product['name'])) ?>">
                                    <button
                                        type="button"
                                        class="card product-card text-start w-100 h-100 border-0"
                                        data-product-id="<?= (int) $product['id'] ?>"
                                        data-product-name="<?= posEscape($product['name']) ?>"
                                        data-product-price="<?= posEscape((string) $product['price']) ?>"
                                    >
                                        <span class="card-body">
                                            <span class="d-block fw-semibold"><?= posEscape($product['name']) ?></span>
                                            <span class="d-block text-success mt-2">₱<?= posEscape(number_format((float) $product['price'], 2)) ?></span>
                                        </span>
                                    </button>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </section>

                <aside class="col-lg-5">
                    <div class="card cart-panel">
                        <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                            <h2 class="h5 mb-0">Current Sale</h2>
                            <button type="button" class="btn btn-sm btn-outline-danger" id="clear-cart">Clear</button>
                        </div>
                        <div class="card-body">
                            <div id="empty-cart" class="text-center text-secondary py-4">Select menu items to start a sale.</div>
                            <div id="cart-items" class="mb-3"></div>
                            <hr>
                            <div class="d-flex justify-content-between fs-5 mb-3">
                                <span>Total</span><strong id="cart-total">$0.00</strong>
                            </div>
                            <label for="amount-paid" class="form-label">Amount paid</label>
                            <div class="input-group mb-2">
                                <span class="input-group-text">₱</span>
                                <input type="number" class="form-control" id="amount-paid" name="amount_paid" min="0" step="0.01" placeholder="0.00" required>
                            </div>
                            <div class="d-flex justify-content-between text-secondary mb-3">
                                <span>Change</span><span id="change-due">$0.00</span>
                            </div>
                            <button type="submit" class="btn btn-success w-100" id="checkout-button" disabled>Complete Sale</button>
                        </div>
                    </div>
                </aside>
            </div>
        </form>
    </main>

    <script>
        const cart = new Map();
        const productButtons = document.querySelectorAll('[data-product-id]');
        const cartItems = document.getElementById('cart-items');
        const emptyCart = document.getElementById('empty-cart');
        const cartTotal = document.getElementById('cart-total');
        const amountPaid = document.getElementById('amount-paid');
        const changeDue = document.getElementById('change-due');
        const checkoutButton = document.getElementById('checkout-button');
        const cartData = document.getElementById('cart-data');

        function formatMoney(cents) {
            return '₱' + (cents / 100).toLocaleString(undefined, {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        }

        function renderCart() {
            cartItems.replaceChildren();
            let totalCents = 0;

            for (const [id, item] of cart) {
                const lineCents = item.priceCents * item.quantity;
                totalCents += lineCents;

                const row = document.createElement('div');
                row.className = 'd-flex align-items-center gap-2 py-2 border-bottom';

                const name = document.createElement('div');
                name.className = 'cart-item-name flex-grow-1';
                name.textContent = item.name;

                const quantity = document.createElement('div');
                quantity.className = 'btn-group btn-group-sm';

                const decrease = document.createElement('button');
                decrease.type = 'button';
                decrease.className = 'btn btn-outline-secondary';
                decrease.textContent = '−';
                decrease.setAttribute('aria-label', 'Decrease ' + item.name);
                decrease.addEventListener('click', () => {
                    if (item.quantity <= 1) {
                        cart.delete(id);
                    } else {
                        item.quantity -= 1;
                    }
                    renderCart();
                });

                const count = document.createElement('span');
                count.className = 'btn btn-outline-secondary disabled';
                count.textContent = item.quantity;

                const increase = document.createElement('button');
                increase.type = 'button';
                increase.className = 'btn btn-outline-secondary';
                increase.textContent = '+';
                increase.setAttribute('aria-label', 'Increase ' + item.name);
                increase.disabled = item.quantity >= 999;
                increase.addEventListener('click', () => {
                    if (item.quantity < 999) {
                        item.quantity += 1;
                        renderCart();
                    }
                });

                quantity.append(decrease, count, increase);

                const lineAmount = document.createElement('strong');
                lineAmount.className = 'text-nowrap';
                lineAmount.textContent = formatMoney(lineCents);

                row.append(name, quantity, lineAmount);
                cartItems.append(row);
            }

            emptyCart.classList.toggle('d-none', cart.size > 0);
            cartTotal.textContent = formatMoney(totalCents);
            const paidCents = Math.round((Number(amountPaid.value) || 0) * 100);
            changeDue.textContent = formatMoney(Math.max(0, paidCents - totalCents));
            checkoutButton.disabled = cart.size === 0 || paidCents < totalCents;
            cartData.value = JSON.stringify(Array.from(cart, ([id, item]) => ({
                id: Number(id),
                quantity: item.quantity
            })));
        }

        productButtons.forEach((button) => {
            button.addEventListener('click', () => {
                const id = button.dataset.productId;
                const priceCents = Math.round(Number(button.dataset.productPrice) * 100);
                const current = cart.get(id);

                if (current) {
                    if (current.quantity < 999) current.quantity += 1;
                } else {
                    cart.set(id, {
                        name: button.dataset.productName,
                        priceCents,
                        quantity: 1
                    });
                }
                renderCart();
            });
        });

        document.getElementById('clear-cart').addEventListener('click', () => {
            cart.clear();
            amountPaid.value = '';
            renderCart();
        });

        amountPaid.addEventListener('input', renderCart);

        document.getElementById('product-search').addEventListener('input', (event) => {
            const search = event.target.value.trim().toLowerCase();
            document.querySelectorAll('.product-column').forEach((column) => {
                column.classList.toggle('d-none', !column.dataset.search.includes(search));
            });
        });

        document.getElementById('checkout-form').addEventListener('submit', (event) => {
            if (cart.size === 0 || checkoutButton.disabled) {
                event.preventDefault();
            }
        });

        renderCart();
    </script>
</body>
</html>
