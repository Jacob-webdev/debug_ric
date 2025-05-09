<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/session.php';

class RegisterController {
    private $userModel;
    private $session;

    public function __construct() {
        $this->userModel = new User();
        $this->session = new Session();
    }

    public function processRegistration($username, $email, $password, $confirm_password) {
        // Validate input
        $errors = $this->validateInput($username, $email, $password, $confirm_password);

        if (!empty($errors)) {
            return [
                'success' => false,
                'message' => implode('<br>', $errors)
            ];
        }

        // Register user
        $result = $this->userModel->register($username, $email, $password);

        if ($result['success']) {
            // Get user data to set session
            $userData = $this->userModel->getUserById($result['user_id']);

            // Set user session
            $this->session->setUserSession($userData);

            return [
                'success' => true,
                'message' => 'Registration successful',
                'redirect' => 'dashboard.php'
            ];
        } else {
            return [
                'success' => false,
                'message' => $result['message']
            ];
        }
    }

    private function validateInput($username, $email, $password, $confirm_password) {
        $errors = [];

        // Validate username
        if (empty($username)) {
            $errors[] = 'Username is required';
        } elseif (strlen($username) < 3 || strlen($username) > 50) {
            $errors[] = 'Username must be between 3 and 50 characters';
        } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
            $errors[] = 'Username can only contain letters, numbers, and underscores';
        }

        // Validate email
        if (empty($email)) {
            $errors[] = 'Email is required';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Invalid email format';
        }

        // Validate password
        if (empty($password)) {
            $errors[] = 'Password is required';
        } elseif (strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters long';
        } elseif (!preg_match('/[A-Z]/', $password) ||
                  !preg_match('/[a-z]/', $password) ||
                  !preg_match('/[0-9]/', $password)) {
            $errors[] = 'Password must contain at least one uppercase letter, one lowercase letter, and one number';
        }

        // Check if passwords match
        if ($password !== $confirm_password) {
            $errors[] = 'Passwords do not match';
        }

        return $errors;
    }
}

// Process registration if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $registerController = new RegisterController();

    $username = $_POST['username'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    $result = $registerController->processRegistration($username, $email, $password, $confirm_password);

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
            // Set error message in session to display on registration page
            $_SESSION['register_error'] = $result['message'];
            // Keep form data for re-populating the form
            $_SESSION['form_data'] = [
                'username' => $username,
                'email' => $email
            ];
            header('Location: /public/register.php');
            exit;
        }
    }
}