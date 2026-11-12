<?php
// Navigation bar ng admin. Kailangan ng $navActive galing sa page: 'users' o 'sessions'.
// Admin pages lang ito. Hindi ito ginagamit ng manager o cashier.

// Siguraduhing may CSRF token para gumana ang Log out button kahit saang admin page
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$adminNavLinks = [
    'users'    => ['index.php', 'Users'],
    'sessions' => ['sessions.php', 'Sessions'],
];
?>
<nav class="navbar navbar-expand-md bg-white border-bottom sn-navbar" aria-label="Admin navigation">
    <div class="container">
        <a class="navbar-brand" href="index.php">Tansioco <span class="sn-nav-role">Admin</span></a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#adminNav" aria-controls="adminNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="adminNav">
            <ul class="navbar-nav me-auto mb-2 mb-md-0">
                <?php foreach ($adminNavLinks as $key => [$href, $label]): ?>
                    <li class="nav-item">
                        <a class="nav-link<?= $navActive === $key ? ' active' : '' ?>" href="<?= $href ?>"<?= $navActive === $key ? ' aria-current="page"' : '' ?>><?= $label ?></a>
                    </li>
                <?php endforeach; ?>
            </ul>
            <div class="d-flex align-items-center gap-2">
                <span class="text-secondary small"><?= htmlspecialchars((string) ($_SESSION['username'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                <!-- Ang admin/index.php ang nagha-handle ng logout (action=logout) -->
                <form method="POST" action="index.php" class="m-0">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="action" value="logout">
                    <button type="submit" class="btn btn-outline-danger btn-sm">Log out</button>
                </form>
            </div>
        </div>
    </div>
</nav>
