document.addEventListener('DOMContentLoaded', function() {
    // Mobile menu toggle
    document.getElementById('mobileMenuBtn').addEventListener('click', function() {
        document.getElementById('sidebar').classList.toggle('open');
    });

    // Chart initialization
    if (document.getElementById('collectionChart')) {
        initCharts();
    }

    // Page navigation
    document.querySelectorAll('[data-page]').forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            showPage(this.getAttribute('data-page'));
        });
    });
});

function initCharts() {
    // Collection chart
    new Chart(document.getElementById('collectionChart').getContext('2d'), {
        type: 'line',
        data: {
            labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
            datasets: [{
                label: 'Milk Collected (L)',
                data: [120, 190, 170, 210, 180, 240, 195],
                backgroundColor: 'rgba(44, 123, 229, 0.2)',
                borderColor: 'rgba(44, 123, 229, 1)',
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });

    // Quality chart
    new Chart(document.getElementById('qualityChart').getContext('2d'), {
        type: 'doughnut',
        data: {
            labels: ['Grade A', 'Grade B', 'Grade C'],
            datasets: [{
                data: [65, 25, 10],
                backgroundColor: [
                    '#00d97e',
                    '#f6c343',
                    '#e63757'
                ]
            }]
        }
    });
}