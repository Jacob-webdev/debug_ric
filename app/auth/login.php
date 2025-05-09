<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/session.php';

class LoginController {
    private $userModel;
    private $session;

    public function __construct() {
        $this->userModel = new User();
        $this->session = new Session();
    }

    public function processLogin($email_or_username, $password) {
        if (empty($email_or_username) || empty($password)) {
            return [
                'success' => false,
                'message' => 'Please enter both email/username and password'
            ];
        }

        $result = $this->userModel->login($email_or_username, $password);

        if ($result['success']) {
            // Set user session
            $this->session->setUserSession($result['user']);

            return [
                'success' => true,
                'message' => 'Login successful',
                'redirect' => 'dashboard.php'
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Invalid username/email or password'
            ];
        }
    }
}

// Process login if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $loginController = new LoginController();

    $email_or_username = $_POST['email'] ?? $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    $result = $loginController->processLogin($email_or_username, $password);

    // If AJAX request, return JSON
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
        strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode($result);
        exit;
    } else {
        // For regular form submission
        if ($result['success']) {
            header('Location: ' . $result['redirect']);
            exit;
        } else {
            // Set error message in session to display on login page
            $_SESSION['login_error'] = $result['message'];
            header('Location: /public/login.php');
            exit;
        }
    }
}