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
$isPremium = $data['is_premium'] ?? 0;

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

// Update user's premium status
$result = $userModel->updatePremiumStatus($userId, $isPremium);

if ($result) {
    echo json_encode([
        'success' => true,
        'message' => 'Premium status updated successfully'
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to update premium status'
    ]);
}