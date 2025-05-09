document.addEventListener('DOMContentLoaded', function() {
    // Elements
    const popup = document.getElementById('popup');
    const sharePopup = document.getElementById('share-popup');
    const deletePopup = document.getElementById('delete-popup');
    const openPopupLink = document.getElementById('open-popup-link');
    const closePopupBtn = document.getElementById('close-popup-btn');
    const closeSharePopupBtn = document.getElementById('close-share-popup-btn');
    const cancelDeleteBtn = document.getElementById('cancel-delete');
    const noteForm = document.getElementById('note-form');
    const shareNoteForm = document.getElementById('share-note-form');
    const noteContent = document.getElementById('note-content');
    const characterCount = document.getElementById('character-count');
    const createFirstNoteBtn = document.getElementById('create-first-note');

    // Current note for deletion
    let currentNoteIdToDelete = null;

    // Max note length
    const MAX_NOTE_LENGTH = 1500;

    // Open new note popup
    if (openPopupLink) {
        openPopupLink.addEventListener('click', function(event) {
            event.preventDefault();
            resetNoteForm();
            document.getElementById('popup-title').textContent = 'New Note';
            document.getElementById('date-fields').style.display = 'none';
            popup.style.display = 'flex';
        });
    }

    // First note creation button
    if (createFirstNoteBtn) {
        createFirstNoteBtn.addEventListener('click', function() {
            resetNoteForm();
            document.getElementById('popup-title').textContent = 'New Note';
            document.getElementById('date-fields').style.display = 'none';
            popup.style.display = 'flex';
        });
    }

    // Close note popup
    if (closePopupBtn) {
        closePopupBtn.addEventListener('click', function() {
            popup.style.display = 'none';
        });
    }

    // Close share popup
    if (closeSharePopupBtn) {
        closeSharePopupBtn.addEventListener('click', function() {
            sharePopup.style.display = 'none';
        });
    }

    // Close delete popup
    if (cancelDeleteBtn) {
        cancelDeleteBtn.addEventListener('click', function() {
            deletePopup.style.display = 'none';
            currentNoteIdToDelete = null;
        });
    }

    // Character count for note content
    if (noteContent) {
        noteContent.addEventListener('input', function() {
            const remaining = MAX_NOTE_LENGTH - this.value.length;
            characterCount.textContent = remaining;

            if (remaining < 0) {
                characterCount.classList.add('error');
                noteContent.classList.add('error');
            } else {
                characterCount.classList.remove('error');
                noteContent.classList.remove('error');
            }
        });
    }

    // Save note
    if (noteForm) {
        noteForm.addEventListener('submit', function(e) {
            e.preventDefault();

            const formData = new FormData(this);
            const noteId = formData.get('note_id');
            const endpoint = noteId ? '../app/api/notes/update.php' : '../app/api/notes/create.php';

            fetch(endpoint, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    popup.style.display = 'none';

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

    // Share note
    if (shareNoteForm) {
        shareNoteForm.addEventListener('submit', function(e) {
            e.preventDefault();

            const formData = new FormData(this);

            fetch('../app/api/notes/share.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Reload the shares list
                    loadShares(formData.get('note_id'));

                    // Clear the username input
                    document.getElementById('share-username').value = '';

                    // Show success message
                    showMessage('share-note-form', 'Note shared successfully!', 'success');
                } else {
                    showMessage('share-note-form', 'Error: ' + data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showMessage('share-note-form', 'An error occurred. Please try again.', 'error');
            });
        });
    }

    // Edit note buttons
    document.querySelectorAll('.btn-edit').forEach(button => {
        button.addEventListener('click', function() {
            const noteId = this.getAttribute('data-note-id');

            // Fetch note data
            fetch(`../app/api/notes/get.php?id=${noteId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Populate form with note data
                    document.getElementById('note-id').value = data.note.id;
                    document.getElementById('note-title').value = data.note.title;
                    document.getElementById('note-content').value = data.note.content;
                    document.getElementById('note-priority').value = data.note.priority;

                    // Show dates
                    document.getElementById('date-fields').style.display = 'flex';
                    document.getElementById('creation-date').value = formatDate(data.note.created_at);
                    document.getElementById('last-modification').value = formatDate(data.note.updated_at);

                    // Update character count
                    const remaining = MAX_NOTE_LENGTH - data.note.content.length;
                    characterCount.textContent = remaining;

                    // Update popup title
                    document.getElementById('popup-title').textContent = 'Edit Note';

                    // Show the popup
                    popup.style.display = 'flex';
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

    // Delete note buttons
    document.querySelectorAll('.btn-delete').forEach(button => {
        button.addEventListener('click', function() {
            const noteId = this.getAttribute('data-note-id');
            currentNoteIdToDelete = noteId;
            deletePopup.style.display = 'flex';
        });
    });

    // Confirm delete button
    document.getElementById('confirm-delete').addEventListener('click', function() {
        if (currentNoteIdToDelete) {
            fetch('../app/api/notes/delete.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    note_id: currentNoteIdToDelete
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Hide the popup
                    deletePopup.style.display = 'none';

                    // Remove the note from the UI
                    const noteElement = document.querySelector(`.note-container[data-note-id="${currentNoteIdToDelete}"]`);
                    if (noteElement) {
                        noteElement.remove();
                    }

                    // Reset current note ID
                    currentNoteIdToDelete = null;

                    // If no notes are left, show the "no notes" message
                    if (document.querySelectorAll('.note-container').length === 0) {
                        const noNotesElement = document.createElement('div');
                        noNotesElement.className = 'no-notes';
                        noNotesElement.innerHTML = `
                            <p>You don't have any notes yet. Create your first note!</p>
                            <button class="btn-create-note" id="create-first-note">Create Note</button>
                        `;
                        document.querySelector('.expo-container').appendChild(noNotesElement);

                        // Add event listener to the new button
                        document.getElementById('create-first-note').addEventListener('click', function() {
                            resetNoteForm();
                            document.getElementById('popup-title').textContent = 'New Note';
                            document.getElementById('date-fields').style.display = 'none';
                            popup.style.display = 'flex';
                        });
                    }
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred. Please try again.');
            });
        }
    });

    // Share note buttons
    document.querySelectorAll('.btn-share').forEach(button => {
        button.addEventListener('click', function() {
            const noteId = this.getAttribute('data-note-id');
            document.getElementById('share-note-id').value = noteId;

            // Load current shares
            loadShares(noteId);

            // Show share popup
            sharePopup.style.display = 'flex';
        });
    });

    // Search functionality
    const searchInput = document.getElementById('search-notes');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();

            document.querySelectorAll('.note-container').forEach(note => {
                const title = note.querySelector('h3').textContent.toLowerCase();
                const content = note.querySelector('.note-content').textContent.toLowerCase();

                if (title.includes(searchTerm) || content.includes(searchTerm)) {
                    note.style.display = 'block';
                } else {
                    note.style.display = 'none';
                }
            });
        });
    }

    // Helper Functions
    function resetNoteForm() {
        noteForm.reset();
        document.getElementById('note-id').value = '';
        characterCount.textContent = MAX_NOTE_LENGTH;
        characterCount.classList.remove('error');
        noteContent.classList.remove('error');
    }

    function formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleString();
    }

    function loadShares(noteId) {
        fetch(`../app/api/notes/get_shares.php?id=${noteId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const sharesList = document.getElementById('shares-list');
                sharesList.innerHTML = '';

                if (data.shares.length === 0) {
                    sharesList.innerHTML = '<p>This note is not shared with anyone yet.</p>';
                    return;
                }

                data.shares.forEach(share => {
                    const shareItem = document.createElement('div');
                    shareItem.className = 'share-item';
                    shareItem.innerHTML = `
                        <div class="share-info">
                            <span class="share-username">${share.username}</span>
                            <span class="share-permission ${share.permission}">${share.permission}</span>
                        </div>
                        <button class="btn-remove-share" data-user-id="${share.user_id}" data-note-id="${noteId}">
                            <i class="fas fa-times"></i>
                        </button>
                    `;
                    sharesList.appendChild(shareItem);

                    // Add event listener to remove share button
                    shareItem.querySelector('.btn-remove-share').addEventListener('click', function() {
                        const userId = this.getAttribute('data-user-id');
                        const noteId = this.getAttribute('data-note-id');

                        fetch('../app/api/notes/remove_share.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify({
                                note_id: noteId,
                                user_id: userId
                            })
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                // Reload shares
                                loadShares(noteId);
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
            } else {
                console.error('Error:', data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
        });
    }

    function showMessage(formId, message, type) {
        const form = document.getElementById(formId);

        // Check if message already exists and remove it
        const existingMessage = form.querySelector('.message');
        if (existingMessage) {
            existingMessage.remove();
        }

        // Create message element
        const messageElement = document.createElement('div');
        messageElement.className = `message ${type}`;
        messageElement.textContent = message;

        // Insert message at the top of the form
        form.insertBefore(messageElement, form.firstChild);

        // Remove message after 3 seconds
        setTimeout(() => {
            messageElement.remove();
        }, 3000);
    }
});

// Utility Functions for URL parameters
function applySorting() {
    const sortSelect = document.getElementById('sort-select');
    const orderDir = getQueryParam('order_dir') || 'DESC';
    const priority = getQueryParam('priority') || '';

    window.location.href = updateQueryParams({
        order_by: sortSelect.value,
        order_dir: orderDir,
        priority: priority
    });
}

function toggleSortDirection() {
    const orderBy = getQueryParam('order_by') || 'updated_at';
    const currentDir = getQueryParam('order_dir') || 'DESC';
    const priority = getQueryParam('priority') || '';
    const newDir = currentDir === 'DESC' ? 'ASC' : 'DESC';

    window.location.href = updateQueryParams({
        order_by: orderBy,
        order_dir: newDir,
        priority: priority
    });
}

function applyPriorityFilter() {
    const priorityFilter = document.getElementById('priority-filter');
    const orderBy = getQueryParam('order_by') || 'updated_at';
    const orderDir = getQueryParam('order_dir') || 'DESC';

    window.location.href = updateQueryParams({
        order_by: orderBy,
        order_dir: orderDir,
        priority: priorityFilter.value
    });
}

function getQueryParam(param) {
    const urlParams = new URLSearchParams(window.location.search);
    return urlParams.get(param);
}

function updateQueryParams(params) {
    const url = new URL(window.location.href);
    Object.keys(params).forEach(key => {
        if (params[key]) {
            url.searchParams.set(key, params[key]);
        } else {
            url.searchParams.delete(key);
        }
    });
    return url.href;
}