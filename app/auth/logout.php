<?php
require_once __DIR__ . '/session.php';

$session = new Session();
$session->logout();

// Redirect to login page
header('Location: /public/login.php');
exit;