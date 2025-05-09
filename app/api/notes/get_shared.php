<?php
require_once '../../config.php';
require_once '../../controllers/NoteController.php';
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

// Initialize note controller
$noteController = new NoteController();

// Get shared notes
$sharedNotes = $noteController->getSharedNotes();

echo json_encode([
    'success' => true,
    'notes' => $sharedNotes
]);