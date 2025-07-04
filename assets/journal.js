document.addEventListener('DOMContentLoaded', function() {
    // Initialize journal functionality
    initJournalForm();
    initEntryActions();
    initSearchAndFilter();
    initTaggingSystem();
});

/**
 * Initialize journal entry form
 */
function initJournalForm() {
    const journalForm = document.getElementById('journal-form');
    if (journalForm) {
        // Auto-save draft every 30 seconds
        const autoSaveInterval = setInterval(saveDraft, 30000);
        
        // Form submission handler
        journalForm.addEventListener('submit', function(e) {
            clearInterval(autoSaveInterval);
            handleJournalSubmit(e);
        });
        
        // Prompt confirmation before leaving unsaved changes
        window.addEventListener('beforeunload', function(e) {
            if (hasUnsavedChanges()) {
                e.preventDefault();
                e.returnValue = 'You have unsaved changes. Are you sure you want to leave?';
            }
        });
    }
}

/**
 * Handle journal form submission
 */
function handleJournalSubmit(e) {
    e.preventDefault();
    const form = e.target;
    const formData = new FormData(form);
    
    // Add rich text content from editor if available
    const editorContent = document.querySelector('.editor-content');
    if (editorContent) {
        formData.set('content', editorContent.innerHTML);
    }
    
    // Show loading state
    const submitBtn = form.querySelector('button[type="submit"]');
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
    
    // Determine endpoint based on action
    const entryId = form.dataset.entryId || '';
    const endpoint = entryId 
        ? `index.php?page=journal&action=update&id=${entryId}`
        : 'index.php?page=journal&action=create';
    
    fetch(endpoint, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showMessage(data.message || 'Entry saved successfully!', 'success');
            
            // Redirect if new entry
            if (!entryId && data.entry_id) {
                window.location.href = `index.php?page=journal&action=edit&id=${data.entry_id}`;
            }
        } else {
            showMessage(data.message || 'Failed to save entry', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showMessage('An error occurred while saving', 'error');
    })
    .finally(() => {
        submitBtn.disabled = false;
        submitBtn.innerHTML = 'Save Entry';
    });
}

/**
 * Save draft automatically
 */
function saveDraft() {
    if (!hasUnsavedChanges()) return;
    
    const form = document.getElementById('journal-form');
    const formData = new FormData(form);
    
    fetch('index.php?page=journal&action=autosave', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showMessage('Draft auto-saved', 'info', 2000);
        }
    })
    .catch(error => {
        console.error('Auto-save failed:', error);
    });
}

/**
 * Check for unsaved changes
 */
function hasUnsavedChanges() {
    const form = document.getElementById('journal-form');
    if (!form) return false;
    
    // Compare current values with original values
    const originalTitle = form.dataset.originalTitle || '';
    const currentTitle = form.querySelector('[name="title"]').value;
    
    const originalContent = form.dataset.originalContent || '';
    const editorContent = document.querySelector('.editor-content');
    const currentContent = editorContent ? editorContent.innerHTML : form.querySelector('[name="content"]').value;
    
    return originalTitle !== currentTitle || originalContent !== currentContent;
}

/**
 * Initialize entry actions (edit, delete, etc.)
 */
function initEntryActions() {
    // Delete entry confirmation
    document.querySelectorAll('.delete-entry').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const entryId = this.dataset.entryId;
            confirmDeleteEntry(entryId);
        });
    });
    
    // Toggle entry preview/content
    document.querySelectorAll('.toggle-entry-content').forEach(btn => {
        btn.addEventListener('click', function() {
            const entryId = this.dataset.entryId;
            const contentEl = document.querySelector(`#entry-content-${entryId}`);
            if (contentEl) {
                contentEl.classList.toggle('expanded');
                this.textContent = contentEl.classList.contains('expanded') ? 
                    'Show Less' : 'Read More';
            }
        });
    });
}

/**
 * Confirm entry deletion
 */
function confirmDeleteEntry(entryId) {
    const modal = `
        <div class="modal-overlay active">
            <div class="modal">
                <h3>Delete Entry</h3>
                <p>Are you sure you want to delete this entry? This action cannot be undone.</p>
                <div class="modal-actions">
                    <button class="btn btn-cancel">Cancel</button>
                    <button class="btn btn-danger" id="confirm-delete">Delete</button>
                </div>
            </div>
        </div>
    `;
    
    document.body.insertAdjacentHTML('beforeend', modal);
    
    // Handle cancel
    document.querySelector('.btn-cancel').addEventListener('click', function() {
        document.querySelector('.modal-overlay').remove();
    });
    
    // Handle delete
    document.getElementById('confirm-delete').addEventListener('click', function() {
        deleteEntry(entryId);
    });
}

/**
 * Delete entry via AJAX
 */
