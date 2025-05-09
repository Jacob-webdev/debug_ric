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
// Initialize note controller
$noteController = new NoteController();
// Delete note
$result = $noteController->deleteNote($noteId);
// Return response
echo json_encode($result);
