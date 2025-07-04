<?php
session_start();
require_once '../config/database.php';
require_once 'includes/admin-layout.php';

$database = new Database();
$db = $database->getConnection();

// Handle booking actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && isset($_POST['booking_id'])) {
        $action = $_POST['action'];
        $booking_id = $_POST['booking_id'];
        $admin_notes = $_POST['admin_notes'] ?? '';
        
        switch ($action) {
            case 'approve':
                $stmt = $db->prepare("UPDATE service_bookings SET status = 'approved', admin_notes = ? WHERE id = ?");
                $stmt->execute([$admin_notes, $booking_id]);
                $success_message = "Booking approved successfully!";
                break;
            case 'reject':
                $stmt = $db->prepare("UPDATE service_bookings SET status = 'cancelled', admin_notes = ? WHERE id = ?");
                $stmt->execute([$admin_notes, $booking_id]);
                $success_message = "Booking rejected successfully!";
                break;
            case 'complete':
                $stmt = $db->prepare("UPDATE service_bookings SET status = 'completed', completed_at = NOW() WHERE id = ?");
                $stmt->execute([$booking_id]);
                $success_message = "Booking marked as completed!";
                break;
        }
    }
}

// Get filters
$status_filter = $_GET['status'] ?? 'all';
$search = $_GET['search'] ?? '';

// Get booking statistics
$stats_query = "
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN status = 'waiting_approval' THEN 1 ELSE 0 END) as waiting_approval,
        SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
        SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
        SUM(total_amount) as total_revenue
    FROM service_bookings
";
$stats_stmt = $db->prepare($stats_query);
$stats_stmt->execute();
$stats = $stats_stmt->fetch();

// Get recent bookings
$bookings_query = "
    SELECT b.*, s.title as service_name, s.description as service_description,
           c.name as category_name
    FROM service_bookings b
    JOIN services s ON b.service_id = s.id
    LEFT JOIN service_categories c ON s.category = c.name
    WHERE 1=1
";

$params = [];

if ($status_filter !== 'all') {
    $bookings_query .= " AND b.status = ?";
    $params[] = $status_filter;
}

