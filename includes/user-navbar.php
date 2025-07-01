<!-- includes/user-navbar.php -->
<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container-fluid">
        <a class="navbar-brand d-flex align-items-center" href="dashboard.php">
            <i class="fas fa-calendar-check me-2"></i> Leave Management System
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link" href="dashboard.php">
                        <i class="fas fa-tachometer-alt me-1"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="apply_leave.php">
                        <i class="fas fa-plus-circle me-1"></i> Apply Leave
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="my_requests.php">
                        <i class="fas fa-list me-1"></i> My Requests
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="profile.php">
                        <i class="fas fa-user me-1"></i> Profile
                    </a>
                </li>
            </ul>

            <div class="ms-auto d-flex align-items-center">
                <!-- Current Date Time Display -->
                <div class="header-pill date-pill me-2">
                    <i class="fas fa-calendar-alt me-1"></i>
                    <span id="currentDateDisplay"></span>
                </div>

                <!-- Current User Login Display -->
                <div class="header-pill user-pill me-2">
                    <i class="fas fa-user me-1"></i>
                    Current User's Login: <?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Guest'); ?>
                </div>

                <!-- Logout Button -->
                <a href="../logout.php" class="header-pill logout-pill">
                    <i class="fas fa-sign-out-alt me-1"></i> Logout
                </a>
            </div>
        </div>
    </div>
</nav>

<style>
    .header-pill {
        background-color: rgba(255, 255, 255, 0.2);
        color: white;
        border-radius: 4px;
        padding: 6px 15px;
        display: inline-flex;
        align-items: center;
        font-size: 14px;
        text-decoration: none;
        border: 1px solid rgba(255, 255, 255, 0.3);
    }

    .logout-pill {
        background-color: rgba(255, 255, 255, 0.3);
        transition: all 0.3s ease;
    }

    .logout-pill:hover {
        background-color: rgba(255, 255, 255, 0.4);
        color: white;
    }

    @media (max-width: 1200px) {
        .header-pill {
            padding: 6px 10px;
            font-size: 12px;
        }
    }

    @media (max-width: 992px) {
        .date-pill, .user-pill, .logout-pill {
            display: none;
        }
    }
</style>