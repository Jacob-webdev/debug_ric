<?php
require_once '../../config.php';
require_once '../../models/User.php';
require_once '../../middlewares/AuthMiddleware.php';

// Initialize auth middleware
$authMiddleware = new AuthMiddleware();
$authMiddleware->requireAdmin();

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
$userId = $data['user_id'] ?? 0;

// Validate user ID
if (!$userId) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid user ID'
    ]);
    exit;
}

// Initialize User model
$userModel = new User();

try {
    // Get the user to check if it exists and is not an admin
    $user = $userModel->getUserById($userId);

    if (!$user) {
        echo json_encode([
            'success' => false,
            'message' => 'User not found'
        ]);
        exit;
    }

    if ($user['role'] === 'admin') {
        echo json_encode([
            'success' => false,
            'message' => 'Cannot delete an admin user'
        ]);
        exit;
    }

    // Delete the user
    $db = getDbConnection();
    $db->beginTransaction();

    // Delete all note shares associated with the user
    $stmt = $db->prepare("DELETE FROM note_shares WHERE user_id = ?");
    $stmt->execute([$userId]);

    // Delete all notes created by the user
    $stmt = $db->prepare("DELETE FROM notes WHERE user_id = ?");
    $stmt->execute([$userId]);

    // Finally delete the user
    $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
    $result = $stmt->execute([$userId]);

    if ($result) {
        $db->commit();
        echo json_encode([
            'success' => true,
            'message' => 'User deleted successfully'
        ]);
    } else {
        $db->rollBack();
        echo json_encode([
            'success' => false,
            'message' => 'Failed to delete user'
        ]);
    }
} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }

    echo json_encode([
        'success' => false,
        'message' => 'An error occurred: ' . $e->getMessage()
    ]);
}