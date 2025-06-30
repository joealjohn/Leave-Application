<footer class="text-center py-2">
    <div class="container">
        <div class="row">
            <div class="col-md-12 d-flex justify-content-center align-items-center flex-wrap">
                <span class="me-3 mb-2 mb-md-0"><i class="fas fa-calendar-check me-1"></i> Leave Management System &copy; <?php echo date('Y'); ?></span>

                <!-- Logout button in footer for accessibility -->
                <?php if (isset($_SESSION['user_id'])): ?>
                    <div class="ms-md-3">
                        <a href="<?php echo (strpos($_SERVER['PHP_SELF'], '/admin/') !== false || strpos($_SERVER['PHP_SELF'], '/user/') !== false) ? '../logout.php' : 'logout.php'; ?>"
                           class="btn btn-sm btn-danger footer-logout">
                            <i class="fas fa-sign-out-alt me-1"></i> Logout
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</footer>