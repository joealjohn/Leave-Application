/**
 * Leave Management System JavaScript
 * Contains client-side functionality for the system
 */

document.addEventListener('DOMContentLoaded', function() {

    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl)
    });

    // Auto-dismiss alerts after 5 seconds
    setTimeout(function() {
        var alerts = document.querySelectorAll('.alert.alert-dismissible');
        alerts.forEach(function(alert) {
            var bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        });
    }, 5000);

    // Date range validation for leave requests
    const startDateInput = document.getElementById('start_date');
    const endDateInput = document.getElementById('end_date');

    if (startDateInput && endDateInput) {
        startDateInput.addEventListener('change', validateDates);
        endDateInput.addEventListener('change', validateDates);

        function validateDates() {
            const startDate = new Date(startDateInput.value);
            const endDate = new Date(endDateInput.value);

            if (startDate > endDate) {
                endDateInput.setCustomValidity('End date cannot be before start date');

                // Show validation message
                let errorMessage = document.getElementById('date-error-message');
                if (!errorMessage) {
                    errorMessage = document.createElement('div');
                    errorMessage.id = 'date-error-message';
                    errorMessage.className = 'text-danger mt-2';
                    endDateInput.parentNode.appendChild(errorMessage);
                }
                errorMessage.textContent = 'End date cannot be before start date';
            } else {
                endDateInput.setCustomValidity('');

                // Remove error message if it exists
                const errorMessage = document.getElementById('date-error-message');
                if (errorMessage) {
                    errorMessage.remove();
                }
            }
        }
    }

    // Confirm delete actions
    const deleteButtons = document.querySelectorAll('.delete-confirm');
    deleteButtons.forEach(function(button) {
        button.addEventListener('click', function(e) {
            if (!confirm('Are you sure you want to delete this item? This action cannot be undone.')) {
                e.preventDefault();
            }
        });
    });

    // Print functionality
    const printButtons = document.querySelectorAll('.btn-print');
    printButtons.forEach(function(button) {
        button.addEventListener('click', function() {
            window.print();
        });
    });

    // Leave days calculator
    function calculateLeaveDays() {
        const startDateInput = document.getElementById('start_date');
        const endDateInput = document.getElementById('end_date');
        const daysOutputElement = document.getElementById('leave_days_output');

        if (startDateInput && endDateInput && daysOutputElement) {
            if (startDateInput.value && endDateInput.value) {
                const startDate = new Date(startDateInput.value);
                const endDate = new Date(endDateInput.value);

                if (startDate <= endDate) {
                    // Calculate business days (excluding weekends)
                    let days = 0;
                    let currentDate = new Date(startDate);

                    while (currentDate <= endDate) {
                        const dayOfWeek = currentDate.getDay();
                        if (dayOfWeek !== 0 && dayOfWeek !== 6) {
                            days++;
                        }
                        currentDate.setDate(currentDate.getDate() + 1);
                    }

                    daysOutputElement.textContent = days + ' working day(s)';
                }
            }
        }
    }

    // Attach calculator to date inputs
    if (document.getElementById('start_date') && document.getElementById('end_date') && document.getElementById('leave_days_output')) {
        document.getElementById('start_date').addEventListener('change', calculateLeaveDays);
        document.getElementById('end_date').addEventListener('change', calculateLeaveDays);

        // Initial calculation if values are present
        calculateLeaveDays();
    }

    // Password strength indicator
    const passwordInput = document.getElementById('password');
    const strengthIndicator = document.getElementById('password-strength');

    if (passwordInput && strengthIndicator) {
        passwordInput.addEventListener('input', function() {
            const password = passwordInput.value;
            let strength = 0;

            if (password.length >= 8) strength += 1;
            if (password.match(/[a-z]/) && password.match(/[A-Z]/)) strength += 1;
            if (password.match(/[0-9]/)) strength += 1;
            if (password.match(/[^a-zA-Z0-9]/)) strength += 1;

            switch (strength) {
                case 0:
                    strengthIndicator.textContent = 'Very Weak';
                    strengthIndicator.className = 'text-danger';
                    break;
                case 1:
                    strengthIndicator.textContent = 'Weak';
                    strengthIndicator.className = 'text-warning';
                    break;
                case 2:
                    strengthIndicator.textContent = 'Fair';
                    strengthIndicator.className = 'text-warning';
                    break;
                case 3:
                    strengthIndicator.textContent = 'Good';
                    strengthIndicator.className = 'text-success';
                    break;
                case 4:
                    strengthIndicator.textContent = 'Strong';
                    strengthIndicator.className = 'text-success';
                    break;
            }
        });
    }

    // Real-time clock update (disabled since we're using fixed datetime for the project)
    /*
    function updateClock() {
        const now = new Date();
        const clockElement = document.getElementById('current-time');
        if (clockElement) {
            clockElement.textContent = now.toISOString().slice(0, 19).replace('T', ' ');
        }
        setTimeout(updateClock, 1000);
    }
    updateClock();
    */

    // Highlight current page in navigation
    const currentPath = window.location.pathname;
    const navLinks = document.querySelectorAll('.navbar-nav .nav-link');

    navLinks.forEach(function(link) {
        const href = link.getAttribute('href');
        if (href && currentPath.includes(href)) {
            link.classList.add('active');
        }
    });

    // Form validation
    const forms = document.querySelectorAll('.needs-validation');

    Array.prototype.slice.call(forms).forEach(function(form) {
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        }, false);
    });

    // Digital clock display
    const clockElement = document.getElementById('digital-clock');

    // Current time is fixed for this project, so we're displaying the static time
    if (clockElement) {
        clockElement.textContent = '2025-06-29 17:03:09';
    }
});

// Function to update leave summary in reports
function updateLeaveSummary() {
    const leaveTypeSelect = document.getElementById('leave-type-filter');
    const statusSelect = document.getElementById('status-filter');
    const summaryElement = document.getElementById('leave-summary');

    if (leaveTypeSelect && statusSelect && summaryElement) {
        const leaveType = leaveTypeSelect.value;
        const status = statusSelect.value;

        // This would normally fetch data from the backend
        // For this static version, we're just showing a placeholder message
        summaryElement.textContent = `Showing summary for ${leaveType !== 'all' ? leaveType : 'all'} leave types with ${status !== 'all' ? status : 'any'} status`;
    }
}

// Export table to CSV
function exportTableToCSV(tableId, filename = 'export.csv') {
    const table = document.getElementById(tableId);
    if (!table) return;

    let csvContent = "data:text/csv;charset=utf-8,";

    // Get headers
    const headers = [];
    const headerCells = table.querySelectorAll('thead th');
    headerCells.forEach(function(cell) {
        headers.push(cell.textContent.trim());
    });
    csvContent += headers.join(',') + '\r\n';

    // Get data rows
    const rows = table.querySelectorAll('tbody tr');
    rows.forEach(function(row) {
        const rowData = [];
        const cells = row.querySelectorAll('td');
        cells.forEach(function(cell) {
            // Replace commas in cell content to avoid CSV issues
            rowData.push('"' + cell.textContent.trim().replace(/"/g, '""') + '"');
        });
        csvContent += rowData.join(',') + '\r\n';
    });

    // Create download link
    const encodedUri = encodeURI(csvContent);
    const link = document.createElement('a');
    link.setAttribute('href', encodedUri);
    link.setAttribute('download', filename);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}