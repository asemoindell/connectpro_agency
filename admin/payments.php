<?php
session_start();
require_once '../config/database.php';
require_once 'includes/admin-functions.php';
require_once 'includes/admin-layout.php';

if (!isAdminLoggedIn()) {
    header('Location: login.php');
    exit;
}

$database = new Database();
$db = $database->getConnection();

// Get admin info
$admin = getCurrentAdmin();

// Get filters
$status_filter = $_GET['status'] ?? '';
$method_filter = $_GET['method'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';
$search = $_GET['search'] ?? '';

// Handle payment actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $payment_id = $_POST['payment_id'] ?? '';
    
    if ($action === 'update_status' && $payment_id) {
        $new_status = $_POST['new_status'] ?? '';
        $admin_notes = $_POST['admin_notes'] ?? '';
        
        $stmt = $db->prepare("UPDATE payments SET payment_status = ?, admin_notes = ? WHERE payment_id = ?");
        if ($stmt->execute([$new_status, $admin_notes, $payment_id])) {
            $_SESSION['success_message'] = 'Payment status updated successfully.';
        } else {
            $_SESSION['error_message'] = 'Failed to update payment status.';
        }
        header('Location: payments.php');
        exit;
    }
}

// Build query with filters
$where_conditions = [];
$params = [];

if ($status_filter) {
    $where_conditions[] = "p.payment_status = ?";
    $params[] = $status_filter;
}

if ($method_filter) {
    $where_conditions[] = "p.payment_method = ?";
    $params[] = $method_filter;
}

if ($date_from) {
    $where_conditions[] = "DATE(p.created_at) >= ?";
    $params[] = $date_from;
}

if ($date_to) {
    $where_conditions[] = "DATE(p.created_at) <= ?";
    $params[] = $date_to;
}

if ($search) {
    $where_conditions[] = "(p.payment_reference LIKE ? OR b.booking_reference LIKE ? OR b.client_name LIKE ? OR p.gateway_transaction_id LIKE ?)";
    $search_param = "%{$search}%";
    $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param]);
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Get payments
$query = "
    SELECT 
        p.*,
        b.booking_reference,
        b.client_name,
        b.client_email,
        s.title as service_name
    FROM payments p
    JOIN service_bookings b ON p.booking_id = b.id
    LEFT JOIN services s ON b.service_id = s.id
    {$where_clause}
    ORDER BY p.created_at DESC
    LIMIT 50
";

$stmt = $db->prepare($query);
$stmt->execute($params);
$payments = $stmt->fetchAll();

// Get payment statistics
$stats_query = "
    SELECT 
        COUNT(*) as total_payments,
        SUM(CASE WHEN p.payment_status = 'completed' THEN p.amount ELSE 0 END) as total_revenue,
        SUM(CASE WHEN p.payment_status = 'pending' THEN p.amount ELSE 0 END) as pending_amount,
        COUNT(CASE WHEN p.payment_status = 'completed' THEN 1 END) as completed_payments,
        COUNT(CASE WHEN p.payment_status = 'pending' THEN 1 END) as pending_payments,
        COUNT(CASE WHEN p.payment_status = 'failed' THEN 1 END) as failed_payments
    FROM payments p
    JOIN service_bookings b ON p.booking_id = b.id
    {$where_clause}
";

$stats_stmt = $db->prepare($stats_query);
$stats_stmt->execute($params);
$stats = $stats_stmt->fetch();

// Get payment method breakdown
$methods_query = "
    SELECT 
        p.payment_method,
        COUNT(*) as count,
        SUM(p.amount) as total_amount
    FROM payments p
    JOIN service_bookings b ON p.booking_id = b.id
    {$where_clause}
    GROUP BY p.payment_method
    ORDER BY total_amount DESC
";

$methods_stmt = $db->prepare($methods_query);
$methods_stmt->execute($params);
$payment_methods = $methods_stmt->fetchAll();
?>

<?php
// Start output buffering to capture content
ob_start();
?>

