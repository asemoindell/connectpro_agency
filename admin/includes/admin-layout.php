<?php
// Admin Layout Template - Include this at the top of admin pages
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

// Helper function to safely format currency values
if (!function_exists('formatCurrency')) {
    function formatCurrency($value, $decimals = 2) {
        // Convert to float, defaulting to 0 if null, empty, or non-numeric
        $numericValue = is_numeric($value) ? (float)$value : 0;
        return number_format($numericValue, $decimals);
    }
}

// Get admin info if not already loaded
if (!isset($admin)) {
    try {
        $database = new Database();
        $pdo = $database->getConnection();
        $stmt = $pdo->prepare("SELECT * FROM admins WHERE admin_id = ?");
        $stmt->execute([$_SESSION['admin_id']]);
        $admin = $stmt->fetch();
    } catch (Exception $e) {
        $admin = ['first_name' => 'Admin', 'last_name' => 'User'];
    }
}

function renderAdminLayout($title, $currentPage = 'dashboard', $content = '') {
    global $admin;
    $adminName = ($admin['first_name'] ?? 'Admin') . ' ' . ($admin['last_name'] ?? 'User');
    $adminInitials = strtoupper(substr($admin['first_name'] ?? 'A', 0, 1) . substr($admin['last_name'] ?? 'U', 0, 1));
    
    ob_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($title); ?> - ConnectPro Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/admin-unified.css">
    <link rel="stylesheet" href="../css/mobile-responsive.css">
</head>
<body>
    <div class="admin-layout">
        <!-- Mobile Menu Toggle -->
        <button class="mobile-menu-toggle d-md-none" id="mobileMenuToggle">
            <i class="fas fa-bars"></i>
        </button>
        
        <!-- Sidebar -->
        <aside class="admin-sidebar" id="adminSidebar">
            <div class="sidebar-header">
                <a href="dashboard.php" class="logo">
                    <i class="fas fa-shield-alt"></i>
                    <span>ConnectPro</span>
                </a>
            </div>
            
            <nav class="sidebar-nav">
                <ul class="nav-list">
                    <li class="nav-item">
                        <a href="dashboard.php" class="nav-link <?php echo $currentPage === 'dashboard' ? 'active' : ''; ?>">
                            <i class="fas fa-tachometer-alt"></i>
                            <span>Dashboard</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="users.php" class="nav-link <?php echo $currentPage === 'users' ? 'active' : ''; ?>">
                            <i class="fas fa-users"></i>
                            <span>User Management</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="bookings.php" class="nav-link <?php echo $currentPage === 'bookings' ? 'active' : ''; ?>">
                            <i class="fas fa-calendar-check"></i>
                            <span>Bookings</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="services.php" class="nav-link <?php echo $currentPage === 'services' ? 'active' : ''; ?>">
                            <i class="fas fa-cogs"></i>
                            <span>Services</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="agents.php" class="nav-link <?php echo $currentPage === 'agents' ? 'active' : ''; ?>">
                            <i class="fas fa-user-tie"></i>
                            <span>Agent Management</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="payments.php" class="nav-link <?php echo $currentPage === 'payments' ? 'active' : ''; ?>">
                            <i class="fas fa-credit-card"></i>
                            <span>Payments</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="email-templates.php" class="nav-link <?php echo $currentPage === 'email-templates' ? 'active' : ''; ?>">
                            <i class="fas fa-envelope"></i>
                            <span>Email Templates</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="settings.php" class="nav-link <?php echo $currentPage === 'settings' ? 'active' : ''; ?>">
                            <i class="fas fa-cog"></i>
                            <span>Settings</span>
                        </a>
                    </li>
                </ul>
            </nav>
            
            <div class="sidebar-footer" style="position: absolute; bottom: 0; width: 100%; padding: 1rem;">
                <div class="admin-user">
                    <div class="admin-avatar"><?php echo $adminInitials; ?></div>
                    <div>
                        <div style="color: white; font-weight: 500; font-size: 0.875rem;"><?php echo htmlspecialchars($adminName); ?></div>
                        <div style="color: #cbd5e1; font-size: 0.75rem;"><?php echo htmlspecialchars($admin['role'] ?? 'Administrator'); ?></div>
                    </div>
                </div>
            </div>
        </aside>
        
        <!-- Main Content -->
        <main class="admin-main">
            <!-- Header -->
            <header class="admin-header">
                <div class="header-left">
                    <button class="btn btn-outline btn-sm d-lg-none" id="sidebarToggle">
                        <i class="fas fa-bars"></i>
                    </button>
                    <h1><?php echo htmlspecialchars($title); ?></h1>
                </div>
                
                <div class="header-right">
                    <div class="admin-user">
                        <span><?php echo htmlspecialchars($adminName); ?></span>
                        <div class="admin-avatar"><?php echo $adminInitials; ?></div>
                    </div>
                    <a href="logout.php" class="btn btn-outline btn-sm">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Logout</span>
                    </a>
                </div>
            </header>
            
            <!-- Content -->
            <div class="admin-content">
                <?php echo $content; ?>
            </div>
        </main>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Sidebar toggle for mobile
        document.getElementById('sidebarToggle')?.addEventListener('click', function() {
            document.getElementById('adminSidebar').classList.toggle('open');
        });
        
        // Mobile menu toggle
        document.getElementById('mobileMenuToggle')?.addEventListener('click', function() {
            document.getElementById('adminSidebar').classList.toggle('show');
        });
        
        // Close mobile menu when clicking outside
        document.addEventListener('click', function(event) {
            const sidebar = document.getElementById('adminSidebar');
            const toggle = document.getElementById('mobileMenuToggle');
            
            if (!sidebar.contains(event.target) && !toggle.contains(event.target)) {
                sidebar.classList.remove('show');
            }
        });
        
        // Auto-refresh certain pages
        <?php if ($currentPage === 'dashboard'): ?>
        // Auto-refresh dashboard every 30 seconds
        setTimeout(() => {
            if (document.hidden === false) {
                location.reload();
            }
        }, 30000);
        <?php endif; ?>
    </script>
</body>
</html>
<?php
    return ob_get_clean();
}

