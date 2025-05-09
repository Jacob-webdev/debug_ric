<?php
require_once '../app/auth/session.php';
require_once '../app/middlewares/AuthMiddleware.php';
require_once '../app/controllers/NoteController.php';

// Initialize auth middleware
$authMiddleware = new AuthMiddleware();
$authMiddleware->requireLogin();

// Initialize session
$session = new Session();

// Initialize note controller
$noteController = new NoteController();

// Get notes shared with the user
$sharedNotes = $noteController->getSharedNotes();

// Define permission class mappings
$permissionClasses = [
    'view' => 'permission-view',
    'edit' => 'permission-edit'
];
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shared Notes | Ricordella</title>
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <link rel="stylesheet" href="assets/css/shared.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <header id="redirect-top">
        <nav id="nav-bar">
            <div id="nav-logo">
                <a href="#redirect-top" id="link-logo">
                    <img src="assets/img/logo-nobg.png" id="logo" fetchpriority="high" loading="eager" alt="Logo"/>
                </a>
            </div>
            <div id="nav-links">
                <ul>
                    <li><a href="dashboard.php">Home</a></li>
                    <li><a href="#" id="open-popup-link">New <i class="fas fa-plus-circle" style="color: green;"></i></a></li>
                    <li><a href="today.php">Today</a></li>
                    <li><a href="shared.php" class="active">Shared</a></li>
                    <?php if (!$session->isPremium()): ?>
                        <li><a href="premium.php">Premium</a></li>
                    <?php endif; ?>
                    <?php if ($session->isAdmin()): ?>
                        <li><a href="admin.php">Admin</a></li>
                    <?php endif; ?>
                </ul>
            </div>
            <div class="search-box">
                <input class="search-txt" type="text" id="search-notes" placeholder="Type to Search">
                <a class="search-btn" href="#"><i class="fas fa-search"></i></a>
            </div>
            <div class="user-icon">
                <div class="user-dropdown">
                    <button class="user-dropbtn" id="user-menu-btn">
                        <i class="fas fa-user-circle"></i>
                        <span id="username-display"><?= htmlspecialchars($session->getUsername()) ?></span>
                        <i class="fas fa-caret-down"></i>
                    </button>
                    <div class="user-dropdown-content">
                        <a href="../app/auth/logout.php" id="logout-btn">Logout</a>
                        <a href="profile.php" id="profile-link">Profile</a>
                        <a href="settings.php" id="settings-link">Settings</a>
                    </div>
                </div>
            </div>
        </nav>
    </header>

    <main>
        <div class="filters-container">
            <h2>Notes Shared With You</h2>
            <div class="search-filter">
                <input type="text" id="local-search" placeholder="Filter shared notes...">
            </div>
        </div>

        <section class="notes-exposing">
            <div class="expo-container">
                <?php if (empty($sharedNotes)): ?>
                    <div class="no-notes">
                        <p>You don't have any notes shared with you yet.</p>
                        <p>When someone shares a note with you, it will appear here.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($sharedNotes as $note): ?>
                        <div class="note-container" data-note-id="<?= $note['id'] ?>">
                            <div class="note-header">
                                <h3><?= htmlspecialchars($note['title']) ?></h3>
                                <div class="permission-badge <?= $permissionClasses[$note['permission']] ?>">
                                    <?= ucfirst($note['permission']) ?>
                                </div>
                            </div>
                            <div class="owner-info">
                                <i class="fas fa-user"></i> <?= htmlspecialchars($note['owner_name']) ?>
                            </div>
                            <div class="note-priority">
                                <span class="priority-badge"><?= htmlspecialchars($note['priority_label']) ?></span>
                            </div>
                            <div class="note-content">
                                <?= nl2br(htmlspecialchars($note['content'])) ?>
                            </div>
                            <div class="note-footer">
                                <div class="note-dates">
                                    <small>Created: <?= date('Y-m-d H:i', strtotime($note['created_at'])) ?></small>
                                    <small>Updated: <?= date('Y-m-d H:i', strtotime($note['updated_at'])) ?></small>
                                </div>
                                <div class="note-actions">
                                    <?php if ($note['permission'] === 'edit'): ?>
                                        <button class="btn-edit" data-note-id="<?= $note['id'] ?>"><i class="fas fa-edit"></i></button>
                                    <?php endif; ?>
                                    <button class="btn-view" data-note-id="<?= $note['id'] ?>"><i class="fas fa-eye"></i></button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>

        <!-- View Note Popup -->
        <div id="view-popup" class="popup">
            <div class="popup-content">
                <span id="close-view-popup-btn" class="close-btn">&times;</span>
                <h2 id="view-note-title"></h2>
                <div class="view-note-info">
                    <div class="view-note-owner"></div>
                    <div class="view-note-priority"></div>
                </div>
                <div class="view-note-content"></div>
                <div class="view-note-dates">
                    <div class="creation-date"></div>
                    <div class="updated-date"></div>
                </div>
            </div>
        </div>

        <!-- Edit Note Popup (for edit permission) -->
        <div id="edit-popup" class="popup">
            <div class="popup-content">
                <span id="close-edit-popup-btn" class="close-btn">&times;</span>
                <h2>Edit Shared Note</h2>
                <form id="edit-note-form" class="note-form">
                    <input type="hidden" id="edit-note-id" name="note_id" value="">

                    <div class="form-group">
                        <label for="edit-note-title">Title</label>
                        <input type="text" id="edit-note-title" name="title" required>
                    </div>

                    <div class="form-group">
                        <label for="edit-note-content">Content</label>
                        <textarea id="edit-note-content" name="content" required></textarea>
                        <div id="edit-character-count">1500</div>
                    </div>

                    <button type="submit" class="styled-button">
                        <span>Save Changes</span>
                    </button>
                </form>
            </div>
        </div>
    </main>

    <footer class="footer">
        <!-- Same as dashboard.php -->
    </footer>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // View Note Popup
            const viewPopup = document.getElementById('view-popup');
            const closeViewPopupBtn = document.getElementById('close-view-popup-btn');

            // Edit Note Popup
            const editPopup = document.getElementById('edit-popup');
            const closeEditPopupBtn = document.getElementById('close-edit-popup-btn');
            const editNoteForm = document.getElementById('edit-note-form');
            const editNoteContent = document.getElementById('edit-note-content');
            const editCharacterCount = document.getElementById('edit-character-count');

            // Local search
            const localSearch = document.getElementById('local-search');

            // View buttons
            document.querySelectorAll('.btn-view').forEach(button => {
                button.addEventListener('click', function() {
                    const noteId = this.getAttribute('data-note-id');

                    // Fetch note data
                    fetch(`../app/api/notes/get.php?id=${noteId}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Populate view popup
                            document.getElementById('view-note-title').textContent = data.note.title;
                            document.querySelector('.view-note-owner').innerHTML = `<i class="fas fa-user"></i> Shared by: ${data.note.owner_name || 'Unknown'}`;
                            document.querySelector('.view-note-priority').innerHTML = `<i class="fas fa-flag"></i> Priority: ${data.note.priority_label}`;
                            document.querySelector('.view-note-content').innerHTML = data.note.content.replace(/\n/g, '<br>');
                            document.querySelector('.creation-date').innerHTML = `<i class="fas fa-calendar-plus"></i> Created: ${formatDate(data.note.created_at)}`;
                            document.querySelector('.updated-date').innerHTML = `<i class="fas fa-calendar-check"></i> Updated: ${formatDate(data.note.updated_at)}`;

                            // Show the popup
                            viewPopup.style.display = 'flex';
                        } else {
                            alert('Error: ' + data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('An error occurred. Please try again.');
                    });
                });
            });

            // Edit buttons
            document.querySelectorAll('.btn-edit').forEach(button => {
                button.addEventListener('click', function() {
                    const noteId = this.getAttribute('data-note-id');

                    // Fetch note data
                    fetch(`../app/api/notes/get.php?id=${noteId}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Populate form with note data
                            document.getElementById('edit-note-id').value = data.note.id;
                            document.getElementById('edit-note-title').value = data.note.title;
                            document.getElementById('edit-note-content').value = data.note.content;

                            // Update character count
                            const remaining = 1500 - data.note.content.length;
                            editCharacterCount.textContent = remaining;

                            // Show the popup
                            editPopup.style.display = 'flex';
                        } else {
                            alert('Error: ' + data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('An error occurred. Please try again.');
                    });
                });
            });

            // Close view popup
            closeViewPopupBtn.addEventListener('click', function() {
                viewPopup.style.display = 'none';
            });

            // Close edit popup
            closeEditPopupBtn.addEventListener('click', function() {
                editPopup.style.display = 'none';
            });

            // Character count for edit note
            if (editNoteContent) {
                editNoteContent.addEventListener('input', function() {
                    const MAX_LENGTH = 1500;
                    const remaining = MAX_LENGTH - this.value.length;
                    editCharacterCount.textContent = remaining;

                    if (remaining < 0) {
                        editCharacterCount.classList.add('error');
                        editNoteContent.classList.add('error');
                    } else {
                        editCharacterCount.classList.remove('error');
                        editNoteContent.classList.remove('error');
                    }
                });
            }

            // Edit note form submit
            if (editNoteForm) {
                editNoteForm.addEventListener('submit', function(e) {
                    e.preventDefault();

                    const formData = new FormData(this);

                    fetch('../app/api/notes/update.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            editPopup.style.display = 'none';

                            // Reload the page to show the updated notes
                            window.location.reload();
                        } else {
                            alert('Error: ' + data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('An error occurred. Please try again.');
                    });
                });
            }

            // Local search functionality
            if (localSearch) {
                localSearch.addEventListener('input', function() {
                    const searchTerm = this.value.toLowerCase();

                    document.querySelectorAll('.note-container').forEach(note => {
                        const title = note.querySelector('h3').textContent.toLowerCase();
                        const content = note.querySelector('.note-content').textContent.toLowerCase();
                        const owner = note.querySelector('.owner-info').textContent.toLowerCase();

                        if (title.includes(searchTerm) || content.includes(searchTerm) || owner.includes(searchTerm)) {
                            note.style.display = 'block';
                        } else {
                            note.style.display = 'none';
                        }
                    });
                });
            }

            // Helper Functions
            function formatDate(dateString) {
                const date = new Date(dateString);
                return date.toLocaleString();
            }
        });
    </script>
</body>
</html>