if ($search) {
    $bookings_query .= " AND (b.booking_reference LIKE ? OR b.client_name LIKE ? OR b.client_email LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$bookings_query .= " ORDER BY b.created_at DESC LIMIT 50";

$bookings_stmt = $db->prepare($bookings_query);
$bookings_stmt->execute($params);
$bookings = $bookings_stmt->fetchAll();

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
    <h1 class="page-title">Booking Management</h1>
    <p class="page-subtitle">Monitor and manage all service bookings</p>
</div>

<!-- Statistics Cards -->
<div class="stats-grid">
    <?php echo renderStatsCard('fas fa-calendar-check', $stats['total'], 'Total Bookings'); ?>
    <?php echo renderStatsCard('fas fa-clock', $stats['pending'] + $stats['waiting_approval'], 'Pending Review'); ?>
    <?php echo renderStatsCard('fas fa-spinner', $stats['in_progress'], 'In Progress'); ?>
    <?php echo renderStatsCard('fas fa-check-circle', $stats['completed'], 'Completed'); ?>
    <?php echo renderStatsCard('fas fa-dollar-sign', $stats['total_revenue'], 'Total Revenue'); ?>
</div>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="d-flex gap-3 align-items-end">
            <div class="form-group" style="margin-bottom: 0;">
                <label for="status" class="form-label">Filter by Status</label>
                <select name="status" id="status" class="form-control form-select">
                    <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>All Bookings</option>
                    <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="waiting_approval" <?php echo $status_filter === 'waiting_approval' ? 'selected' : ''; ?>>Waiting Approval</option>
                    <option value="approved" <?php echo $status_filter === 'approved' ? 'selected' : ''; ?>>Approved</option>
                    <option value="in_progress" <?php echo $status_filter === 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                    <option value="completed" <?php echo $status_filter === 'completed' ? 'selected' : ''; ?>>Completed</option>
                    <option value="cancelled" <?php echo $status_filter === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                </select>
            </div>
            
            <div class="form-group" style="margin-bottom: 0;">
                <label for="search" class="form-label">Search Bookings</label>
                <input type="text" name="search" id="search" class="form-control" 
                       placeholder="Search by reference, client name, or email..." 
                       value="<?php echo htmlspecialchars($search); ?>">
            </div>
            
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-search"></i>
                Search
            </button>
            
            <a href="bookings.php" class="btn btn-outline">
                <i class="fas fa-times"></i>
                Clear
            </a>
        </form>
    </div>
</div>

<!-- Bookings Table -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Bookings (<?php echo count($bookings); ?> found)</h3>
    </div>
    
    <?php if (empty($bookings)): ?>
        <div class="card-body">
            <div class="text-center py-5">
                <i class="fas fa-calendar-times text-muted" style="font-size: 3rem; margin-bottom: 1rem;"></i>
                <h4 class="text-muted">No bookings found</h4>
                <p class="text-muted">No bookings match your current filters.</p>
            </div>
        </div>
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
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($bookings as $booking): ?>
                    <tr>
                        <td>
                            <div style="font-weight: 500;"><?php echo htmlspecialchars($booking['booking_reference']); ?></div>
                            <?php if ($booking['urgency_level'] === 'urgent'): ?>
                                <span class="status-badge status-rejected" style="font-size: 0.6rem;">
                                    <i class="fas fa-exclamation-triangle"></i> URGENT
                                </span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div style="font-weight: 500;"><?php echo htmlspecialchars($booking['service_name'] ?? 'Unknown Service'); ?></div>
                            <div style="font-size: 0.75rem; color: var(--text-muted);">
                                <?php echo htmlspecialchars($booking['category_name'] ?? 'No Category'); ?>
                            </div>
                        </td>
                        <td>
                            <div><?php echo htmlspecialchars($booking['client_name']); ?></div>
                            <div style="font-size: 0.875rem; color: var(--text-muted);">
                                <?php echo htmlspecialchars($booking['client_email']); ?>
                            </div>
                        </td>
                        <td><?php echo renderStatusBadge($booking['status']); ?></td>
                        <td>
                            <div style="font-weight: 500;">$<?php echo formatCurrency($booking['total_amount']); ?></div>
                            <?php if ($booking['quoted_price'] != $booking['total_amount']): ?>
                                <div style="font-size: 0.75rem; color: var(--text-muted);">
                                    Quote: $<?php echo formatCurrency($booking['quoted_price']); ?>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div><?php echo date('M j, Y', strtotime($booking['created_at'])); ?></div>
                            <div style="font-size: 0.75rem; color: var(--text-muted);">
                                <?php echo date('g:i A', strtotime($booking['created_at'])); ?>
                            </div>
                        </td>
                        <td>
                            <div class="d-flex gap-2">
                                <?php if ($booking['status'] === 'waiting_approval'): ?>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="action" value="approve">
                                        <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                                        <button type="submit" class="btn btn-success btn-sm" title="Approve Booking">
                                            <i class="fas fa-check"></i>
                                        </button>
                                    </form>
                                    
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="action" value="reject">
                                        <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                                        <button type="submit" class="btn btn-danger btn-sm" title="Reject Booking">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </form>
                                <?php endif; ?>
                                
                                <?php if ($booking['status'] === 'in_progress'): ?>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="action" value="complete">
                                        <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                                        <button type="submit" class="btn btn-success btn-sm" title="Mark as Completed">
                                            <i class="fas fa-check-circle"></i>
                                        </button>
                                    </form>
                                <?php endif; ?>
                                
                                <a href="booking-details.php?id=<?php echo $booking['id']; ?>" class="btn btn-primary btn-sm" title="View Details">
                                    <i class="fas fa-eye"></i>
                                </a>
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
echo renderAdminLayout('Booking Management', 'bookings', $content);
?>
