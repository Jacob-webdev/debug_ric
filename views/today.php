<?php
require_once '../app/auth/session.php';
require_once '../app/middlewares/AuthMiddleware.php';
require_once '../app/controllers/NoteController.php';

// Initialize auth middleware
$authMiddleware = new AuthMiddleware();
$authMiddleware->requireLogin();

// Initialize session
$session = new Session();

// Initialize note controller
$noteController = new NoteController();

// Get today's notes with sorting
$orderBy = $_GET['order_by'] ?? 'created_at';
$orderDir = $_GET['order_dir'] ?? 'DESC';

// Apply priority filter if set
$filters = ['today' => true];
if (isset($_GET['priority']) && is_numeric($_GET['priority'])) {
    $filters['priority'] = (int)$_GET['priority'];
}

// Get today's notes
$notes = $noteController->getTodayNotes($orderBy, $orderDir);

// Get available priority levels
$db = getDbConnection();
$stmt = $db->prepare(
    "SELECT id, label, premium_only 
     FROM priority_levels 
     ORDER BY id"
);
$stmt->execute();
$priorities = $stmt->fetchAll();

// Define priority class mappings for color coding
$priorityClasses = [
    1 => 'priority-low',
    2 => 'priority-normal',
    3 => 'priority-high',
    4 => 'priority-urgent'
];
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Today's Notes | Ricordella</title>
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <header id="redirect-top">
        <nav id="nav-bar">
            <div id="nav-logo">
                <a href="#redirect-top" id="link-logo">
                    <img src="assets/img/logo-nobg.png" id="logo" fetchpriority="high" loading="eager" alt="Logo"/>
                </a>
            </div>
            <div id="nav-links">
                <ul>
                    <li><a href="dashboard.php">Home</a></li>
                    <li><a href="#" id="open-popup-link">New <i class="fas fa-plus-circle" style="color: green;"></i></a></li>
                    <li><a href="today.php" class="active">Today</a></li>
                    <li><a href="shared.php">Shared</a></li>
                    <?php if (!$session->isPremium()): ?>
                        <li><a href="premium.php">Premium</a></li>
                    <?php endif; ?>
                    <?php if ($session->isAdmin()): ?>
                        <li><a href="admin.php">Admin</a></li>
                    <?php endif; ?>
                </ul>
            </div>
            <div class="search-box">
                <input class="search-txt" type="text" id="search-notes" placeholder="Type to Search">
                <a class="search-btn" href="#"><i class="fas fa-search"></i></a>
            </div>
            <div class="user-icon">
                <div class="user-dropdown">
                    <button class="user-dropbtn" id="user-menu-btn">
                        <i class="fas fa-user-circle"></i>
                        <span id="username-display"><?= htmlspecialchars($session->getUsername()) ?></span>
                        <i class="fas fa-caret-down"></i>
                    </button>
                    <div class="user-dropdown-content">
                        <a href="../app/auth/logout.php" id="logout-btn">Logout</a>
                        <a href="profile.php" id="profile-link">Profile</a>
                        <a href="settings.php" id="settings-link">Settings</a>
                    </div>
                </div>
            </div>
        </nav>
    </header>

    <main>
        <div class="filters-container">
            <h2>Today's Notes</h2>
            <div class="filters">
                <div class="sort-options">
                    <label for="sort-select">Sort by:</label>
                    <select id="sort-select" onchange="applySorting()">
                        <option value="created_at" <?= $orderBy === 'created_at' ? 'selected' : '' ?>>Creation Time</option>
                        <option value="priority" <?= $orderBy === 'priority' ? 'selected' : '' ?>>Priority</option>
                        <option value="title" <?= $orderBy === 'title' ? 'selected' : '' ?>>Title</option>
                    </select>
                    <button onclick="toggleSortDirection()" class="sort-direction">
                        <i class="fas fa-sort-<?= $orderDir === 'DESC' ? 'down' : 'up' ?>"></i>
                    </button>
                </div>
                <div class="priority-filter">
                    <label for="priority-filter">Filter by priority:</label>
                    <select id="priority-filter" onchange="applyPriorityFilter()">
                        <option value="">All Priorities</option>
                        <?php foreach ($priorities as $priority): ?>
                            <?php
                            // Skip premium-only priorities for non-premium users
                            if ($priority['premium_only'] && !$session->isPremium()) continue;
                            ?>
                            <option value="<?= $priority['id'] ?>" <?= isset($filters['priority']) && $filters['priority'] == $priority['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($priority['label']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>

        <section class="notes-exposing">
            <div class="expo-container">
                <?php if (empty($notes)): ?>
                    <div class="no-notes">
                        <p>You haven't created any notes today. Create your first note of the day!</p>
                        <button class="btn-create-note" id="create-first-note">Create Note</button>
                    </div>
                <?php else: ?>
                    <?php foreach ($notes as $note): ?>
                        <div class="note-container <?= $priorityClasses[$note['priority']] ?>" data-note-id="<?= $note['id'] ?>">
                            <div class="note-header">
                                <h3><?= htmlspecialchars($note['title']) ?></h3>
                                <div class="note-actions">
                                    <button class="btn-edit" data-note-id="<?= $note['id'] ?>"><i class="fas fa-edit"></i></button>
                                    <button class="btn-delete" data-note-id="<?= $note['id'] ?>"><i class="fas fa-trash"></i></button>
                                    <button class="btn-share" data-note-id="<?= $note['id'] ?>"><i class="fas fa-share-alt"></i></button>
                                </div>
                            </div>
                            <div class="note-priority">
                                <span class="priority-badge"><?= htmlspecialchars($note['priority_label']) ?></span>
                            </div>
                            <div class="note-content">
                                <?= nl2br(htmlspecialchars($note['content'])) ?>
                            </div>
                            <div class="note-footer">
                                <div class="note-dates">
                                    <small>Created: <?= date('H:i:s', strtotime($note['created_at'])) ?></small>
                                    <small>Updated: <?= date('H:i:s', strtotime($note['updated_at'])) ?></small>
                                </div>
                                <?php if ($note['is_shared']): ?>
                                    <div class="sharing-info">
                                        <i class="fas fa-users" title="This note is shared"></i>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>

        <!-- Popups are identical to the dashboard page -->
        <!-- New Note Popup -->
        <div id="popup" class="popup">
            <!-- Same as dashboard.php -->
        </div>

        <!-- Share Note Popup -->
        <div id="share-popup" class="popup">
            <!-- Same as dashboard.php -->
        </div>

        <!-- Confirm Delete Popup -->
        <div id="delete-popup" class="popup">
            <!-- Same as dashboard.php -->
        </div>
    </main>

    <footer class="footer">
        <!-- Same as dashboard.php -->
    </footer>

    <!-- Include the dashboard.js script which has all the functionality -->
    <script src="assets/scripts/dashboard.js"></script>
</body>
</html>