<div class="admin-header">
    <h1><i class="fas fa-credit-card"></i> Payment Management</h1>
    <p>Monitor and manage all payment transactions</p>
</div>

<?php if (isset($_SESSION['success_message'])): ?>
    <div class="alert alert-success">
        <i class="fas fa-check-circle"></i>
        <?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?>
    </div>
<?php endif; ?>

<?php if (isset($_SESSION['error_message'])): ?>
    <div class="alert alert-error">
        <i class="fas fa-exclamation-circle"></i>
        <?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?>
    </div>
<?php endif; ?>

<!-- Payment Statistics -->
<div class="stats-grid">
    <?php
    echo createStatsCard(
        'Total Revenue',
        '$' . formatCurrency($stats['total_revenue'] ?? 0),
        'fas fa-dollar-sign',
        'success'
    );
    
    echo createStatsCard(
        'Total Payments',
        formatCurrency($stats['total_payments'] ?? 0, 0),
        'fas fa-credit-card',
        'primary'
    );
    
    echo createStatsCard(
        'Pending Amount',
        '$' . formatCurrency($stats['pending_amount'] ?? 0),
        'fas fa-clock',
        'warning'
    );
    
    echo createStatsCard(
        'Failed Payments',
        formatCurrency($stats['failed_payments'] ?? 0, 0),
        'fas fa-exclamation-triangle',
        'danger'
    );
    ?>
</div>

