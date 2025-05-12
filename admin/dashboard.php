<?php
require_once '../config/db.php';
require_once '../utils/functions.php';

// Require admin privileges
requireAdmin();

// Default sorting
$sort_column = $_GET['sort'] ?? 'id';
$sort_order = $_GET['order'] ?? 'asc';

// Validate sorting column and order
$valid_columns = ['id', 'username', 'email', 'role', 'is_premium', 'notes_count', 'created_at'];
if (!in_array($sort_column, $valid_columns)) {
    $sort_column = 'id';
}

if (!in_array($sort_order, ['asc', 'desc'])) {
    $sort_order = 'asc';
}

// Get all users with sorting
$users = getAllUsers($sort_column, $sort_order);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Admin Dashboard | Ricordella</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="stylesheet" href="../assets/style/dashboard.css">
    <link rel="stylesheet" href="../assets/style/admin.css">
    <link rel="stylesheet" href="../assets/style/font-general.css">
    <link rel="icon" href="../assets/img/logo-favicon.ico" type="image/x-icon">
</head>
<body>
    <header>
        <div class="logo">Ricordella Admin</div>
        <nav>
            <a href="dashboard.php" class="active">Users</a>
            <a href="logs.php">Logs</a>
        </nav>
        <div class="user-info">
            <span>Admin: <?php echo htmlspecialchars($_SESSION['username']); ?></span>
            <a href="../logout.php" class="logout">Logout</a>
        </div>
    </header>

    <main>
        <h1>User Management</h1>

        <?php if (isset($_SESSION['admin_message'])): ?>
            <div class="alert <?php echo $_SESSION['admin_message_type']; ?>">
                <?php
                    echo $_SESSION['admin_message'];
                    unset($_SESSION['admin_message']);
                    unset($_SESSION['admin_message_type']);
                ?>
            </div>
        <?php endif; ?>

        <div class="users-table-container">
            <table class="users-table">
                <thead>
                    <tr>
                        <th class="sortable">
                            <a href="?sort=id&order=<?php echo $sort_column === 'id' && $sort_order === 'asc' ? 'desc' : 'asc'; ?>">
                                ID
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                                     viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                     stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                     class="lucide lucide-chevrons-up-down <?php echo $sort_column === 'id' ? 'active' : ''; ?>">
                                    <path d="m7 15 5 5 5-5"></path>
                                    <path d="m7 9 5-5 5 5"></path>
                                </svg>
                            </a>
                        </th>
                        <th class="sortable">
                            <a href="?sort=username&order=<?php echo $sort_column === 'username' && $sort_order === 'asc' ? 'desc' : 'asc'; ?>">
                                Username
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                                     viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                     stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                     class="lucide lucide-chevrons-up-down <?php echo $sort_column === 'username' ? 'active' : ''; ?>">
                                    <path d="m7 15 5 5 5-5"></path>
                                    <path d="m7 9 5-5 5 5"></path>
                                </svg>
                            </a>
                        </th>
                        <th>Email</th>
                        <th>Role</th>
                        <th class="sortable">
                            <a href="?sort=is_premium&order=<?php echo $sort_column === 'is_premium' && $sort_order === 'asc' ? 'desc' : 'asc'; ?>">
                                Premium
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                                     viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                     stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                     class="lucide lucide-chevrons-up-down <?php echo $sort_column === 'is_premium' ? 'active' : ''; ?>">
                                    <path d="m7 15 5 5 5-5"></path>
                                    <path d="m7 9 5-5 5 5"></path>
                                </svg>
                            </a>
                        </th>
                       <th class="sortable" data-sort="notes_count">
                            Notes Count
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                                 viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                 stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                 class="lucide lucide-chevrons-up-down">
                                <path d="m7 15 5 5 5-5"></path>
                                <path d="m7 9 5-5 5 5"></path>
                            </svg>
                        </th>
                        <th class="sortable">
                            <a href="?sort=created_at&order=<?php echo $sort_column === 'created_at' && $sort_order === 'asc' ? 'desc' : 'asc'; ?>">
                                Created
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                                     viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                     stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                     class="lucide lucide-chevrons-up-down <?php echo $sort_column === 'created_at' ? 'active' : ''; ?>">
                                    <path d="m7 15 5 5 5-5"></path>
                                    <path d="m7 9 5-5 5 5"></path>
                                </svg>
                            </a>
                        </th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?php echo $user['id']; ?></td>
                            <td><?php echo htmlspecialchars($user['username']); ?></td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td><?php echo $user['role']; ?></td>
                            <td><?php echo $user['is_premium'] ? 'Yes' : 'No'; ?></td>
                            <td><?php echo $user['notes_count']; ?></td>
                            <td><?php echo formatDate($user['created_at']); ?></td>
                            <td class="actions">
                                <a href="edit_user.php?id=<?php echo $user['id']; ?>" class="btn edit">Edit</a>
                                <?php if ($user['role'] !== 'admin'): ?>
                                <a href="toggle_premium.php?id=<?php echo $user['id']; ?>&premium=<?php echo $user['is_premium'] ? '0' : '1'; ?>" class="btn <?php echo $user['is_premium'] ? 'remove' : 'add'; ?>">
                                    <?php echo $user['is_premium'] ? 'Remove Premium' : 'Add Premium'; ?>
                                </a>
                                <a href="delete_user.php?id=<?php echo $user['id']; ?>" class="btn delete" onclick="return confirm('Are you sure you want to delete this user? All their notes will also be deleted.')">Delete</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>

    <footer>
        <p>&copy; <?php echo date('Y'); ?> Ricordella - Admin Panel</p>
    </footer>

<script src="../assets/script/sort-table-admin.js"></script>
</body>
</html>