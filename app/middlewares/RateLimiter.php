<?php
require_once __DIR__ . '/../config.php';

class RateLimiter {
    private $db;
    private $maxAttempts;
    private $decayMinutes;
    private $ipAddress;

    public function __construct($maxAttempts = 60, $decayMinutes = 1) {
        $this->db = getDbConnection();
        $this->maxAttempts = $maxAttempts;
        $this->decayMinutes = $decayMinutes;
        $this->ipAddress = $this->getIpAddress();
    }

    /**
     * Check if the request exceeds the rate limit
     */
    public function tooManyAttempts($key) {
        $attempts = $this->attempts($key);
        return $attempts >= $this->maxAttempts;
    }

    /**
     * Get the number of attempts for the given key
     */
    public function attempts($key) {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) as attempts
             FROM rate_limit
             WHERE ip_address = ? 
               AND action_key = ?
               AND created_at > DATE_SUB(NOW(), INTERVAL ? MINUTE)"
        );
        $stmt->execute([$this->ipAddress, $key, $this->decayMinutes]);
        $result = $stmt->fetch();

        return $result ? (int) $result['attempts'] : 0;
    }

    /**
     * Increment the counter for a given key
     */
    public function hit($key) {
        $stmt = $this->db->prepare(
            "INSERT INTO rate_limit (ip_address, action_key, created_at)
             VALUES (?, ?, NOW())"
        );
        $stmt->execute([$this->ipAddress, $key]);
    }

    /**
     * Reset the counter for a given key
     */
    public function clear($key) {
        $stmt = $this->db->prepare(
            "DELETE FROM rate_limit
             WHERE ip_address = ? AND action_key = ?"
        );
        $stmt->execute([$this->ipAddress, $key]);
    }

    /**
     * Get the remaining number of attempts
     */
    public function remaining($key) {
        return $this->maxAttempts - $this->attempts($key);
    }

    /**
     * Get client IP address
     */
    private function getIpAddress() {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }

        return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '0.0.0.0';
    }
}

// Create table if it doesn't exist
function createRateLimitTable() {
    $db = getDbConnection();
    $sql = "CREATE TABLE IF NOT EXISTS rate_limit (
        id INT AUTO_INCREMENT PRIMARY KEY,
        ip_address VARCHAR(45) NOT NULL,
        action_key VARCHAR(255) NOT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_ip_key (ip_address, action_key)
    ) ENGINE=InnoDB CHARSET=utf8mb4";

    $db->exec($sql);
}

// Call this function once to ensure the table exists
createRateLimitTable();