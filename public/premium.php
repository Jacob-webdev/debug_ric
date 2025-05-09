<?php

require_once '../app/config.php';
require_once '../app/auth/session.php';
require_once '../app/middlewares/AuthMiddleware.php';
// Initialize auth middleware
$authMiddleware = new AuthMiddleware();
$authMiddleware->requireLogin();
// Include premium view
require_once '../views/premium.php';
