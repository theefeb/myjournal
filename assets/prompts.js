document.addEventListener('DOMContentLoaded', function() {
    // Load today's prompt
    loadTodaysPrompt();
    
    // New prompt button handler
    const newPromptBtn = document.getElementById('new-prompt-btn');
    if (newPromptBtn) {
        newPromptBtn.addEventListener('click', function(e) {
            e.preventDefault();
            getNewPrompt();
        });
    }
});

function loadTodaysPrompt() {
    fetch('index.php?page=prompt&action=today')
        .then(response => response.json())
        .then(data => {
            const promptContainer = document.querySelector('.prompt-card');
            if (promptContainer && data.content) {
                promptContainer.querySelector('p').textContent = data.content;
                if (data.id) {
                    promptContainer.querySelector('small').textContent = `Prompt #${data.id}`;
                }
            }
        });
}

function getNewPrompt() {
    const promptCard = document.querySelector('.prompt-card');
    if (promptCard) {
        promptCard.classList.add('loading');
    }
    
    fetch('index.php?page=prompt&action=random')
        .then(response => response.json())
        .then(data => {
            if (data.content) {
                const promptContainer = document.querySelector('.prompt-card');
                if (promptContainer) {
                    promptContainer.querySelector('p').textContent = data.content;
                    if (data.id) {
                        promptContainer.querySelector('small').textContent = `Prompt #${data.id}`;
                    }
                }
            }
        })
        .finally(() => {
            if (promptCard) {
                promptCard.classList.remove('loading');
            }
        });
}