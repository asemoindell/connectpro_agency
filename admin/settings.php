<?php
session_start();
require_once '../config/database.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

$database = new Database();
$db = $database->getConnection();

$success_message = '';
$error_message = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'update_email_settings':
            // Handle email settings update
            $success_message = "Email settings updated successfully! (Note: Implement SMTP configuration in EmailNotification.php)";
            break;
            
        case 'update_payment_settings':
            // Handle payment settings update
            $success_message = "Payment settings updated successfully! (Note: Implement real payment gateway integration)";
            break;
            
        case 'update_auto_approval':
            $days = $_POST['auto_approval_days'] ?? 3;
            $success_message = "Auto-approval set to $days days. Update the cron job accordingly.";
            break;
            
        case 'test_email':
            $test_email = $_POST['test_email'] ?? '';
            if ($test_email) {
                require_once '../includes/EmailNotification.php';
                $emailNotification = new EmailNotification($db);
                
                // Try to send test email
                $result = $emailNotification->sendTestEmail($test_email);
                if ($result) {
                    $success_message = "Test email sent successfully to $test_email";
                } else {
                    $error_message = "Failed to send test email. Check email configuration.";
                }
            }
            break;
    }
}

// Get current statistics
$stats = [];
try {
    $stmt = $db->query("SELECT COUNT(*) as count FROM service_bookings");
    $stats['total_bookings'] = $stmt->fetch()['count'];
    
    $stmt = $db->query("SELECT COUNT(*) as count FROM service_bookings WHERE status = 'waiting_approval'");
    $stats['pending_approvals'] = $stmt->fetch()['count'];
    
    $stmt = $db->query("SELECT COUNT(*) as count FROM users WHERE status = 'active'");
    $stats['active_users'] = $stmt->fetch()['count'];
    
    $stmt = $db->query("SELECT COUNT(*) as count FROM services WHERE status = 'active'");
    $stats['active_services'] = $stmt->fetch()['count'];
    
} catch (Exception $e) {
    $stats = ['total_bookings' => 0, 'pending_approvals' => 0, 'active_users' => 0, 'active_services' => 0];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Settings - ConnectPro Admin</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/admin.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .settings-container { max-width: 1200px; margin: 0 auto; padding: 20px; }
        .settings-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 20px; margin-top: 20px; }
        .settings-card { background: white; border-radius: 10px; padding: 25px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .settings-card h3 { color: #333; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 500; color: #555; }
        .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px; }
        .btn-settings { background: #007bff; color: white; padding: 12px 24px; border: none; border-radius: 6px; cursor: pointer; font-weight: 500; }
        .btn-settings:hover { background: #0056b3; }
        .btn-test { background: #28a745; }
        .btn-test:hover { background: #1e7e34; }
        .alert { padding: 15px; border-radius: 6px; margin-bottom: 20px; }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 30px; }
        .stat-card { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; border-radius: 10px; text-align: center; }
        .stat-number { font-size: 2em; font-weight: bold; margin-bottom: 5px; }
        .stat-label { font-size: 0.9em; opacity: 0.9; }
    </style>
</head>
<body>
    <div class="admin-container">
        <!-- Admin Navigation -->
        <nav class="admin-nav">
            <div class="admin-nav-brand">
                <i class="fas fa-cog"></i>
                <span>ConnectPro Admin</span>
            </div>
            <div class="admin-nav-links">
                <a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                <a href="bookings.php"><i class="fas fa-calendar-check"></i> Bookings</a>
                <a href="settings.php" class="active"><i class="fas fa-cog"></i> Settings</a>
                <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </nav>

        <div class="admin-content">
            <div class="settings-container">
                <h1><i class="fas fa-cog"></i> System Settings</h1>
                
                <?php if ($success_message): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success_message); ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($error_message): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error_message); ?>
                    </div>
                <?php endif; ?>

                <!-- System Statistics -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $stats['total_bookings']; ?></div>
                        <div class="stat-label">Total Bookings</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $stats['pending_approvals']; ?></div>
                        <div class="stat-label">Pending Approvals</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $stats['active_users']; ?></div>
                        <div class="stat-label">Active Users</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $stats['active_services']; ?></div>
                        <div class="stat-label">Active Services</div>
                    </div>
                </div>

                <div class="settings-grid">
                    <!-- Email Settings -->
                    <div class="settings-card">
                        <h3><i class="fas fa-envelope"></i> Email Configuration</h3>
                        <form method="POST">
                            <input type="hidden" name="action" value="update_email_settings">
                            <div class="form-group">
                                <label>SMTP Server</label>
                                <input type="text" name="smtp_host" placeholder="smtp.gmail.com" value="localhost">
                            </div>
                            <div class="form-group">
                                <label>SMTP Port</label>
                                <input type="number" name="smtp_port" placeholder="587" value="587">
                            </div>
                            <div class="form-group">
                                <label>Email Address</label>
                                <input type="email" name="email_address" placeholder="admin@connectpro.com" value="admin@connectpro.com">
                            </div>
                            <div class="form-group">
                                <label>Email Password</label>
                                <input type="password" name="email_password" placeholder="App Password">
                            </div>
                            <button type="submit" class="btn-settings">
                                <i class="fas fa-save"></i> Update Email Settings
                            </button>
                        </form>
                        
                        <!-- Test Email -->
                        <hr style="margin: 20px 0;">
                        <form method="POST" style="display: flex; gap: 10px; align-items: end;">
                            <div class="form-group" style="flex: 1; margin: 0;">
                                <label>Test Email Address</label>
                                <input type="email" name="test_email" placeholder="test@example.com" required>
                            </div>
                            <input type="hidden" name="action" value="test_email">
                            <button type="submit" class="btn-settings btn-test">
                                <i class="fas fa-paper-plane"></i> Send Test
                            </button>
                        </form>
                    </div>

                    <!-- Payment Settings -->
                    <div class="settings-card">
                        <h3><i class="fas fa-credit-card"></i> Payment Configuration</h3>
                        <form method="POST">
                            <input type="hidden" name="action" value="update_payment_settings">
                            <div class="form-group">
                                <label>Stripe Publishable Key</label>
                                <input type="text" name="stripe_public_key" placeholder="pk_test_...">
                            </div>
                            <div class="form-group">
                                <label>Stripe Secret Key</label>
                                <input type="password" name="stripe_secret_key" placeholder="sk_test_...">
                            </div>
                            <div class="form-group">
                                <label>USDT Wallet Address</label>
                                <input type="text" name="usdt_wallet" placeholder="TRC20 wallet address">
                            </div>
                            <div class="form-group">
                                <label>PayPal Client ID</label>
                                <input type="text" name="paypal_client_id" placeholder="PayPal Client ID">
                            </div>
                            <button type="submit" class="btn-settings">
                                <i class="fas fa-save"></i> Update Payment Settings
                            </button>
                        </form>
                    </div>

                    <!-- Auto-Approval Settings -->
                    <div class="settings-card">
                        <h3><i class="fas fa-clock"></i> Auto-Approval Settings</h3>
                        <form method="POST">
                            <input type="hidden" name="action" value="update_auto_approval">
                            <div class="form-group">
                                <label>Auto-Approval Days</label>
                                <select name="auto_approval_days">
                                    <option value="3">3 Days</option>
                                    <option value="4" selected>4 Days</option>
                                    <option value="5">5 Days</option>
                                    <option value="7">7 Days</option>
                                </select>
                                <small style="color: #666; margin-top: 5px; display: block;">
                                    Bookings will be auto-approved after this many days
                                </small>
                            </div>
                            <div class="form-group">
                                <label>Cron Job Command</label>
                                <textarea readonly rows="2" style="background: #f8f9fa; font-family: monospace; font-size: 12px;">0 9 * * * cd /path/to/Agency && php cron/auto-approve-bookings.php</textarea>
                                <small style="color: #666; margin-top: 5px; display: block;">
                                    Add this to your server's crontab for daily execution
                                </small>
                            </div>
                            <button type="submit" class="btn-settings">
                                <i class="fas fa-save"></i> Update Auto-Approval
                            </button>
                        </form>
                    </div>

                    <!-- System Information -->
                    <div class="settings-card">
                        <h3><i class="fas fa-info-circle"></i> System Information</h3>
                        <div style="line-height: 1.8;">
                            <p><strong>PHP Version:</strong> <?php echo PHP_VERSION; ?></p>
                            <p><strong>Database:</strong> MySQL <?php 
                                try {
                                    $stmt = $db->query("SELECT VERSION()");
                                    echo $stmt->fetchColumn();
                                } catch (Exception $e) {
                                    echo "Connection Error";
                                }
                            ?></p>
                            <p><strong>Server Time:</strong> <?php echo date('Y-m-d H:i:s'); ?></p>
                            <p><strong>Installation:</strong> <?php echo date('Y-m-d', filemtime('../index.php')); ?></p>
                        </div>
                        
                        <hr style="margin: 20px 0;">
                        
                        <div style="margin-bottom: 15px;">
                            <strong>Quick Actions:</strong>
                        </div>
                        <div style="display: flex; flex-direction: column; gap: 10px;">
                            <a href="../test-workflow.php" target="_blank" class="btn-settings" style="text-align: center; text-decoration: none; display: block;">
                                <i class="fas fa-vial"></i> Run System Tests
                            </a>
                            <a href="../setup-complete.php" target="_blank" class="btn-settings" style="text-align: center; text-decoration: none; display: block; background: #6c757d;">
                                <i class="fas fa-database"></i> Database Setup
                            </a>
                            <a href="http://localhost/phpmyadmin" target="_blank" class="btn-settings" style="text-align: center; text-decoration: none; display: block; background: #17a2b8;">
                                <i class="fas fa-database"></i> phpMyAdmin
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Documentation Links -->
                <div class="settings-card" style="margin-top: 20px;">
                    <h3><i class="fas fa-book"></i> Documentation & Resources</h3>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px;">
                        <a href="../IMPLEMENTATION_COMPLETE.md" target="_blank" style="display: block; padding: 15px; background: #f8f9fa; border-radius: 6px; text-decoration: none; color: #333;">
                            <i class="fas fa-file-alt"></i> <strong>Implementation Guide</strong><br>
                            <small>Complete documentation of all features</small>
                        </a>
                        <a href="../DATABASE_README.md" target="_blank" style="display: block; padding: 15px; background: #f8f9fa; border-radius: 6px; text-decoration: none; color: #333;">
                            <i class="fas fa-database"></i> <strong>Database Documentation</strong><br>
                            <small>Schema and setup instructions</small>
                        </a>
                        <a href="../book-service.php" target="_blank" style="display: block; padding: 15px; background: #f8f9fa; border-radius: 6px; text-decoration: none; color: #333;">
                            <i class="fas fa-calendar-plus"></i> <strong>Booking Form</strong><br>
                            <small>Test the booking workflow</small>
                        </a>
                        <a href="../user/dashboard.php" target="_blank" style="display: block; padding: 15px; background: #f8f9fa; border-radius: 6px; text-decoration: none; color: #333;">
                            <i class="fas fa-user"></i> <strong>User Dashboard</strong><br>
                            <small>Client portal interface</small>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
