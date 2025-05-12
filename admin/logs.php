<?php
require_once '../config/db.php';
require_once '../utils/functions.php';

// Richiede che l'utente sia un admin
requireAdmin();

$log_file = __DIR__ . '/../logs/errors.log';
$logs = file_exists($log_file) ? file_get_contents($log_file) : "No logs available.";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>System Logs | Ricordella Admin</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="stylesheet" href="../assets/style/admin.css">
    <link rel="stylesheet" href="../assets/style/font-general.css">
</head>
<body>
    <header>
        <div class="logo">Ricordella Admin</div>
        <nav>
            <a href="dashboard.php">Users</a>
            <a href="logs.php" class="active">Logs</a>
        </nav>
        <div class="user-info">
            <span>Admin: <?php echo htmlspecialchars($_SESSION['username']); ?></span>
            <a href="../logout.php" class="logout">Logout</a>
        </div>
    </header>

    <main>
        <h1>System Logs</h1>
        <div class="logs-container">
            <pre class="logs"><?php echo htmlspecialchars($logs); ?></pre>
        </div>
    </main>

    <footer>
        <p>&copy; <?php echo date('Y'); ?> Ricordella - Admin Panel</p>
    </footer>
</body>
</html>