<!-- includes/footer-scripts.php -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Function to update the date display
        function updateCurrentDate() {
            const now = new Date();

            // Format the date to YYYY-MM-DD HH:MM:SS
            const year = now.getFullYear();
            const month = String(now.getMonth() + 1).padStart(2, '0');
            const day = String(now.getDate()).padStart(2, '0');
            const hours = String(now.getHours()).padStart(2, '0');
            const minutes = String(now.getMinutes()).padStart(2, '0');
            const seconds = String(now.getSeconds()).padStart(2, '0');

            const formattedDate = `${year}-${month}-${day} ${hours}:${minutes}:${seconds}`;

            // Update all date display elements
            const dateDisplays = document.querySelectorAll('#currentDateDisplay');
            dateDisplays.forEach(element => {
                element.textContent = formattedDate;
            });
        }

        // Update immediately
        updateCurrentDate();

        // Update every second
        setInterval(updateCurrentDate, 1000);

        // Hide alerts after 5 seconds
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert:not(.alert-permanent)');
            alerts.forEach(function(alert) {
                if (bootstrap && bootstrap.Alert) {
                    const bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                } else {
                    alert.style.display = 'none';
                }
            });
        }, 5000);

        // Add tooltip initialization if Bootstrap is available
        if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
            tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl)
            });
        }
    });
</script>