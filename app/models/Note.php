<?php
require_once __DIR__ . '/../config.php';

class Note {
    private $db;

    public function __construct() {
        $this->db = getDbConnection();
    }

    /**
     * Create a new note
     */
    public function create($userId, $title, $content, $priority = 2) {
        try {
            // Validate content length
            if (strlen($content) > MAX_NOTE_LENGTH) {
                return [
                    'success' => false,
                    'message' => 'Note content exceeds maximum allowed length'
                ];
            }

            // Check if priority level is allowed for user
            if (!$this->isPriorityAllowedForUser($userId, $priority)) {
                return [
                    'success' => false,
                    'message' => 'This priority level is only available for premium users'
                ];
            }

            $stmt = $this->db->prepare(
                "INSERT INTO notes (user_id, title, content, priority, created_at, updated_at) 
                 VALUES (?, ?, ?, ?, NOW(), NOW())"
            );
            $result = $stmt->execute([$userId, $title, $content, $priority]);

            if ($result) {
                return [
                    'success' => true,
                    'message' => 'Note created successfully',
                    'note_id' => $this->db->lastInsertId()
                ];
            } else {
                return ['success' => false, 'message' => 'Failed to create note'];
            }
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }

    /**
     * Update an existing note
     */
    public function update($noteId, $userId, $title, $content, $priority = null) {
        try {
            // Validate content length
            if (strlen($content) > MAX_NOTE_LENGTH) {
                return [
                    'success' => false,
                    'message' => 'Note content exceeds maximum allowed length'
                ];
            }

            // Check if user owns the note or has edit permission
            if (!$this->canEditNote($userId, $noteId)) {
                return [
                    'success' => false,
                    'message' => 'You do not have permission to edit this note'
                ];
            }

            // If priority is being updated, check if allowed for user
            if ($priority !== null && !$this->isPriorityAllowedForUser($userId, $priority)) {
                return [
                    'success' => false,
                    'message' => 'This priority level is only available for premium users'
                ];
            }

            // Build query based on whether priority is being updated
            if ($priority !== null) {
                $stmt = $this->db->prepare(
                    "UPDATE notes 
                     SET title = ?, content = ?, priority = ? 
                     WHERE id = ? AND (user_id = ? OR id IN (
                         SELECT note_id FROM note_shares 
                         WHERE user_id = ? AND permission = 'edit'
                     ))"
                );
                $result = $stmt->execute([$title, $content, $priority, $noteId, $userId, $userId]);
            } else {
                $stmt = $this->db->prepare(
                    "UPDATE notes 
                     SET title = ?, content = ? 
                     WHERE id = ? AND (user_id = ? OR id IN (
                         SELECT note_id FROM note_shares 
                         WHERE user_id = ? AND permission = 'edit'
                     ))"
                );
                $result = $stmt->execute([$title, $content, $noteId, $userId, $userId]);
            }

            if ($stmt->rowCount() > 0) {
                return ['success' => true, 'message' => 'Note updated successfully'];
            } else {
                return ['success' => false, 'message' => 'Failed to update note'];
            }
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }

    /**
     * Delete a note
     */
    public function delete($noteId, $userId) {
        try {
            $stmt = $this->db->prepare(
                "DELETE FROM notes WHERE id = ? AND user_id = ?"
            );
            $result = $stmt->execute([$noteId, $userId]);

            if ($stmt->rowCount() > 0) {
                return ['success' => true, 'message' => 'Note deleted successfully'];
            } else {
                return ['success' => false, 'message' => 'Failed to delete note or note not found'];
            }
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }

    /**
     * Get a single note by ID
     */
    public function getById($noteId, $userId) {
        $stmt = $this->db->prepare(
            "SELECT n.*, pl.label as priority_label 
             FROM notes n
             JOIN priority_levels pl ON n.priority = pl.id
             WHERE n.id = ? AND (
                 n.user_id = ? OR 
                 n.is_shared = TRUE OR 
                 n.id IN (SELECT note_id FROM note_shares WHERE user_id = ?)
             )"
        );
        $stmt->execute([$noteId, $userId, $userId]);

        return $stmt->fetch();
    }

    /**
     * Get all notes for a user
     */
    public function getUserNotes($userId, $orderBy = 'updated_at', $orderDir = 'DESC', $filters = []) {
        $allowedOrders = ['updated_at', 'created_at', 'priority', 'title'];
        $allowedDirs = ['ASC', 'DESC'];

        // Sanitize order parameters
        $orderBy = in_array($orderBy, $allowedOrders) ? $orderBy : 'updated_at';
        $orderDir = in_array($orderDir, $allowedDirs) ? $orderDir : 'DESC';

        // Base query
        $sql = "SELECT n.*, pl.label as priority_label 
                FROM notes n
                JOIN priority_levels pl ON n.priority = pl.id
                WHERE n.user_id = ?";
        $params = [$userId];

        // Apply filters
        if (!empty($filters['priority'])) {
            $sql .= " AND n.priority = ?";
            $params[] = $filters['priority'];
        }

        // Filter for notes created today
        if (!empty($filters['today']) && $filters['today'] === true) {
            $sql .= " AND DATE(n.created_at) = CURDATE()";
        }

        // Add order by clause
        $sql .= " ORDER BY n.$orderBy $orderDir";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    /**
     * Get notes shared with user
     */
    public function getSharedNotes($userId) {
        $stmt = $this->db->prepare(
            "SELECT n.*, pl.label as priority_label, u.username as owner_name, 
                    ns.permission
             FROM notes n
             JOIN priority_levels pl ON n.priority = pl.id
             JOIN users u ON n.user_id = u.id
             JOIN note_shares ns ON n.id = ns.note_id
             WHERE ns.user_id = ?
             ORDER BY n.updated_at DESC"
        );
        $stmt->execute([$userId]);

        return $stmt->fetchAll();
    }

    /**
     * Share a note with another user
     */
    public function shareNote($noteId, $ownerUserId, $targetUsername, $permission = 'view') {
        try {
            // Verify note ownership
            $stmt = $this->db->prepare("SELECT id FROM notes WHERE id = ? AND user_id = ?");
            $stmt->execute([$noteId, $ownerUserId]);

            if ($stmt->rowCount() == 0) {
                return [
                    'success' => false,
                    'message' => 'You do not have permission to share this note'
                ];
            }

            // Get target user ID
            $stmt = $this->db->prepare("SELECT id FROM users WHERE username = ?");
            $stmt->execute([$targetUsername]);
            $targetUser = $stmt->fetch();

            if (!$targetUser) {
                return ['success' => false, 'message' => 'User not found'];
            }

            $targetUserId = $targetUser['id'];

            // Don't allow sharing with yourself
            if ($targetUserId == $ownerUserId) {
                return [
                    'success' => false,
                    'message' => 'You cannot share a note with yourself'
                ];
            }

            // Mark note as shared
            $stmt = $this->db->prepare("UPDATE notes SET is_shared = TRUE WHERE id = ?");
            $stmt->execute([$noteId]);

            // Create or update share
            $stmt = $this->db->prepare(
                "INSERT INTO note_shares (note_id, user_id, permission, shared_at)
                 VALUES (?, ?, ?, NOW())
                 ON DUPLICATE KEY UPDATE permission = ?, shared_at = NOW()"
            );
            $result = $stmt->execute([$noteId, $targetUserId, $permission, $permission]);

            if ($result) {
                return ['success' => true, 'message' => 'Note shared successfully'];
            } else {
                return ['success' => false, 'message' => 'Failed to share note'];
            }
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }

    /**
     * Remove share from a note
     */
    public function removeShare($noteId, $ownerUserId, $targetUserId) {
        try {
            // Verify note ownership
            $stmt = $this->db->prepare("SELECT id FROM notes WHERE id = ? AND user_id = ?");
            $stmt->execute([$noteId, $ownerUserId]);

            if ($stmt->rowCount() == 0) {
                return [
                    'success' => false,
                    'message' => 'You do not have permission to modify sharing for this note'
                ];
            }

            // Delete share
            $stmt = $this->db->prepare(
                "DELETE FROM note_shares 
                 WHERE note_id = ? AND user_id = ?"
            );
            $result = $stmt->execute([$noteId, $targetUserId]);

            // Update is_shared if this was the last share
            $stmt = $this->db->prepare(
                "UPDATE notes SET is_shared = EXISTS(
                    SELECT 1 FROM note_shares WHERE note_id = ?
                ) WHERE id = ?"
            );
            $stmt->execute([$noteId, $noteId]);

            if ($result) {
                return ['success' => true, 'message' => 'Share removed successfully'];
            } else {
                return ['success' => false, 'message' => 'Failed to remove share'];
            }
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }

    /**
     * Check if user can edit a note
     */
    private function canEditNote($userId, $noteId) {
        $stmt = $this->db->prepare(
            "SELECT 1 
             FROM notes 
             WHERE id = ? AND (
                 user_id = ? OR 
                 id IN (SELECT note_id FROM note_shares WHERE user_id = ? AND permission = 'edit')
             )"
        );
        $stmt->execute([$noteId, $userId, $userId]);

        return $stmt->rowCount() > 0;
    }

    /**
     * Check if priority level is allowed for this user
     */
    private function isPriorityAllowedForUser($userId, $priorityId) {
        $stmt = $this->db->prepare(
            "SELECT 1
             FROM users u
             JOIN priority_levels pl ON (pl.id = ? AND (pl.premium_only = FALSE OR u.is_premium = TRUE))
             WHERE u.id = ?"
        );
        $stmt->execute([$priorityId, $userId]);

        return $stmt->rowCount() > 0;
    }
}