function renderStatsCard($icon, $number, $label, $change = null, $color = 'primary') {
    $colorClass = $color === 'primary' ? '' : " stat-card-{$color}";
    $changeHtml = '';
    
    // Convert number to float and handle non-numeric values
    if (is_numeric($number)) {
        $formattedNumber = number_format((float)$number);
    } else {
        // If it's already a formatted string (like "$1,234.56"), use as-is
        $formattedNumber = $number;
    }
    
    if ($change !== null) {
        $changeIcon = $change >= 0 ? 'fa-arrow-up' : 'fa-arrow-down';
        $changeColor = $change >= 0 ? 'success' : 'danger';
        $changeHtml = "<div class=\"stat-change text-{$changeColor}\">
            <i class=\"fas {$changeIcon}\"></i> " . abs($change) . "%
        </div>";
    }
    
    return "
    <div class=\"stat-card{$colorClass}\">
        <div class=\"stat-icon\">
            <i class=\"{$icon}\"></i>
        </div>
        <div class=\"stat-number\">" . $formattedNumber . "</div>
        <div class=\"stat-label\">{$label}</div>
        {$changeHtml}
    </div>";
}

function renderStatusBadge($status) {
    $statusMap = [
        'pending' => ['pending', 'clock'],
        'approved' => ['approved', 'check'],
        'rejected' => ['rejected', 'times'],
        'active' => ['active', 'check-circle'],
        'inactive' => ['inactive', 'times-circle'],
        'in_progress' => ['in-progress', 'spinner'],
        'completed' => ['completed', 'check-circle'],
        'cancelled' => ['rejected', 'times'],
        'waiting_approval' => ['pending', 'clock']
    ];
    
    $config = $statusMap[$status] ?? ['pending', 'question'];
    return "<span class=\"status-badge status-{$config[0]}\">
        <i class=\"fas fa-{$config[1]}\"></i>
        " . ucfirst(str_replace('_', ' ', $status)) . "
    </span>";
}

function renderActionButton($href, $icon, $text, $type = 'primary', $size = 'sm') {
    return "<a href=\"{$href}\" class=\"btn btn-{$type} btn-{$size}\">
        <i class=\"fas fa-{$icon}\"></i>
        <span>{$text}</span>
    </a>";
}

// Helper functions for the new layout system
function createStatsCard($title, $value, $icon, $type = 'primary') {
    $colorClass = $type !== 'primary' ? " stats-card-{$type}" : "";
    
    return "
    <div class=\"stats-card{$colorClass}\">
        <div class=\"stats-icon\">
            <i class=\"{$icon}\"></i>
        </div>
        <div class=\"stats-content\">
            <div class=\"stats-value\">{$value}</div>
            <div class=\"stats-title\">{$title}</div>
        </div>
    </div>";
}

function createStatusBadge($status, $text = null) {
    if ($text === null) {
        $text = ucfirst(str_replace('_', ' ', $status));
    }
    
    $statusMap = [
        'pending' => 'warning',
        'approved' => 'success',
        'rejected' => 'danger',
        'active' => 'success',
        'inactive' => 'secondary',
        'in_progress' => 'info',
        'completed' => 'success',
        'cancelled' => 'danger',
        'waiting_approval' => 'warning',
        'paid' => 'success',
        'payment_pending' => 'warning',
        'failed' => 'danger'
    ];
    
    $badgeType = $statusMap[$status] ?? 'secondary';
    
    return "<span class=\"badge badge-{$badgeType}\">{$text}</span>";
}

function createActionButton($url, $text, $icon = null, $type = 'primary', $size = 'sm') {
    $iconHtml = $icon ? "<i class=\"fas fa-{$icon}\"></i> " : "";
    
    return "<a href=\"{$url}\" class=\"btn btn-{$type} btn-{$size}\">
        {$iconHtml}{$text}
    </a>";
}
?>
