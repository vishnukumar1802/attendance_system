</div> <!-- End Page Content Padding -->
</div> <!-- End Main Content -->
</div> <!-- End App Container -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Use a simple script to mark the active link if PHP match failed or for client-side interactivity
    const currentPath = window.location.pathname.split("/").pop();
    document.querySelectorAll('.sidebar .nav-link').forEach(link => {
        if (link.getAttribute('href') === currentPath) {
            link.classList.add('active');
        }
    });
</script>
<script>
    // Global Notification Polling (Admin)
    function pollAdminNotifications() {
        const badge = document.getElementById('admin-notif-badge');
        if (!badge) return;

        fetch('../ajax/fetch_notifications.php')
            .then(response => response.json())
            .then(data => {
                if (data.count > 0) {
                    badge.textContent = data.count;
                    badge.style.display = 'inline-block';
                } else {
                    badge.style.display = 'none';
                }
            })
            .catch(err => console.error('Poll Error:', err));
    }

    if (document.getElementById('admin-notif-badge')) {
        setInterval(pollAdminNotifications, 5000); // 5 seconds
        pollAdminNotifications();
    }
</script>
</body>

</html>