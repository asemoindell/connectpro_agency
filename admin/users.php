<?php
session_start();
require_once '../config/database.php';
require_once 'includes/admin-layout.php';

$database = new Database();
$db = $database->getConnection();

// Handle user approval/rejection actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && isset($_POST['user_id'])) {
        $action = $_POST['action'];
        $user_id = $_POST['user_id'];
        $admin_notes = $_POST['admin_notes'] ?? '';
        
        if ($action === 'approve') {
            $stmt = $db->prepare("UPDATE users SET status = 'approved', approved_by = ?, approved_at = NOW(), admin_notes = ? WHERE id = ?");
            $stmt->execute([$_SESSION['admin_id'], $admin_notes, $user_id]);
            $success_message = "User approved successfully!";
        } elseif ($action === 'reject') {
            $stmt = $db->prepare("UPDATE users SET status = 'rejected', approved_by = ?, approved_at = NOW(), admin_notes = ? WHERE id = ?");
            $stmt->execute([$_SESSION['admin_id'], $admin_notes, $user_id]);
            $success_message = "User rejected successfully!";
        } elseif ($action === 'delete') {
            $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $success_message = "User deleted successfully!";
        } elseif ($action === 'reset_password') {
            // Generate a new random password
            $new_password = generateRandomPassword();
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            
            $stmt = $db->prepare("UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$hashed_password, $user_id]);
            
            // Store the plain password temporarily for display
            $_SESSION['temp_password_' . $user_id] = $new_password;
            $success_message = "Password reset successfully! New password: " . $new_password;
        }
    }
}

