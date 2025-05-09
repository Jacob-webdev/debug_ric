<?php
require_once '../../config.php';
require_once '../../controllers/NoteController.php';
require_once '../../middlewares/AuthMiddleware.php';

// Initialize auth middleware
$authMiddleware = new AuthMiddleware();
$authMiddleware->requireLogin();

// Set JSON response headers
header('Content-Type: application/json');

// Check if request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
    exit;
}

// Get request data from JSON
$data = json_decode(file_get_contents('php://input'), true);
$noteId = $data['note_id'] ?? 0;
$targetUserId = $data['user_id'] ?? 0;

// Validate inputs
if (!$noteId || !$targetUserId) {
    echo json_encode([
        'success' => false,
        'message' => 'Note ID and target user ID are required'
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
            'message' => 'You do not have permission to remove shares for this note'
        ]);
        exit;
    }

    // Remove the share
    $stmt = $db->prepare("DELETE FROM note_shares WHERE note_id = ? AND user_id = ?");
    $stmt->execute([$noteId, $targetUserId]);

    // Update the is_shared flag if this was the last share
    $stmt = $db->prepare(
        "UPDATE notes 
         SET is_shared = EXISTS(SELECT 1 FROM note_shares WHERE note_id = ?)
         WHERE id = ?"
    );
    $stmt->execute([$noteId, $noteId]);

    echo json_encode([
        'success' => true,
        'message' => 'Share removed successfully'
    ]);
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}