document.addEventListener('DOMContentLoaded', function() {
    // Initialize mood chart
    const moodChart = initMoodChart();
    
    // Mood selection handler
    const moodForm = document.getElementById('mood-form');
    if (moodForm) {
        moodForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            
            fetch('index.php?page=mood&action=record', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    updateMoodChart(moodChart);
                    showMessage('Mood recorded successfully!', 'success');
                } else {
                    showMessage(data.message || 'Failed to record mood', 'error');
                }
            });
        });
    }

    // Period selector
    const periodSelect = document.getElementById('period-select');
    if (periodSelect) {
        periodSelect.addEventListener('change', function() {
            updateMoodChart(moodChart, this.value);
        });
    }
});

function initMoodChart() {
    const ctx = document.getElementById('moodChart').getContext('2d');
    return new Chart(ctx, {
        type: 'line',
        data: {
            labels: [],
            datasets: [{
                label: 'Mood',
                data: [],
                borderColor: '#5d78ff',
                backgroundColor: 'rgba(93, 120, 255, 0.1)',
                tension: 0.3,
                fill: true
            }]
        },
        options: {
            scales: {
                y: {
                    min: 1,
                    max: 5,
                    ticks: {
                        callback: function(value) {
                            const moods = ['', '😞', '🙁', '😐', '🙂', '😊'];
                            return moods[value];
                        }
                    }
                }
            }
        }
    });
}

function updateMoodChart(chart, period = 'week') {
    fetch(`index.php?page=mood&action=data&period=${period}`)
        .then(response => response.json())
        .then(data => {
            chart.data.labels = data.map(item => item.date);
            chart.data.datasets[0].data = data.map(item => item.avg_mood);
            chart.update();
        });
}

function showMessage(message, type) {
    const messageDiv = document.createElement('div');
    messageDiv.className = `system-message ${type}`;
    messageDiv.innerHTML = `
        <p>${message}</p>
        <button class="close-message">&times;</button>
    `;
    
    document.body.prepend(messageDiv);
    setTimeout(() => messageDiv.remove(), 5000);
}