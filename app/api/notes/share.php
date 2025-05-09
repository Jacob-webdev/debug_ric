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

// Get request data
$noteId = isset($_POST['note_id']) ? (int)$_POST['note_id'] : 0;
$username = $_POST['username'] ?? '';
$permission = $_POST['permission'] ?? 'view';

// Validate inputs
if (!$noteId || !$username) {
    echo json_encode([
        'success' => false,
        'message' => 'Note ID and username are required'
    ]);
    exit;
}

// Validate permission value
if (!in_array($permission, ['view', 'edit'])) {
    $permission = 'view'; // Default to view-only if invalid
}

// Initialize note controller
$noteController = new NoteController();

// Share the note
$result = $noteController->shareNote($noteId, $username, $permission);

// Return response
echo json_encode($result);