<?php
// Check login status for header navigation
$is_user_logged_in = isset($_SESSION['user_id']);
$is_admin_logged_in = isset($_SESSION['admin_id']);
?>

<nav class="navbar">
    <div class="nav-container">
        <div class="nav-logo">
            <i class="fas fa-network-wired"></i>
            <span>ConnectPro</span>
        </div>
        <ul class="nav-menu">
            <li class="nav-item">
                <a href="index.php#home" class="nav-link">Home</a>
            </li>
            <li class="nav-item">
                <a href="services.php" class="nav-link">Services</a>
            </li>
            <li class="nav-item">
                <a href="book-service.php" class="nav-link">Book Service</a>
            </li>
            <li class="nav-item">
                <a href="about.php" class="nav-link">About</a>
            </li>
            <li class="nav-item">
                <a href="contact.php" class="nav-link">Contact</a>
            </li>
            
            <?php if ($is_user_logged_in): ?>
                <li class="nav-item nav-item-mobile">
                    <a href="user/dashboard.php" class="nav-link auth-link">Dashboard</a>
                </li>
                <li class="nav-item nav-item-mobile">
                    <a href="user/logout.php" class="nav-link auth-logout" onclick="return confirm('Logout from your account?')">Logout</a>
                </li>
            <?php elseif ($is_admin_logged_in): ?>
                <li class="nav-item nav-item-mobile">
                    <a href="admin/dashboard.php" class="nav-link auth-link">Admin Panel</a>
                </li>
                <li class="nav-item nav-item-mobile">
                    <a href="admin/logout.php" class="nav-link auth-logout" onclick="return confirm('Logout from admin panel?')">Admin Logout</a>
                </li>
            <?php else: ?>
                <li class="nav-item nav-item-mobile">
                    <a href="user/login.php" class="nav-link auth-link">User Login</a>
                </li>
                <li class="nav-item nav-item-mobile">
                    <a href="user/register.php" class="nav-link auth-link auth-register">Register</a>
                </li>
            <?php endif; ?>
        </ul>
        
        <div class="nav-auth">
            <?php if ($is_user_logged_in): ?>
                <a href="user/dashboard.php" class="nav-link auth-link">
                    <i class="fas fa-user"></i> Dashboard
                </a>
                <a href="user/logout.php" class="nav-link auth-logout" onclick="return confirm('Logout from your account?')">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            <?php elseif ($is_admin_logged_in): ?>
                <a href="admin/dashboard.php" class="nav-link auth-link">
                    <i class="fas fa-cog"></i> Admin Panel
                </a>
                <a href="admin/logout.php" class="nav-link auth-logout" onclick="return confirm('Logout from admin panel?')">
                    <i class="fas fa-sign-out-alt"></i> Admin Logout
                </a>
            <?php else: ?>
                <a href="user/login.php" class="nav-link auth-link">Login</a>
                <a href="user/register.php" class="nav-link auth-link auth-register">Register</a>
            <?php endif; ?>
        </div>
        
        <div class="nav-toggle" id="mobile-menu">
            <span class="bar"></span>
            <span class="bar"></span>
            <span class="bar"></span>
        </div>
    </div>
</nav>

<style>
.auth-logout {
    color: #dc3545 !important;
    font-weight: 500;
}

.auth-logout:hover {
    color: #c82333 !important;
}
</style>