<!-- Payment Methods Breakdown -->
<div class="row">
    <div class="col-md-4">
        <div class="admin-card">
            <div class="card-header">
                <h3><i class="fas fa-chart-pie"></i> Payment Methods</h3>
            </div>
            <div class="card-body">
                <?php if (empty($payment_methods)): ?>
                    <p class="text-muted">No payment data available</p>
                <?php else: ?>
                    <?php foreach ($payment_methods as $method): ?>
                        <div class="method-stat">
                            <div class="method-info">
                                <span class="method-name">
                                    <?php
                                    $icons = [
                                        'stripe' => 'fab fa-stripe',
                                        'paypal' => 'fab fa-paypal',
                                        'usdt' => 'fab fa-bitcoin',
                                        'bitcoin' => 'fab fa-bitcoin',
                                        'bank_transfer' => 'fas fa-university'
                                    ];
                                    $icon = $icons[$method['payment_method']] ?? 'fas fa-credit-card';
                                    ?>
                                    <i class="<?php echo $icon; ?>"></i>
                                    <?php echo ucfirst(str_replace('_', ' ', $method['payment_method'])); ?>
                                </span>
                                <span class="method-amount">$<?php echo formatCurrency($method['total_amount']); ?></span>
                            </div>
                            <div class="method-count"><?php echo $method['count']; ?> transactions</div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-md-8">
        <!-- Filters -->
        <div class="admin-card">
            <div class="card-header">
                <h3><i class="fas fa-filter"></i> Filters</h3>
            </div>
            <div class="card-body">
                <form method="GET" class="filter-form">
                    <div class="row">
                        <div class="col-md-3">
                            <label>Status</label>
                            <select name="status" class="form-control">
                                <option value="">All Statuses</option>
                                <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="processing" <?php echo $status_filter === 'processing' ? 'selected' : ''; ?>>Processing</option>
                                <option value="completed" <?php echo $status_filter === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                <option value="failed" <?php echo $status_filter === 'failed' ? 'selected' : ''; ?>>Failed</option>
                                <option value="refunded" <?php echo $status_filter === 'refunded' ? 'selected' : ''; ?>>Refunded</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label>Payment Method</label>
                            <select name="method" class="form-control">
                                <option value="">All Methods</option>
                                <option value="stripe" <?php echo $method_filter === 'stripe' ? 'selected' : ''; ?>>Stripe</option>
                                <option value="paypal" <?php echo $method_filter === 'paypal' ? 'selected' : ''; ?>>PayPal</option>
                                <option value="usdt" <?php echo $method_filter === 'usdt' ? 'selected' : ''; ?>>USDT</option>
                                <option value="bitcoin" <?php echo $method_filter === 'bitcoin' ? 'selected' : ''; ?>>Bitcoin</option>
                                <option value="bank_transfer" <?php echo $method_filter === 'bank_transfer' ? 'selected' : ''; ?>>Bank Transfer</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label>From Date</label>
                            <input type="date" name="date_from" value="<?php echo $date_from; ?>" class="form-control">
                        </div>
                        <div class="col-md-2">
                            <label>To Date</label>
                            <input type="date" name="date_to" value="<?php echo $date_to; ?>" class="form-control">
                        </div>
                        <div class="col-md-2">
                            <label>Search</label>
                            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Payment ID, Booking..." class="form-control">
                        </div>
                    </div>
                    <div class="filter-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i> Apply Filters
                        </button>
                        <a href="payments.php" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Clear
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Payments Table -->
<div class="admin-card">
    <div class="card-header">
        <h3><i class="fas fa-list"></i> Payment Transactions</h3>
        <div class="card-actions">
            <button onclick="exportPayments()" class="btn btn-secondary">
                <i class="fas fa-download"></i> Export
            </button>
        </div>
    </div>
    <div class="card-body">
        <?php if (empty($payments)): ?>
            <div class="empty-state">
                <i class="fas fa-credit-card"></i>
                <h4>No Payments Found</h4>
                <p>No payment transactions match your current filters.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Payment ID</th>
                            <th>Booking</th>
                            <th>Client</th>
                            <th>Service</th>
                            <th>Amount</th>
                            <th>Method</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($payments as $payment): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($payment['payment_reference']); ?></strong>
                                    <?php if ($payment['gateway_transaction_id']): ?>
                                        <br><small class="text-muted"><?php echo htmlspecialchars($payment['gateway_transaction_id']); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="bookings.php?search=<?php echo urlencode($payment['booking_reference']); ?>" 
                                       class="booking-link">
                                        <?php echo htmlspecialchars($payment['booking_reference']); ?>
                                    </a>
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($payment['client_name']); ?></strong>
                                    <br><small><?php echo htmlspecialchars($payment['client_email']); ?></small>
                                </td>
                                <td><?php echo htmlspecialchars($payment['service_name'] ?? 'N/A'); ?></td>
                                <td>
                                    <strong class="amount">$<?php echo formatCurrency($payment['amount']); ?></strong>
                                    <?php if ($payment['currency'] && $payment['currency'] !== 'USD'): ?>
                                        <br><small><?php echo $payment['currency']; ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php
                                    $method_icons = [
                                        'stripe' => 'fab fa-stripe',
                                        'paypal' => 'fab fa-paypal',
                                        'usdt' => 'fab fa-bitcoin',
                                        'bitcoin' => 'fab fa-bitcoin',
                                        'bank_transfer' => 'fas fa-university'
                                    ];
                                    $icon = $method_icons[$payment['payment_method']] ?? 'fas fa-credit-card';
                                    $method_name = ucfirst(str_replace('_', ' ', $payment['payment_method']));
                                    ?>
                                    <span class="payment-method">
                                        <i class="<?php echo $icon; ?>"></i>
                                        <?php echo $method_name; ?>
                                    </span>
                                </td>
                                <td>
                                    <?php echo createStatusBadge($payment['payment_status']); ?>
                                    <?php if ($payment['paid_at']): ?>
                                        <br><small class="text-muted">Paid: <?php echo date('M j, Y', strtotime($payment['paid_at'])); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <strong><?php echo date('M j, Y', strtotime($payment['created_at'])); ?></strong>
                                    <br><small><?php echo date('H:i A', strtotime($payment['created_at'])); ?></small>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <button onclick="viewPayment(<?php echo $payment['payment_id']; ?>)" 
                                                class="btn btn-sm btn-primary" title="View Details">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <?php if (in_array($payment['payment_status'], ['pending', 'processing'])): ?>
                                            <button onclick="updatePaymentStatus(<?php echo $payment['payment_id']; ?>, '<?php echo $payment['payment_status']; ?>')" 
                                                    class="btn btn-sm btn-warning" title="Update Status">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                        <?php endif; ?>
                                        <?php if ($payment['payment_status'] === 'completed'): ?>
                                            <button onclick="issueRefund(<?php echo $payment['payment_id']; ?>)" 
                                                    class="btn btn-sm btn-danger" title="Issue Refund">
                                                <i class="fas fa-undo"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Payment Status Update Modal -->
