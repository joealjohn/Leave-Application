/**
 * Leave Management System JavaScript
 * Contains client-side functionality for the system
 */

document.addEventListener('DOMContentLoaded', function() {

    // CRITICAL: Fix modal backdrop issues
    fixModalBackdropIssues();

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
            const password = this.value;
            let strength = 0;
            let strengthText = '';
            let strengthClass = '';

            if (password.length >= 6) strength++;
            if (/[a-z]/.test(password)) strength++;
            if (/[A-Z]/.test(password)) strength++;
            if (/[0-9]/.test(password)) strength++;
            if (/[^a-zA-Z0-9]/.test(password)) strength++;

            switch (strength) {
                case 0:
                case 1:
                    strengthText = 'Very Weak';
                    strengthClass = 'text-danger';
                    break;
                case 2:
                    strengthText = 'Weak';
                    strengthClass = 'text-warning';
                    break;
                case 3:
                    strengthText = 'Medium';
                    strengthClass = 'text-info';
                    break;
                case 4:
                    strengthText = 'Strong';
                    strengthClass = 'text-success';
                    break;
                case 5:
                    strengthText = 'Very Strong';
                    strengthClass = 'text-success fw-bold';
                    break;
            }

            strengthIndicator.textContent = strengthText;
            strengthIndicator.className = strengthClass;
        });
    }
});

/**
 * CRITICAL FUNCTION: Fix modal backdrop issues
 * This prevents the black overlay problem
 */
function fixModalBackdropIssues() {
    // Remove any orphaned modal backdrops
    const orphanedBackdrops = document.querySelectorAll('.modal-backdrop');
    orphanedBackdrops.forEach(backdrop => {
        backdrop.remove();
    });

    // Remove modal-open class from body if no modals are actually open
    const openModals = document.querySelectorAll('.modal.show');
    if (openModals.length === 0) {
        document.body.classList.remove('modal-open');
        document.body.style.overflow = '';
        document.body.style.paddingRight = '';
    }

    // Add event listeners to all modals to properly clean up
    const allModals = document.querySelectorAll('.modal');
    allModals.forEach(modal => {
        // Clean up when modal is hidden
        modal.addEventListener('hidden.bs.modal', function() {
            // Remove any leftover backdrops
            const backdrops = document.querySelectorAll('.modal-backdrop');
            backdrops.forEach(backdrop => {
                backdrop.remove();
            });

            // Reset body classes and styles
            document.body.classList.remove('modal-open');
            document.body.style.overflow = '';
            document.body.style.paddingRight = '';
        });

        // Ensure proper cleanup when modal is being hidden
        modal.addEventListener('hide.bs.modal', function() {
            // Force remove modal-open class after a short delay
            setTimeout(() => {
                if (document.querySelectorAll('.modal.show').length === 0) {
                    document.body.classList.remove('modal-open');
                    document.body.style.overflow = '';
                    document.body.style.paddingRight = '';
                }
            }, 150);
        });
    });

    // Add emergency cleanup on click outside modals
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('modal-backdrop')) {
            // Force close all modals and clean up
            const openModals = document.querySelectorAll('.modal.show');
            openModals.forEach(modal => {
                const bsModal = bootstrap.Modal.getInstance(modal);
                if (bsModal) {
                    bsModal.hide();
                }
            });

            // Emergency cleanup
            setTimeout(() => {
                const backdrops = document.querySelectorAll('.modal-backdrop');
                backdrops.forEach(backdrop => backdrop.remove());
                document.body.classList.remove('modal-open');
                document.body.style.overflow = '';
                document.body.style.paddingRight = '';
            }, 200);
        }
    });

    // Emergency cleanup function - can be called manually
    window.emergencyModalCleanup = function() {
        const backdrops = document.querySelectorAll('.modal-backdrop');
        backdrops.forEach(backdrop => backdrop.remove());

        const modals = document.querySelectorAll('.modal.show');
        modals.forEach(modal => modal.classList.remove('show'));

        document.body.classList.remove('modal-open');
        document.body.style.overflow = '';
        document.body.style.paddingRight = '';

        console.log('Emergency modal cleanup performed');
    };
}

// Additional helper functions
window.closeAllModals = function() {
    const modals = document.querySelectorAll('.modal');
    modals.forEach(modal => {
        const bsModal = bootstrap.Modal.getInstance(modal);
        if (bsModal) {
            bsModal.hide();
        }
    });
};

// Keyboard shortcut for emergency cleanup (Ctrl+Shift+Escape)
document.addEventListener('keydown', function(e) {
    if (e.ctrlKey && e.shiftKey && e.key === 'Escape') {
        window.emergencyModalCleanup();
    }
});