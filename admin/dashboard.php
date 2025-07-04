<?php
session_start();
require_once '../config/database.php';
require_once 'includes/admin-layout.php';

try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    // Get admin info
    $stmt = $pdo->prepare("SELECT * FROM admins WHERE admin_id = ?");
    $stmt->execute([$_SESSION['admin_id']]);
    $admin = $stmt->fetch();
    
    // Get dashboard statistics
    $stats = [];
    
    // Total bookings
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM service_bookings");
        $stats['total_bookings'] = $stmt->fetch()['count'];
    } catch (Exception $e) {
        $stats['total_bookings'] = 0;
    }
    
    // Pending approvals
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM service_bookings WHERE status = 'waiting_approval'");
        $stats['pending_approvals'] = $stmt->fetch()['count'];
    } catch (Exception $e) {
        $stats['pending_approvals'] = 0;
    }
    
    // Completed bookings
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM service_bookings WHERE status = 'completed'");
        $stats['completed_bookings'] = $stmt->fetch()['count'];
    } catch (Exception $e) {
        $stats['completed_bookings'] = 0;
    }
    
    // Total revenue from payments table for accuracy
    try {
        $stmt = $pdo->query("SELECT SUM(amount) as total FROM payments WHERE payment_status = 'completed'");
        $result = $stmt->fetch();
        $stats['total_revenue'] = $result['total'] ?: 0;
    } catch (Exception $e) {
        // Fallback to service_bookings table
        $stmt = $pdo->query("SELECT SUM(total_amount) as total FROM service_bookings WHERE status IN ('completed', 'paid')");
        $result = $stmt->fetch();
        $stats['total_revenue'] = $result['total'] ?: 0;
    }
    
    // Crypto payments statistics
    try {
        $stmt = $pdo->query("
            SELECT 
                COUNT(*) as crypto_payments_count,
                SUM(amount) as crypto_revenue
            FROM payments 
            WHERE payment_method IN ('usdt', 'bitcoin') AND payment_status = 'completed'
        ");
        $crypto_stats = $stmt->fetch();
        $stats['crypto_payments'] = $crypto_stats['crypto_payments_count'] ?: 0;
        $stats['crypto_revenue'] = $crypto_stats['crypto_revenue'] ?: 0;
    } catch (Exception $e) {
        $stats['crypto_payments'] = 0;
        $stats['crypto_revenue'] = 0;
    }
    
    // Total users
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
        $stats['total_users'] = $stmt->fetch()['count'];
    } catch (Exception $e) {
        $stats['total_users'] = 0;
    }
    
    // Active users (logged in last 30 days)
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE last_activity > DATE_SUB(NOW(), INTERVAL 30 DAY)");
        $stats['active_users'] = $stmt->fetch()['count'];
    } catch (Exception $e) {
        $stats['active_users'] = 0;
    }
    
    // Recent bookings
    try {
        $stmt = $pdo->prepare("
            SELECT sb.*, s.title as service_name, CONCAT(u.first_name, ' ', u.last_name) as user_name
            FROM service_bookings sb
            LEFT JOIN services s ON sb.service_id = s.id
            LEFT JOIN users u ON sb.user_id = u.id
            ORDER BY sb.created_at DESC
            LIMIT 10
        ");
        $stmt->execute();
        $recent_bookings = $stmt->fetchAll();
    } catch (Exception $e) {
        $recent_bookings = [];
    }
    
    // Recent users
    try {
        $stmt = $pdo->query("
            SELECT id, first_name, last_name, email, status, created_at
            FROM users
            ORDER BY created_at DESC
            LIMIT 10
        ");
        $recent_users = $stmt->fetchAll();
    } catch (Exception $e) {
        $recent_users = [];
    }
    
} catch (Exception $e) {
    $stats = [
        'total_bookings' => 0,
        'pending_approvals' => 0,
        'completed_bookings' => 0,
        'total_revenue' => 0,
        'total_users' => 0,
        'active_users' => 0
    ];
    $recent_bookings = [];
    $recent_users = [];
}

// Build the dashboard content
ob_start();
?>

<div class="page-header">
    <h1 class="page-title">Dashboard</h1>
    <p class="page-subtitle">Welcome back, <?php echo htmlspecialchars($admin['first_name'] . ' ' . $admin['last_name']); ?>. Here's what's happening today.</p>
</div>

<!-- Statistics Cards -->
<div class="stats-grid">
    <?php echo renderStatsCard('fas fa-calendar-check', $stats['total_bookings'], 'Total Bookings'); ?>
    <?php echo renderStatsCard('fas fa-clock', $stats['pending_approvals'], 'Pending Approvals'); ?>
    <?php echo renderStatsCard('fas fa-check-circle', $stats['completed_bookings'], 'Completed'); ?>
    <?php echo renderStatsCard('fas fa-dollar-sign', '$' . number_format($stats['total_revenue'], 2), 'Total Revenue'); ?>
    <?php echo renderStatsCard('fas fa-users', $stats['total_users'], 'Total Users'); ?>
    <?php echo renderStatsCard('fas fa-user-check', $stats['active_users'], 'Active Users'); ?>
</div>

<!-- Crypto Payments Stats -->
<?php if ($stats['crypto_payments'] > 0): ?>
<div class="crypto-stats-section">
    <h3><i class="fab fa-bitcoin"></i> Cryptocurrency Payments</h3>
    <div class="stats-grid crypto-stats">
        <?php echo renderStatsCard('fab fa-bitcoin', $stats['crypto_payments'], 'Crypto Payments', 'warning'); ?>
        <?php echo renderStatsCard('fas fa-coins', '$' . number_format($stats['crypto_revenue'], 2), 'Crypto Revenue', 'warning'); ?>
        <?php
        $crypto_percentage = $stats['total_revenue'] > 0 ? round(($stats['crypto_revenue'] / $stats['total_revenue']) * 100, 1) : 0;
        echo renderStatsCard('fas fa-percentage', $crypto_percentage . '%', 'Crypto Share', 'info');
        ?>
    </div>
</div>
<?php endif; ?>

<!-- Content Grid -->
<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
    <!-- Recent Bookings -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Recent Bookings</h3>
        </div>
        <div class="card-body">
            <?php if (empty($recent_bookings)): ?>
                <p class="text-muted">No recent bookings found.</p>
            <?php else: ?>
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Reference</th>
                                <th>Service</th>
                                <th>Client</th>
                                <th>Status</th>
                                <th>Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_bookings as $booking): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($booking['booking_reference']); ?></td>
                                <td><?php echo htmlspecialchars($booking['service_name'] ?: 'Unknown Service'); ?></td>
                                <td><?php echo htmlspecialchars($booking['client_name'] ?: $booking['user_name'] ?: 'Unknown'); ?></td>
                                <td><?php echo renderStatusBadge($booking['status']); ?></td>
                                <td>$<?php echo number_format($booking['total_amount'], 2); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
        <div class="card-footer">
            <a href="bookings.php" class="btn btn-primary btn-sm">View All Bookings</a>
        </div>
    </div>
    
    <!-- Recent Users -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Recent Users</h3>
        </div>
        <div class="card-body">
            <?php if (empty($recent_users)): ?>
                <p class="text-muted">No recent users found.</p>
            <?php else: ?>
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Status</th>
                                <th>Joined</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_users as $user): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td><?php echo renderStatusBadge($user['status']); ?></td>
                                <td><?php echo date('M j, Y', strtotime($user['created_at'])); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
        <div class="card-footer">
            <a href="users.php" class="btn btn-primary btn-sm">View All Users</a>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="card mt-4">
    <div class="card-header">
        <h3 class="card-title">Quick Actions</h3>
    </div>
    <div class="card-body">
        <div class="d-flex gap-3">
            <?php echo renderActionButton('users.php', 'users', 'Manage Users'); ?>
            <?php echo renderActionButton('bookings.php', 'calendar-check', 'View Bookings'); ?>
            <?php echo renderActionButton('services.php', 'cogs', 'Manage Services'); ?>
            <?php echo renderActionButton('email-templates.php', 'envelope', 'Email Templates'); ?>
            <?php echo renderActionButton('settings.php', 'cog', 'Settings'); ?>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
echo renderAdminLayout('Dashboard', 'dashboard', $content);
?>
