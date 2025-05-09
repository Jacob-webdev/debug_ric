<?php

require_once '../app/config.php';
require_once '../app/auth/session.php';
// Initialize session
$session = new Session();
// If user is already logged in, redirect to dashboard
if ($session->isLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}

// Include login view
require_once '../views/login.php';