function deleteEntry(entryId) {
    fetch(`index.php?page=journal&action=delete&id=${entryId}`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({
            csrf_token: document.querySelector('meta[name="csrf-token"]').content
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showMessage(data.message || 'Entry deleted successfully', 'success');
            // Remove entry from DOM or reload list
            const entryEl = document.querySelector(`.entry-card[data-entry-id="${entryId}"]`);
            if (entryEl) {
                entryEl.remove();
            } else {
                window.location.reload();
            }
        } else {
            showMessage(data.message || 'Failed to delete entry', 'error');
        }
        document.querySelector('.modal-overlay').remove();
    })
    .catch(error => {
        console.error('Error:', error);
        showMessage('An error occurred while deleting', 'error');
        document.querySelector('.modal-overlay').remove();
    });
}

/**
 * Initialize search and filter functionality
 */
function initSearchAndFilter() {
    const searchForm = document.getElementById('search-form');
    if (searchForm) {
        searchForm.addEventListener('submit', function(e) {
            e.preventDefault();
            applyFilters();
        });
        
        // Live search after 500ms of inactivity
        const searchInput = searchForm.querySelector('input[name="search"]');
        let searchTimeout;
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                applyFilters();
            }, 500);
        });
    }
    
    // Date filter changes
    document.querySelectorAll('.filter-date').forEach(input => {
        input.addEventListener('change', applyFilters);
    });
    
    // Tag filter changes
    document.querySelectorAll('.filter-tag').forEach(checkbox => {
        checkbox.addEventListener('change', applyFilters);
    });
}

/**
 * Apply filters and refresh entry list
 */
function applyFilters() {
    const form = document.getElementById('search-form');
    const formData = new FormData(form);
    
    // Get selected tags
    const selectedTags = [];
    document.querySelectorAll('.filter-tag:checked').forEach(checkbox => {
        selectedTags.push(checkbox.value);
    });
    if (selectedTags.length) {
        formData.set('tags', selectedTags.join(','));
    }
    
    // Show loading state
    const entriesContainer = document.getElementById('entries-container');
    if (entriesContainer) {
        entriesContainer.classList.add('loading');
    }
    
    fetch('index.php?page=journal&action=filter', {
        method: 'POST',
        body: formData
    })
    .then(response => response.text())
    .then(html => {
        if (entriesContainer) {
            entriesContainer.innerHTML = html;
            initEntryActions(); // Reinitialize actions for new elements
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showMessage('Failed to apply filters', 'error');
    })
    .finally(() => {
        if (entriesContainer) {
            entriesContainer.classList.remove('loading');
        }
    });
}

e.detail.tagify = undefined;

/**
 * Initialize tagging system
 */
function initTaggingSystem() {
    const tagInputs = document.querySelectorAll('.tag-input');
    
    tagInputs.forEach(input => {
        // Initialize Tagify
        new Tagify(input, {
            whitelist: JSON.parse(input.dataset.tags || '[]'),
            dropdown: {
                enabled: 1,
                maxItems: 5,
                closeOnSelect: false
            },
            editTags: false,
            originalInputValueFormat: values => values.map(item => item.value).join(',')
        });
        
        // Load tags asynchronously
        input.addEventListener('input', function(e) {
            const tagify = e.detail.tagify;
            tagify.settings = undefined;
            const value = e.detail.value;
            
            if (value.length < 2) return;
            
            fetch(`index.php?page=tags&action=search&query=${encodeURIComponent(value)}`)
            .then(response => response.json())
            .then(tags => {
                tagify.settings.whitelist = tags;
                tagify.dropdown.show.call(tagify, value);
            });
        });
    });
}

/**
 * Show flash message
 */
function showMessage(message, type = 'info', timeout = 5000) {
    const messageEl = document.createElement('div');
    messageEl.className = `alert alert-${type}`;
    messageEl.innerHTML = `
        <p>${message}</p>
        <button class="close-alert">&times;</button>
    `;
    
    const messagesContainer = document.getElementById('messages-container') || 
                             document.querySelector('body');
    messagesContainer.prepend(messageEl);
    
    // Auto-hide after timeout
    if (timeout) {
        setTimeout(() => {
            messageEl.remove();
        }, timeout);
    }
    
    // Close button
    messageEl.querySelector('.close-alert').addEventListener('click', function() {
        messageEl.remove();
    });
}

/**
 * Initialize rich text editor
 */
function initEditor() {
    const editor = document.querySelector('.editor-content');
    if (!editor) return;
    
    // Simple rich text functionality
    document.querySelectorAll('.editor-toolbar button').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const command = this.dataset.command;
            
            if (command === 'h1' || command === 'h2' || command === 'p') {
                document.execCommand('formatBlock', false, command);
            } else {
                document.execCommand(command, false, null);
            }
            
            editor.focus();
        });
    });
}

// Initialize editor when DOM is loaded
document.addEventListener('DOMContentLoaded', initEditor);