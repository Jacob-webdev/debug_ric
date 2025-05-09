<?php
require_once __DIR__ . '/../config.php';

class Session {
    public function __construct() {
        // Set secure session parameters
        ini_set('session.use_only_cookies', 1);
        ini_set('session.use_strict_mode', 1);

        session_set_cookie_params([
            'lifetime' => SESSION_LIFETIME,
            'path' => SESSION_PATH,
            'domain' => SESSION_DOMAIN,
            'secure' => SESSION_SECURE,
            'httponly' => SESSION_HTTPONLY
        ]);

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Check for session age
        if ($this->isSessionExpired()) {
            $this->regenerateSession();
        }
    }

    /**
     * Set user data in session after successful login
     */
    public function setUserSession($userData) {
        $_SESSION['user_id'] = $userData['id'];
        $_SESSION['username'] = $userData['username'];
        $_SESSION['email'] = $userData['email'];
        $_SESSION['is_premium'] = $userData['is_premium'];
        $_SESSION['role'] = $userData['role'];
        $_SESSION['created'] = time();
        $_SESSION['last_activity'] = time();

        // Regenerate session ID when setting user data
        $this->regenerateSession();
    }

    /**
     * Check if user is logged in
     */
    public function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }

    /**
     * Check if user is an admin
     */
    public function isAdmin() {
        return $this->isLoggedIn() && isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
    }

    /**
     * Check if user is premium
     */
    public function isPremium() {
        return $this->isLoggedIn() && isset($_SESSION['is_premium']) && $_SESSION['is_premium'] === 1;
    }

    /**
     * Get current user ID
     */
    public function getUserId() {
        return $this->isLoggedIn() ? $_SESSION['user_id'] : null;
    }

    /**
     * Get current username
     */
    public function getUsername() {
        return $this->isLoggedIn() ? $_SESSION['username'] : null;
    }

    /**
     * Logout user
     */
    public function logout() {
        // Unset all session variables
        $_SESSION = array();

        // Delete the session cookie
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }

        // Destroy the session
        session_destroy();
    }

    /**
     * Check if session has expired due to inactivity
     */
    private function isSessionExpired() {
        if (isset($_SESSION['last_activity']) &&
            (time() - $_SESSION['last_activity'] > SESSION_LIFETIME)) {
            return true;
        }

        // Update last activity time
        $_SESSION['last_activity'] = time();
        return false;
    }

    /**
     * Regenerate session ID to prevent session fixation attacks
     */
    private function regenerateSession() {
        // If this session is obsolete it means there's already a new id
        if (isset($_SESSION['OBSOLETE'])) {
            return;
        }

        // Set current session to expire in 10 seconds
        $_SESSION['OBSOLETE'] = true;
        $_SESSION['EXPIRES'] = time() + 10;

        // Create new session without destroying the old one
        session_regenerate_id(false);

        // Grab current session ID and close both sessions to allow other scripts to use them
        $newSession = session_id();
        session_write_close();

        // Set session ID to the new one, and start it back up again
        session_id($newSession);
        session_start();

        // Now we unset the obsolete and expiration values for the session we want to keep
        unset($_SESSION['OBSOLETE']);
        unset($_SESSION['EXPIRES']);
    }
}