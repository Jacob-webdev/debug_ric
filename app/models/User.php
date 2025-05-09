<?php
require_once __DIR__ . '/../config.php';

class User {
    private $db;

    public function __construct() {
        $this->db = getDbConnection();
    }

    /**
     * Register a new user
     */
    public function register($username, $email, $password) {
        // Check if email or username already exists
        $stmt = $this->db->prepare("SELECT id FROM users WHERE email = ? OR username = ? LIMIT 1");
        $stmt->execute([$email, $username]);

        if ($stmt->rowCount() > 0) {
            return ['success' => false, 'message' => 'Email or username already in use'];
        }

        // Hash the password
        $password_hash = password_hash($password, PASSWORD_DEFAULT);

        try {
            $stmt = $this->db->prepare(
                "INSERT INTO users (username, email, password_hash, created_at) 
                 VALUES (?, ?, ?, NOW())"
            );
            $result = $stmt->execute([$username, $email, $password_hash]);

            if ($result) {
                return [
                    'success' => true,
                    'message' => 'User registered successfully',
                    'user_id' => $this->db->lastInsertId()
                ];
            } else {
                return ['success' => false, 'message' => 'Registration failed'];
            }
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }

    /**
     * Login user
     */
    public function login($email_or_username, $password) {
        $stmt = $this->db->prepare(
            "SELECT id, username, email, password_hash, is_premium, role 
             FROM users 
             WHERE email = ? OR username = ? 
             LIMIT 1"
        );
        $stmt->execute([$email_or_username, $email_or_username]);

        if ($stmt->rowCount() == 1) {
            $user = $stmt->fetch();

            if (password_verify($password, $user['password_hash'])) {
                // Remove password hash from session data
                unset($user['password_hash']);

                return [
                    'success' => true,
                    'message' => 'Login successful',
                    'user' => $user
                ];
            }
        }

        return ['success' => false, 'message' => 'Invalid credentials'];
    }

    /**
     * Get user by ID
     */
    public function getUserById($id) {
        $stmt = $this->db->prepare(
            "SELECT id, username, email, is_premium, role, created_at 
             FROM users 
             WHERE id = ?"
        );
        $stmt->execute([$id]);

        return $stmt->fetch();
    }

    /**
     * Update user premium status
     */
    public function updatePremiumStatus($userId, $isPremium) {
        $stmt = $this->db->prepare("UPDATE users SET is_premium = ? WHERE id = ?");
        return $stmt->execute([$isPremium ? 1 : 0, $userId]);
    }

    /**
     * Get all users (for admin)
     */
    public function getAllUsers() {
        $stmt = $this->db->prepare(
            "SELECT id, username, email, is_premium, role, created_at 
             FROM users 
             ORDER BY created_at DESC"
        );
        $stmt->execute();

        return $stmt->fetchAll();
    }
}