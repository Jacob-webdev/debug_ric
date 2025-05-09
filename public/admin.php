<?php

require_once '../app/config.php';
require_once '../app/auth/session.php';
require_once '../app/middlewares/AuthMiddleware.php';
// Initialize auth middleware
$authMiddleware = new AuthMiddleware();
$authMiddleware->requireAdmin();
// Include admin view
require_once '../views/admin.php';
