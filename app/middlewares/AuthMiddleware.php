<?php
require_once __DIR__ . '/../auth/session.php';

class AuthMiddleware {
    private $session;

    public function __construct() {
        $this->session = new Session();
    }

    /**
     * Check if user is logged in
     */
    public function requireLogin() {
        if (!$this->session->isLoggedIn()) {
            header('Location: /public/login.php');
            exit;
        }

        return true;
    }

    /**
     * Check if user is an admin
     */
    public function requireAdmin() {
        $this->requireLogin();

        if (!$this->session->isAdmin()) {
            header('Location: /public/dashboard.php');
            exit;
        }

        return true;
    }

    /**
     * Check if user is a premium user
     */
    public function requirePremium() {
        $this->requireLogin();

        if (!$this->session->isPremium()) {
            header('Location: /public/premium.php');
            exit;
        }

        return true;
    }
}