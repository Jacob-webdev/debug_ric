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
$title = $_POST['title'] ?? '';
$content = $_POST['content'] ?? '';
$priority = isset($_POST['priority']) ? (int)$_POST['priority'] : null;

// Initialize note controller
$noteController = new NoteController();

// Update note
$result = $noteController->updateNote($noteId, $title, $content, $priority);

// Return response
echo json_encode($result);