// Function to generate random password
function generateRandomPassword($length = 12) {
    $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*';
    $password = '';
    for ($i = 0; $i < $length; $i++) {
        $password .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $password;
}

// Get filters
$status_filter = $_GET['status'] ?? 'all';
$search = $_GET['search'] ?? '';

// Build query with filters
$query = "
    SELECT 
        u.*,
        a.first_name as approved_by_name
    FROM users u
    LEFT JOIN admins a ON u.approved_by = a.admin_id
    WHERE 1=1
";

$params = [];

if ($status_filter !== 'all') {
    $query .= " AND u.status = ?";
    $params[] = $status_filter;
}

if ($search) {
    $query .= " AND (CONCAT(u.first_name, ' ', u.last_name) LIKE ? OR u.email LIKE ? OR u.phone LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$query .= " ORDER BY u.created_at DESC";

$stmt = $db->prepare($query);
$stmt->execute($params);
$users = $stmt->fetchAll();

// Get statistics
$stats = [];
$stmt = $db->query("SELECT status, COUNT(*) as count FROM users GROUP BY status");
$status_counts = $stmt->fetchAll();
foreach ($status_counts as $status) {
    $stats[$status['status']] = $status['count'];
}

// Get admin info
$stmt = $db->prepare("SELECT * FROM admins WHERE admin_id = ?");
$stmt->execute([$_SESSION['admin_id']]);
$admin = $stmt->fetch();

// Build the content
ob_start();
?>

<?php if (isset($success_message)): ?>
<div class="alert alert-success">
    <i class="fas fa-check-circle"></i>
    <?php echo htmlspecialchars($success_message); ?>
</div>
<?php endif; ?>

<div class="page-header">
    <h1 class="page-title">User Management</h1>
    <p class="page-subtitle">Manage and monitor all registered users</p>
</div>

<!-- Statistics Cards -->
<div class="stats-grid">
    <?php echo renderStatsCard('fas fa-users', array_sum($stats), 'Total Users'); ?>
    <?php echo renderStatsCard('fas fa-clock', $stats['pending'] ?? 0, 'Pending Approval'); ?>
    <?php echo renderStatsCard('fas fa-check-circle', $stats['approved'] ?? 0, 'Approved Users'); ?>
    <?php echo renderStatsCard('fas fa-times-circle', $stats['rejected'] ?? 0, 'Rejected Users'); ?>
</div>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="d-flex gap-3 align-items-end">
            <div class="form-group" style="margin-bottom: 0;">
                <label for="status" class="form-label">Filter by Status</label>
                <select name="status" id="status" class="form-control form-select">
                    <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>All Users</option>
                    <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="approved" <?php echo $status_filter === 'approved' ? 'selected' : ''; ?>>Approved</option>
                    <option value="rejected" <?php echo $status_filter === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                </select>
            </div>
            
            <div class="form-group" style="margin-bottom: 0;">
                <label for="search" class="form-label">Search Users</label>
                <input type="text" name="search" id="search" class="form-control" 
                       placeholder="Search by name, email, or phone..." 
                       value="<?php echo htmlspecialchars($search); ?>">
            </div>
            
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-search"></i>
                Search
            </button>
            
            <a href="users.php" class="btn btn-outline">
                <i class="fas fa-times"></i>
                Clear
            </a>
        </form>
    </div>
</div>

<!-- Users Table -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Users (<?php echo count($users); ?> found)</h3>
    </div>
    
    <?php if (empty($users)): ?>
        <div class="card-body">
            <div class="text-center py-5">
                <i class="fas fa-users text-muted" style="font-size: 3rem; margin-bottom: 1rem;"></i>
                <h4 class="text-muted">No users found</h4>
                <p class="text-muted">No users match your current filters.</p>
            </div>
        </div>
    <?php else: ?>
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Contact</th>
                        <th>Password</th>
                        <th>Status</th>
                        <th>Joined</th>
                        <th>Approved By</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                    <tr>
                        <td>
                            <div class="d-flex align-items-center gap-3">
                                <div class="user-avatar">
                                    <?php echo strtoupper(substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1)); ?>
                                </div>
                                <div>
                                    <div style="font-weight: 500;">
                                        <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>
                                    </div>
                                    <?php if ($user['admin_notes']): ?>
                                        <div style="font-size: 0.75rem; color: var(--text-muted);">
                                            <i class="fas fa-sticky-note"></i>
                                            <?php echo htmlspecialchars(substr($user['admin_notes'], 0, 50)) . (strlen($user['admin_notes']) > 50 ? '...' : ''); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div><?php echo htmlspecialchars($user['email']); ?></div>
                            <div style="font-size: 0.875rem; color: var(--text-muted);">
                                <?php echo htmlspecialchars($user['phone']); ?>
                            </div>
                        </td>
                        <td>
                            <div class="password-container">
                                <?php 
                                // Check if there's a temporary password for this user
                                $temp_password = $_SESSION['temp_password_' . $user['id']] ?? null;
                                if ($temp_password) {
                                    unset($_SESSION['temp_password_' . $user['id']]);
                                    echo '<div class="alert alert-info alert-sm mb-2">
                                            <strong>New Password:</strong> ' . htmlspecialchars($temp_password) . '
                                            <button type="button" class="btn btn-link btn-sm p-0 ms-2" 
                                                    onclick="copyText(\'' . htmlspecialchars($temp_password) . '\')" 
                                                    title="Copy New Password">
                                                <i class="fas fa-copy"></i>
                                            </button>
                                          </div>';
                                }
                                ?>
                                <div class="password-display">
                                    <span class="password-hidden" id="password-hidden-<?php echo $user['id']; ?>">
                                        <span class="password-dots">••••••••••</span>
                                        <button type="button" class="btn btn-link btn-sm p-0 ms-2" 
                                                onclick="togglePassword(<?php echo $user['id']; ?>)" 
                                                title="Show Hashed Password">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </span>
                                    <span class="password-visible" id="password-visible-<?php echo $user['id']; ?>" style="display: none;">
                                        <span class="password-text small"><?php echo htmlspecialchars(substr($user['password'], 0, 30)) . '...'; ?></span>
                                        <button type="button" class="btn btn-link btn-sm p-0 ms-2" 
                                                onclick="togglePassword(<?php echo $user['id']; ?>)" 
                                                title="Hide Password">
                                            <i class="fas fa-eye-slash"></i>
                                        </button>
                                        <button type="button" class="btn btn-link btn-sm p-0 ms-1" 
                                                onclick="copyText('<?php echo htmlspecialchars($user['password']); ?>')" 
                                                title="Copy Hashed Password">
                                            <i class="fas fa-copy"></i>
                                        </button>
                                    </span>
                                </div>
                                <div class="password-actions mt-1">
                                    <form method="POST" style="display: inline;" 
                                          onsubmit="return confirm('Are you sure you want to reset this user\'s password? A new random password will be generated.')">
                                        <input type="hidden" name="action" value="reset_password">
                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                        <button type="submit" class="btn btn-outline-warning btn-sm" title="Reset Password">
                                            <i class="fas fa-key"></i> Reset
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </td>
                        <td><?php echo renderStatusBadge($user['status']); ?></td>
                        <td>
                            <div><?php echo date('M j, Y', strtotime($user['created_at'])); ?></div>
                            <div style="font-size: 0.75rem; color: var(--text-muted);">
                                <?php echo date('g:i A', strtotime($user['created_at'])); ?>
                            </div>
                        </td>
                        <td>
                            <?php if ($user['approved_by_name']): ?>
                                <div style="font-size: 0.875rem;"><?php echo htmlspecialchars($user['approved_by_name']); ?></div>
                                <div style="font-size: 0.75rem; color: var(--text-muted);">
                                    <?php echo date('M j, Y', strtotime($user['approved_at'])); ?>
                                </div>
                            <?php else: ?>
                                <span class="text-muted">-</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="d-flex gap-2">
                                <?php if ($user['status'] === 'pending'): ?>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="action" value="approve">
                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                        <button type="submit" class="btn btn-success btn-sm" title="Approve User">
                                            <i class="fas fa-check"></i>
                                        </button>
                                    </form>
                                    
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="action" value="reject">
                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                        <button type="submit" class="btn btn-danger btn-sm" title="Reject User">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </form>
                                <?php endif; ?>
                                
                                <a href="user-details.php?id=<?php echo $user['id']; ?>" class="btn btn-primary btn-sm" title="View Details">
                                    <i class="fas fa-eye"></i>
                                </a>
                                
                                <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this user?')">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                    <button type="submit" class="btn btn-danger btn-sm" title="Delete User">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php
$content = ob_get_clean();

// Add JavaScript for password functionality
$additional_js = "
<script>
function togglePassword(userId) {
    const hiddenElement = document.getElementById('password-hidden-' + userId);
    const visibleElement = document.getElementById('password-visible-' + userId);
    
    if (hiddenElement.style.display === 'none') {
        hiddenElement.style.display = 'inline';
        visibleElement.style.display = 'none';
    } else {
        hiddenElement.style.display = 'none';
        visibleElement.style.display = 'inline';
    }
}

function copyText(text) {
    // Create a temporary textarea element
    const tempTextarea = document.createElement('textarea');
    tempTextarea.value = text;
    document.body.appendChild(tempTextarea);
    
    // Select and copy the text
    tempTextarea.select();
    document.execCommand('copy');
    
    // Remove the temporary element
    document.body.removeChild(tempTextarea);
    
    // Show feedback
    showCopyFeedback();
}

function showCopyFeedback() {
    // Create a toast notification
    const toast = document.createElement('div');
    toast.className = 'toast-notification';
    toast.innerHTML = '<i class=\"fas fa-check\"></i> Copied to clipboard!';
    document.body.appendChild(toast);
    
    // Show toast
    setTimeout(() => toast.classList.add('show'), 100);
    
    // Remove toast after 3 seconds
    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => document.body.removeChild(toast), 300);
    }, 3000);
}

