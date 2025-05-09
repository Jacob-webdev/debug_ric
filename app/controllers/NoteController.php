<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../models/Note.php';
require_once __DIR__ . '/../auth/session.php';

class NoteController {
    private $noteModel;
    private $session;

    public function __construct() {
        $this->noteModel = new Note();
        $this->session = new Session();

        // Check if user is logged in
        if (!$this->session->isLoggedIn()) {
            $this->redirectToLogin();
        }
    }

    /**
     * Create a new note
     */
    public function createNote($title, $content, $priority = 2) {
        $userId = $this->session->getUserId();

        // Validate input
        if (empty($title) || empty($content)) {
            return [
                'success' => false,
                'message' => 'Title and content are required'
            ];
        }

        return $this->noteModel->create($userId, $title, $content, $priority);
    }

    /**
     * Update an existing note
     */
    public function updateNote($noteId, $title, $content, $priority = null) {
        $userId = $this->session->getUserId();

        // Validate input
        if (empty($title) || empty($content)) {
            return [
                'success' => false,
                'message' => 'Title and content are required'
            ];
        }

        return $this->noteModel->update($noteId, $userId, $title, $content, $priority);
    }

    /**
     * Delete a note
     */
    public function deleteNote($noteId) {
        $userId = $this->session->getUserId();

        return $this->noteModel->delete($noteId, $userId);
    }

    /**
     * Get a single note by ID
     */
    public function getNote($noteId) {
        $userId = $this->session->getUserId();

        return $this->noteModel->getById($noteId, $userId);
    }

    /**
     * Get all notes for the current user
     */
    public function getUserNotes($orderBy = 'updated_at', $orderDir = 'DESC', $filters = []) {
        $userId = $this->session->getUserId();

        return $this->noteModel->getUserNotes($userId, $orderBy, $orderDir, $filters);
    }

    /**
     * Get notes created today
     */
    public function getTodayNotes($orderBy = 'created_at', $orderDir = 'DESC') {
        $userId = $this->session->getUserId();

        $filters = ['today' => true];
        return $this->noteModel->getUserNotes($userId, $orderBy, $orderDir, $filters);
    }

    /**
     * Get notes shared with the current user
     */
    public function getSharedNotes() {
        $userId = $this->session->getUserId();

        return $this->noteModel->getSharedNotes($userId);
    }

    /**
     * Share a note with another user
     */
    public function shareNote($noteId, $targetUsername, $permission = 'view') {
        $userId = $this->session->getUserId();

        return $this->noteModel->shareNote($noteId, $userId, $targetUsername, $permission);
    }

    /**
     * Redirect to login if user is not authenticated
     */
    private function redirectToLogin() {
        header('Location: /public/login.php');
        exit;
    }
}