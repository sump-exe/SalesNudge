<?php
// UI ng Admin Sessions page (HTML lang ito). Ang data ay kinukuha ng sessions.js mula sa sessions_data.php.
// Galing sa admin/sessions.php ang pagtawag dito, kaya may session at admin check na.
$navActive = 'sessions';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="idle-csrf-token" content="<?= htmlspecialchars((string) $_SESSION['idle_csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link rel="stylesheet" href="../styles.css">
    <script src="../_idle_timeout.js" defer></script>
    <title>Admin - Sessions</title>
</head>
<body>
    <?php require __DIR__ . '/_nav.php'; ?>
    <main class="container py-5">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
            <h1 class="h2 mb-0">Sessions</h1>
            <button type="button" id="sessions-refresh" class="btn btn-primary">Refresh</button>
        </div>

        <p class="text-secondary mb-1">Login history of all accounts. The latest 200 sessions are shown, newest first.</p>
        <p class="text-secondary small mb-3">
            <strong>Active</strong> means no logout has been recorded yet. A session that was closed without logging out
            stays Active until its owner opens a page again. Admin and manager sessions are logged out after 5 minutes without activity.
        </p>

        <!-- Dito lumalabas ang loading, empty at error messages -->
        <div id="sessions-state" class="alert d-none" role="status" aria-live="polite"></div>
        <p id="sessions-summary" class="fw-semibold d-none"></p>

        <div id="sessions-table-wrap" class="table-responsive d-none">
            <table class="table table-striped table-bordered align-middle mb-0">
                <thead class="table-dark">
                    <tr>
                        <th scope="col">Record #</th>
                        <th scope="col">Username</th>
                        <th scope="col">Role</th>
                        <th scope="col">Login time</th>
                        <th scope="col">Logout / expired at</th>
                        <th scope="col">Duration</th>
                        <th scope="col">Auto logout</th>
                        <th scope="col">Status</th>
                    </tr>
                </thead>
                <tbody id="sessions-body"></tbody>
            </table>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
    <script src="sessions.js"></script>
</body>
</html>
