<?php
require_once '../app/config.php';
require_once '../app/auth/session.php';

// Initialize session
$session = new Session();

// Check if user is logged in
if ($session->isLoggedIn()) {
    // Redirect to dashboard
    header('Location: dashboard.php');
    exit;
} else {
    // Redirect to login
    header('Location: login.php');
    exit;
}