<?php
require_once '../../config.php';
require_once '../../middlewares/AuthMiddleware.php';

// Initialize auth middleware
$authMiddleware = new AuthMiddleware();
$authMiddleware->requireLogin();

// Set JSON response headers
header('Content-Type: application/json');

// Check if request method is GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
    exit;
}

// Get request data
$noteId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Validate inputs
if (!$noteId) {
    echo json_encode([
        'success' => false,
        'message' => 'Note ID is required'
    ]);
    exit;
}

// Initialize session
$session = new Session();
$userId = $session->getUserId();

// Get database connection
$db = getDbConnection();

try {
    // Check note ownership
    $stmt = $db->prepare("SELECT id FROM notes WHERE id = ? AND user_id = ?");
    $stmt->execute([$noteId, $userId]);

    if ($stmt->rowCount() === 0) {
        echo json_encode([
            'success' => false,
            'message' => 'You do not have permission to view shares for this note'
        ]);
        exit;
    }

    // Get shares for the note
    $stmt = $db->prepare(
        "SELECT ns.note_id, ns.user_id, u.username, ns.permission, ns.shared_at
         FROM note_shares ns
         JOIN users u ON ns.user_id = u.id
         WHERE ns.note_id = ?
         ORDER BY ns.shared_at DESC"
    );
    $stmt->execute([$noteId]);
    $shares = $stmt->fetchAll();

    echo json_encode([
        'success' => true,
        'shares' => $shares
    ]);
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}