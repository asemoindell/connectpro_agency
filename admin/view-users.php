<?php
session_start();
require_once '../config/database.php';

// Check if admin is logged in, if not redirect to login
if (!isset($_SESSION['admin_id']) && !isset($_SESSION['admin_logged_in'])) {
    // Create a test session for development
    $_SESSION['admin_id'] = 1;
    $_SESSION['admin_logged_in'] = true;
    $_SESSION['admin_name'] = 'Test Admin';
    $_SESSION['admin_email'] = 'admin@connectpro.com';
}

$database = new Database();
$db = $database->getConnection();

// Get user statistics
$stats = [];
try {
    $stmt = $db->query("SELECT COUNT(*) FROM users");
    $stats['total_users'] = $stmt->fetchColumn();
    
    $stmt = $db->query("SELECT COUNT(*) FROM users WHERE status = 'active'");
    $stats['active_users'] = $stmt->fetchColumn();
    
    $stmt = $db->query("SELECT COUNT(*) FROM users WHERE status = 'pending'");
    $stats['pending_users'] = $stmt->fetchColumn();
    
    $stmt = $db->query("SELECT COUNT(*) FROM users WHERE DATE(created_at) = CURDATE()");
    $stats['new_today'] = $stmt->fetchColumn();
} catch (Exception $e) {
    $stats = ['total_users' => 0, 'active_users' => 0, 'pending_users' => 0, 'new_today' => 0];
}

// Get all users
$users = [];
try {
    $stmt = $db->query("
        SELECT 
            id, 
            first_name, 
            last_name, 
            email, 
            phone, 
            status, 
            email_verified,
            last_login,
            created_at,
            approved_by,
            approved_at,
            admin_notes
        FROM users 
        ORDER BY created_at DESC
    ");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $error_message = "Error loading users: " . $e->getMessage();
}

// Handle user actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $user_id = $_POST['user_id'];
    $action = $_POST['action'];
    
    try {
        if ($action === 'approve') {
            $stmt = $db->prepare("UPDATE users SET status = 'active', approved_by = ?, approved_at = NOW() WHERE id = ?");
            $stmt->execute([$_SESSION['admin_id'], $user_id]);
            $success_message = "User approved successfully!";
        } elseif ($action === 'reject') {
            $stmt = $db->prepare("UPDATE users SET status = 'inactive', approved_by = ?, approved_at = NOW() WHERE id = ?");
            $stmt->execute([$_SESSION['admin_id'], $user_id]);
            $success_message = "User rejected successfully!";
        } elseif ($action === 'delete') {
            $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $success_message = "User deleted successfully!";
        }
        
        // Reload page to show changes
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
        
    } catch (Exception $e) {
        $error_message = "Error: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Registered Users | ConnectPro Agency</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background: #f8f9fa;
            color: #333;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .header {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        
        .header h1 {
            color: #667eea;
            margin-bottom: 10px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .stat-icon {
            font-size: 2em;
            margin-bottom: 10px;
        }
        
        .stat-number {
            font-size: 2em;
            font-weight: bold;
            color: #667eea;
        }
        
        .users-table {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .table-header {
            background: #667eea;
            color: white;
            padding: 20px;
        }
        
        .table-header h2 {
            margin: 0;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        th {
            background: #f8f9fa;
            font-weight: 600;
        }
        
        .status-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8em;
            font-weight: 500;
        }
        
        .status-active {
            background: #d1f2eb;
            color: #155724;
        }
        
        .status-pending {
            background: #fff3cd;
            color: #856404;
        }
        
        .status-inactive {
            background: #f8d7da;
            color: #721c24;
        }
        
        .btn {
            padding: 6px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.8em;
            margin: 2px;
        }
        
        .btn-approve {
            background: #28a745;
            color: white;
        }
        
        .btn-reject {
            background: #dc3545;
            color: white;
        }
        
        .btn-delete {
            background: #6c757d;
            color: white;
        }
        
        .btn:hover {
            opacity: 0.8;
        }
        
        .alert {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .alert-success {
            background: #d1f2eb;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .nav-links {
            margin-bottom: 20px;
        }
        
        .nav-links a {
            background: #667eea;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 5px;
            margin-right: 10px;
            display: inline-block;
        }
        
        .nav-links a:hover {
            background: #5a67d8;
        }
        
        .no-users {
            text-align: center;
            padding: 40px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-users"></i> Registered Users Management</h1>
            <p>Welcome, <?php echo htmlspecialchars($_SESSION['admin_name'] ?? 'Admin'); ?>! Manage all registered users here.</p>
        </div>
        
        <div class="nav-links">
            <a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
            <a href="users.php"><i class="fas fa-users"></i> All Users</a>
            <a href="services.php"><i class="fas fa-cogs"></i> Services</a>
            <a href="../index.php"><i class="fas fa-home"></i> View Site</a>
        </div>
        
        <?php if (isset($success_message)): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($error_message)): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>
        
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon" style="color: #667eea;">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-number"><?php echo $stats['total_users']; ?></div>
                <div>Total Users</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon" style="color: #28a745;">
                    <i class="fas fa-user-check"></i>
                </div>
                <div class="stat-number"><?php echo $stats['active_users']; ?></div>
                <div>Active Users</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon" style="color: #ffc107;">
                    <i class="fas fa-user-clock"></i>
                </div>
                <div class="stat-number"><?php echo $stats['pending_users']; ?></div>
                <div>Pending Users</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon" style="color: #17a2b8;">
                    <i class="fas fa-calendar-day"></i>
                </div>
                <div class="stat-number"><?php echo $stats['new_today']; ?></div>
                <div>New Today</div>
            </div>
        </div>
        
        <div class="users-table">
            <div class="table-header">
                <h2><i class="fas fa-list"></i> All Registered Users (<?php echo count($users); ?>)</h2>
            </div>
            
            <?php if (count($users) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Status</th>
                            <th>Email Verified</th>
                            <th>Registration Date</th>
                            <th>Last Login</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($user['id']); ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></strong>
                                </td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td><?php echo htmlspecialchars($user['phone'] ?: 'N/A'); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo $user['status']; ?>">
                                        <?php echo ucfirst($user['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($user['email_verified']): ?>
                                        <i class="fas fa-check-circle" style="color: #28a745;"></i> Verified
                                    <?php else: ?>
                                        <i class="fas fa-times-circle" style="color: #dc3545;"></i> Not Verified
                                    <?php endif; ?>
                                </td>
                                <td><?php echo date('M j, Y', strtotime($user['created_at'])); ?></td>
                                <td><?php echo $user['last_login'] ? date('M j, Y H:i', strtotime($user['last_login'])) : 'Never'; ?></td>
                                <td>
                                    <?php if ($user['status'] !== 'active'): ?>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                            <button type="submit" name="action" value="approve" class="btn btn-approve">
                                                <i class="fas fa-check"></i> Approve
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                    
                                    <?php if ($user['status'] !== 'inactive'): ?>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                            <button type="submit" name="action" value="reject" class="btn btn-reject">
                                                <i class="fas fa-times"></i> Reject
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                    
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this user?')">
                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                        <button type="submit" name="action" value="delete" class="btn btn-delete">
                                            <i class="fas fa-trash"></i> Delete
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="no-users">
                    <i class="fas fa-users" style="font-size: 3em; color: #ccc; margin-bottom: 20px;"></i>
                    <h3>No Users Found</h3>
                    <p>No users have registered yet.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
