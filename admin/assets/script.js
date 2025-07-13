// Update date & time every second
setInterval(() => {
    const now = new Date();
    document.getElementById('datetime').textContent =
        now.toLocaleDateString() + ' ' + now.toLocaleTimeString();
}, 1000);

// Load page content dynamically on sidebar click
document.querySelectorAll('.sidebar a').forEach(link => {
    link.addEventListener('click', e => {
        e.preventDefault();
        const page = link.getAttribute('data-page');
        if (page) {
            // Highlight active
            document.querySelectorAll('.sidebar a').forEach(l => l.classList.remove('active'));
            link.classList.add('active');

            // Load page via fetch
            document.getElementById('main-content').innerHTML = '<p>Loading...</p>';
            fetch(page)
                .then(res => res.text())
                .then(html => {
                    document.getElementById('main-content').innerHTML = html;
                });

            // Set auto-refresh if logs page
            setAutoRefresh(page);
        }
    });
});

// Auto-refresh logs page every 30 seconds
let refreshInterval = null;
function setAutoRefresh(page) {
    if (refreshInterval) clearInterval(refreshInterval);
    if (page === 'view_logs.php') {
        refreshInterval = setInterval(() => {
            fetch(page)
                .then(res => res.text())
                .then(html => {
                    document.getElementById('main-content').innerHTML = html;
                });
        }, 30000);
    }
}

// Start auto-refresh on initial page load
setAutoRefresh('view_logs.php');