<div id="statusModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Update Payment Status</h3>
            <span class="close" onclick="closeModal('statusModal')">&times;</span>
        </div>
        <form method="POST" id="statusForm">
            <div class="modal-body">
                <input type="hidden" name="action" value="update_status">
                <input type="hidden" name="payment_id" id="modal_payment_id">
                
                <div class="form-group">
                    <label>New Status</label>
                    <select name="new_status" id="modal_new_status" class="form-control" required>
                        <option value="pending">Pending</option>
                        <option value="processing">Processing</option>
                        <option value="completed">Completed</option>
                        <option value="failed">Failed</option>
                        <option value="refunded">Refunded</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Admin Notes</label>
                    <textarea name="admin_notes" class="form-control" rows="3" 
                              placeholder="Add notes about this status change..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" onclick="closeModal('statusModal')" class="btn btn-secondary">Cancel</button>
                <button type="submit" class="btn btn-primary">Update Status</button>
            </div>
        </form>
    </div>
</div>

<style>
.method-stat {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.5rem 0;
    border-bottom: 1px solid #eee;
}

.method-stat:last-child {
    border-bottom: none;
}

.method-info {
    display: flex;
    justify-content: space-between;
    width: 100%;
    align-items: center;
}

.method-name {
    font-weight: 500;
}

.method-name i {
    margin-right: 0.5rem;
    width: 20px;
}

.method-amount {
    font-weight: bold;
    color: var(--success-color);
}

.method-count {
    font-size: 0.85rem;
    color: var(--text-muted);
    margin-top: 0.25rem;
}

.payment-method {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.25rem 0.5rem;
    background: #f8f9fa;
    border-radius: 4px;
    font-size: 0.85rem;
}

.booking-link {
    color: var(--primary-color);
    text-decoration: none;
    font-weight: 500;
}

.booking-link:hover {
    text-decoration: underline;
}

.amount {
    color: var(--success-color);
}

.filter-form .row {
    align-items: end;
}

.filter-actions {
    margin-top: 1rem;
    display: flex;
    gap: 0.5rem;
}

.col-md-2, .col-md-3, .col-md-4, .col-md-8 {
    margin-bottom: 0.5rem;
}
</style>

<script>
function viewPayment(paymentId) {
    // Implement payment details view
    alert('View payment details for ID: ' + paymentId);
}

function updatePaymentStatus(paymentId, currentStatus) {
    document.getElementById('modal_payment_id').value = paymentId;
    document.getElementById('modal_new_status').value = currentStatus;
    document.getElementById('statusModal').style.display = 'block';
}

function issueRefund(paymentId) {
    if (confirm('Are you sure you want to issue a refund for this payment? This action cannot be undone.')) {
        // Implement refund logic
        alert('Refund functionality to be implemented for payment ID: ' + paymentId);
    }
}

function exportPayments() {
    // Get current filter parameters
    const params = new URLSearchParams(window.location.search);
    params.set('export', 'csv');
    
    // Create temporary link and trigger download
    const link = document.createElement('a');
    link.href = 'payments.php?' + params.toString();
    link.download = 'payments_export.csv';
    link.click();
}

function closeModal(modalId) {
    document.getElementById(modalId).style.display = 'none';
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modals = document.querySelectorAll('.modal');
    modals.forEach(modal => {
        if (event.target === modal) {
            modal.style.display = 'none';
        }
    });
};
</script>

<?php
// Capture the content and render with layout
$content = ob_get_clean();
echo renderAdminLayout('Payment Management', 'payments', $content);
?>