// Auto-hide temporary password alerts after 10 seconds
document.addEventListener('DOMContentLoaded', function() {
    const tempPasswordAlerts = document.querySelectorAll('.alert-info.alert-sm');
    tempPasswordAlerts.forEach(alert => {
        setTimeout(() => {
            alert.style.transition = 'opacity 0.5s ease';
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 500);
        }, 10000);
    });
});
</script>

<style>
.password-container {
    font-family: monospace;
    font-size: 0.875rem;
    max-width: 200px;
}

.password-dots {
    color: var(--text-muted);
    letter-spacing: 2px;
}

.password-text {
    background-color: #f8f9fa;
    padding: 2px 6px;
    border-radius: 4px;
    border: 1px solid #dee2e6;
    font-family: monospace;
    font-size: 0.75rem;
    word-break: break-all;
}

.password-container .btn-link {
    color: #6c757d;
    text-decoration: none;
    border: none;
    background: none;
    padding: 0;
    margin: 0;
    line-height: 1;
}

.password-container .btn-link:hover {
    color: #495057;
}

.password-container .btn-link:focus {
    outline: none;
    box-shadow: none;
}

.password-container .btn-link i {
    font-size: 0.875rem;
}

.password-actions {
    margin-top: 0.5rem;
}

.alert-sm {
    padding: 0.375rem 0.75rem;
    font-size: 0.75rem;
}

.toast-notification {
    position: fixed;
    top: 20px;
    right: 20px;
    background: #28a745;
    color: white;
    padding: 10px 20px;
    border-radius: 4px;
    z-index: 9999;
    transform: translateX(100%);
    transition: transform 0.3s ease;
}

.toast-notification.show {
    transform: translateX(0);
}

.toast-notification i {
    margin-right: 5px;
}

/* Make password column more responsive */
@media (max-width: 768px) {
    .password-container {
        max-width: 150px;
    }
    
    .password-text {
        font-size: 0.7rem;
    }
}
</style>
";

echo renderAdminLayout('User Management', 'users', $content . $additional_js);
?>
