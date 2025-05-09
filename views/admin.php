<?php
require_once '../app/auth/session.php';
require_once '../app/middlewares/AuthMiddleware.php';
require_once '../app/models/User.php';

// Initialize auth middleware and check admin rights
$authMiddleware = new AuthMiddleware();
$authMiddleware->requireAdmin();

// Initialize session
$session = new Session();

// Get all users
$userModel = new User();
$users = $userModel->getAllUsers();

// Get user statistics
$db = getDbConnection();
$stmt = $db->prepare(
    "SELECT u.id, u.username, COUNT(n.id) as note_count 
     FROM users u 
     LEFT JOIN notes n ON u.id = n.user_id 
     GROUP BY u.id 
     ORDER BY note_count DESC"
);
$stmt->execute();
$userStats = $stmt->fetchAll();

// Associate stats with users
$usersWithStats = [];
foreach ($users as $user) {
    $usersWithStats[$user['id']] = $user;
    $usersWithStats[$user['id']]['note_count'] = 0;
}

foreach ($userStats as $stat) {
    if (isset($usersWithStats[$stat['id']])) {
        $usersWithStats[$stat['id']]['note_count'] = $stat['note_count'];
    }
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | Ricordella</title>
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <link rel="stylesheet" href="assets/css/admin.css">
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
                    <li><a href="today.php">Today</a></li>
                    <li><a href="shared.php">Shared</a></li>
                    <li><a href="admin.php" class="active">Admin</a></li>
                </ul>
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
                    </div>
                </div>
            </div>
        </nav>
    </header>

    <main>
        <div class="admin-header">
            <h1>Admin Dashboard</h1>
            <div class="system-stats">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Total Users</h3>
                        <span><?= count($users) ?></span>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-crown"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Premium Users</h3>
                        <span><?= count(array_filter($users, function($user) { return $user['is_premium'] == 1; })) ?></span>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-sticky-note"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Total Notes</h3>
                        <span><?= array_sum(array_column($userStats, 'note_count')) ?></span>
                    </div>
                </div>
            </div>
        </div>

        <div class="admin-content">
            <div class="users-table-container">
                <div class="table-header">
                    <h2>User Management</h2>
                    <div class="table-actions">
                        <input type="text" id="user-search" placeholder="Search users...">
                    </div>
                </div>

                <table class="users-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Registered</th>
                            <th>Notes</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($usersWithStats as $user): ?>
                        <tr data-user-id="<?= $user['id'] ?>">
                            <td><?= $user['id'] ?></td>
                            <td><?= htmlspecialchars($user['username']) ?></td>
                            <td><?= htmlspecialchars($user['email']) ?></td>
                            <td>
                                <span class="user-role <?= $user['role'] ?>"><?= ucfirst($user['role']) ?></span>
                            </td>
                            <td>
                                <?php if ($user['is_premium']): ?>
                                    <span class="user-status premium">Premium</span>
                                <?php else: ?>
                                    <span class="user-status standard">Standard</span>
                                <?php endif; ?>
                            </td>
                            <td><?= date('Y-m-d', strtotime($user['created_at'])) ?></td>
                            <td><?= $user['note_count'] ?></td>
                            <td class="action-buttons">
                                <?php if ($user['is_premium']): ?>
                                    <button class="btn-remove-premium" data-user-id="<?= $user['id'] ?>" title="Remove Premium">
                                        <i class="fas fa-crown"></i>
                                    </button>
                                <?php else: ?>
                                    <button class="btn-make-premium" data-user-id="<?= $user['id'] ?>" title="Make Premium">
                                        <i class="far fa-crown"></i>
                                    </button>
                                <?php endif; ?>

                                <?php if ($user['id'] !== $session->getUserId()): ?>
                                    <button class="btn-delete-user" data-user-id="<?= $user['id'] ?>" title="Delete User">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <!-- Confirm Delete User Popup -->
    <div id="delete-user-popup" class="popup">
        <div class="popup-content delete-confirm">
            <h2>Delete User</h2>
            <p>Are you sure you want to delete this user? This will permanently remove all their notes and cannot be undone.</p>
            <div class="delete-actions">
                <button id="confirm-delete-user" class="btn-danger">Delete</button>
                <button id="cancel-delete-user" class="btn-secondary">Cancel</button>
            </div>
        </div>
    </div>

    <footer class="footer">
        <div class="container">
            <div class="row">
                <div class="footer-col">
                    <h4>company</h4>
                    <ul>
                        <li><a href="#">about us</a></li>
                        <li><a href="#">our services</a></li>
                        <li><a href="#">privacy policy</a></li>
                        <li><a href="#">affiliate program</a></li>
                    </ul>
                </div>
                <div class="footer-col">
                    <h4>get help</h4>
                    <ul>
                        <li><a href="#">FAQ</a></li>
                        <li><a href="#">help</a></li>
                        <li><a href="#">AI chat</a></li>
                        <li><a href="#">recovery data</a></li>
                    </ul>
                </div>
                <div class="footer-col">
                    <h4>Premium</h4>
                    <ul>
                        <li><a href="premium.php">info</a></li>
                        <li><a href="#">offers</a></li>
                        <li><a href="#">account</a></li>
                        <li><a href="#">payments</a></li>
                    </ul>
                </div>
                <div class="footer-col">
                    <h4>follow us</h4>
                    <div class="social-links">
                        <a href="#"><i class="fab fa-facebook-f"></i></a>
                        <a href="#"><i class="fab fa-twitter"></i></a>
                        <a href="#"><i class="fab fa-instagram"></i></a>
                        <a href="#"><i class="fab fa-linkedin-in"></i></a>
                    </div>
                </div>
            </div>
        </div>
        <div>
            <hr class="footer-line">
            <p class="footer-text-line">Copyright © 2025 All rights reserved.</p>
        </div>
    </footer>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // User search functionality
            const userSearch = document.getElementById('user-search');
            userSearch.addEventListener('input', function() {
                const searchTerm = this.value.toLowerCase();

                document.querySelectorAll('.users-table tbody tr').forEach(row => {
                    const username = row.querySelector('td:nth-child(2)').textContent.toLowerCase();
                    const email = row.querySelector('td:nth-child(3)').textContent.toLowerCase();

                    if (username.includes(searchTerm) || email.includes(searchTerm)) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
            });

            // Make premium buttons
            document.querySelectorAll('.btn-make-premium').forEach(button => {
                button.addEventListener('click', function() {
                    const userId = this.getAttribute('data-user-id');
                    updatePremiumStatus(userId, true);
                });
            });

            // Remove premium buttons
            document.querySelectorAll('.btn-remove-premium').forEach(button => {
                button.addEventListener('click', function() {
                    const userId = this.getAttribute('data-user-id');
                    updatePremiumStatus(userId, false);
                });
            });

            // Delete user buttons
            let userIdToDelete = null;
            const deleteUserPopup = document.getElementById('delete-user-popup');

            document.querySelectorAll('.btn-delete-user').forEach(button => {
                button.addEventListener('click', function() {
                    userIdToDelete = this.getAttribute('data-user-id');
                    deleteUserPopup.style.display = 'flex';
                });
            });

            // Cancel delete user
            document.getElementById('cancel-delete-user').addEventListener('click', function() {
                deleteUserPopup.style.display = 'none';
                userIdToDelete = null;
            });

            // Confirm delete user
            document.getElementById('confirm-delete-user').addEventListener('click', function() {
                if (userIdToDelete) {
                    deleteUser(userIdToDelete);
                }
            });

            // Function to update premium status
            function updatePremiumStatus(userId, isPremium) {
                fetch('../app/api/admin/update_premium.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        user_id: userId,
                        is_premium: isPremium ? 1 : 0
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Reload the page to reflect changes
                        window.location.reload();
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred. Please try again.');
                });
            }

            // Function to delete user
            function deleteUser(userId) {
                fetch('../app/api/admin/delete_user.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        user_id: userId
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Close popup
                        deleteUserPopup.style.display = 'none';

                        // Remove user row from table
                        const userRow = document.querySelector(`tr[data-user-id="${userId}"]`);
                        if (userRow) {
                            userRow.remove();
                        }

                        // Update stats (simplest way is to reload)
                        window.location.reload();
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred. Please try again.');
                });
            }
        });
    </script>
</body>
</html>