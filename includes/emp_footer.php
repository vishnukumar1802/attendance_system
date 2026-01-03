</main>
</div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    const currentPath = window.location.pathname.split("/").pop();
    document.querySelectorAll('.sidebar .nav-link').forEach(link => {
        if (link.getAttribute('href') === currentPath) {
            link.classList.add('active');
        }
    });
</script>
<script>
    // Global Notification Polling (Employee)
    function pollNotifications() {
        if (!document.getElementById('notif-badge')) return;

        // Use absolute path to avoid directory depth issues
        fetch('../ajax/fetch_notifications.php')
            .then(response => response.json())
            .then(data => {
                const badge = document.getElementById('notif-badge');
                if (data.count > 0) {
                    badge.textContent = data.count;
                    badge.style.display = 'inline-block';
                } else {
                    badge.style.display = 'none';
                }
            })
            .catch(err => console.error('Poll Error:', err));
    }

    // Determine if we need to poll (only if logged in and badge exists)
    if (document.getElementById('notif-badge')) {
        setInterval(pollNotifications, 5000); // 5 seconds
        pollNotifications(); // Initial fetch
    }
</script>
</body>

</html>