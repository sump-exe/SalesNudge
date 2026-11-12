<?php
// Navigation bar ng manager. Kailangan ng $navActive galing sa page: 'dashboard', 'menu' o 'orders'.
// Bootstrap 5 navbar ito, kaya nagko-collapse sa maliit na screen (kailangan ng bootstrap.bundle.min.js sa page).
// Manager pages lang ang laman ng links dito, walang link papunta sa admin pages.

// Siguraduhing may CSRF token para gumana ang Log out button kahit saang manager page
if (empty($_SESSION['manager_csrf_token'])) {
    $_SESSION['manager_csrf_token'] = bin2hex(random_bytes(32));
}

$managerNavLinks = [
    'dashboard' => ['index.php', 'Sales Summary'],
    'menu'      => ['menu.php', 'Menu Items'],
    'orders'    => ['orderHistory.php', 'Order History'],
];
?>
<nav class="navbar navbar-expand-md bg-white border-bottom sn-navbar" aria-label="Manager navigation">
    <div class="container">
        <a class="navbar-brand" href="index.php">Tansioco <span class="sn-nav-role">Manager</span></a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#managerNav" aria-controls="managerNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="managerNav">
            <ul class="navbar-nav me-auto mb-2 mb-md-0">
                <?php foreach ($managerNavLinks as $key => [$href, $label]): ?>
                    <li class="nav-item">
                        <a class="nav-link<?= $navActive === $key ? ' active' : '' ?>" href="<?= $href ?>"<?= $navActive === $key ? ' aria-current="page"' : '' ?>><?= $label ?></a>
                    </li>
                <?php endforeach; ?>
            </ul>
            <div class="d-flex align-items-center gap-2">
                <span class="text-secondary small"><?= htmlspecialchars((string) ($_SESSION['username'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                <!-- Ang manager/index.php ang nagha-handle ng logout (action=logout) -->
                <form method="POST" action="index.php" class="m-0">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['manager_csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="action" value="logout">
                    <button type="submit" class="btn btn-outline-danger btn-sm">Log out</button>
                </form>
            </div>
        </div>
    </div>
</nav>
