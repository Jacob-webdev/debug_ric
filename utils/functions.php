<?php
require_once __DIR__ . '/../config/db.php';

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

/**
 * Check if user is admin
 */
function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

/**
 * Check if user is premium
 */
function isPremium() {
    return isset($_SESSION['is_premium']) && $_SESSION['is_premium'] === true;
}

/**
 * Redirect user if not logged in
 */
function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: /php_ricordella/login.php");
        exit;
    }
}

/**
 * Redirect user if not admin
 */
function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        header("Location: /php_ricordella/user/dashboard.php");
        exit;
    }
}

/**
 * Get user by ID
 */
function getUserById($userId) {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();
    return $user;
}

/**
 * Get notes by user ID
 */
function getNotesByUserId($userId, $orderBy = "created_at DESC") {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM notes WHERE user_id = ? ORDER BY $orderBy");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $notes = [];
    while ($note = $result->fetch_assoc()) {
        $notes[] = $note;
    }
    $stmt->close();
    return $notes;
}

/**
 * Get note by ID
 */
function getNoteById($noteId) {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM notes WHERE id = ?");
    $stmt->bind_param("i", $noteId);
    $stmt->execute();
    $result = $stmt->get_result();
    $note = $result->fetch_assoc();
    $stmt->close();
    return $note;
}

/**
 * Get shared notes for a user
 */
function getSharedNotes($userId) {
    global $conn;
    $sql = "SELECT n.*, u.username, ns.permission 
            FROM notes n 
            JOIN note_shares ns ON n.id = ns.note_id 
            JOIN users u ON n.user_id = u.id
            WHERE ns.user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $notes = [];
    while ($note = $result->fetch_assoc()) {
        $notes[] = $note;
    }
    $stmt->close();
    return $notes;
}

/**
 * Get today's notes
 */
function getTodaysNotes($userId) {
    global $conn;
    $today = date('Y-m-d');
    $sql = "SELECT * FROM notes WHERE user_id = ? AND DATE(created_at) = ? ORDER BY priority DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("is", $userId, $today);
    $stmt->execute();
    $result = $stmt->get_result();
    $notes = [];
    while ($note = $result->fetch_assoc()) {
        $notes[] = $note;
    }
    $stmt->close();
    return $notes;
}

/**
 * Format date for display
 */
function formatDate($date) {
    return date('d M Y, H:i', strtotime($date));
}

/**
 * Count notes for a user
 */
function countUserNotes($userId) {
    global $conn;
    $stmt = $conn->prepare("SELECT COUNT(*) AS count FROM notes WHERE user_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();
    $stmt->close();
    return $data['count'];
}

/**
 * Get all users (for admin)
 */
function getAllUsers($sort_column = 'id', $sort_order = 'asc') {
    global $conn;

    // Sanitize column to prevent SQL injection
    $valid_columns = ['id', 'username', 'email', 'role', 'is_premium', 'notes_count', 'created_at'];
    if (!in_array($sort_column, $valid_columns)) {
        $sort_column = 'id';
    }

    $sort_order = strtolower($sort_order) === 'desc' ? 'DESC' : 'ASC';

    // Handling special case for notes_count which is a calculated field
    if ($sort_column === 'notes_count') {
        $order_by = "COUNT(notes.id) $sort_order";
    } else {
        $order_by = "users.$sort_column $sort_order";
    }

    // Query to get all users with sorting
    $query = "
        SELECT users.id, users.username, users.email, users.role, 
               users.is_premium, users.created_at, COUNT(notes.id) AS notes_count
        FROM users
        LEFT JOIN notes ON users.id = notes.user_id
        GROUP BY users.id
        ORDER BY $order_by
    ";

    $result = $conn->query($query);
    if (!$result) {
        logError("Database error in getAllUsers: " . $conn->error);
        die("Database error: " . $conn->error);
    }

    $users = [];
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }

    return $users;
}

function logError($message) {
    $log_file = __DIR__ . '/../logs/errors.log';
    $date = date('Y-m-d H:i:s');
    $formatted_message = "[$date] $message\n";
    file_put_contents($log_file, $formatted_message, FILE_APPEND);
}
?>
