<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once '../config/database.php';
require_once 'includes/admin-layout.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

// Check if booking ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: bookings.php');
    exit();
}

$booking_id = intval($_GET['id']);
$database = new Database();
$db = $database->getConnection();

// Handle booking actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        
        switch ($action) {
            case 'update_status':
                $new_status = $_POST['status'];
                $admin_notes = $_POST['admin_notes'] ?? '';
                
                $stmt = $db->prepare("UPDATE service_bookings SET status = ?, admin_notes = ?, updated_at = NOW() WHERE id = ?");
                $stmt->execute([$new_status, $admin_notes, $booking_id]);
                $success_message = "Booking status updated successfully!";
                break;
                
            case 'assign_agent':
                $agent_id = $_POST['agent_id'];
                $stmt = $db->prepare("UPDATE service_bookings SET assigned_admin_id = ?, updated_at = NOW() WHERE id = ?");
                $stmt->execute([$agent_id, $booking_id]);
                $success_message = "Agent assigned successfully!";
                break;
                
            case 'update_pricing':
                $quoted_price = floatval($_POST['quoted_price']);
                $agent_fee = floatval($_POST['agent_fee']);
                $total_amount = floatval($_POST['total_amount']);
                
                $stmt = $db->prepare("UPDATE service_bookings SET quoted_price = ?, agent_fee = ?, total_amount = ?, updated_at = NOW() WHERE id = ?");
                $stmt->execute([$quoted_price, $agent_fee, $total_amount, $booking_id]);
                $success_message = "Pricing updated successfully!";
                break;
        }
    }
}

try {
    // Get booking details with related information
    $stmt = $db->prepare("
        SELECT 
            sb.*,
            u.first_name as user_first_name,
            u.last_name as user_last_name,
            u.email as user_email,
            u.phone as user_phone,
            u.created_at as user_joined,
            se.title as service_title,
            se.category as service_category,
            se.description as service_description,
            se.price_range as service_base_price,
            a.first_name as agent_first_name,
            a.last_name as agent_last_name,
            a.email as agent_email
        FROM service_bookings sb
        LEFT JOIN users u ON sb.user_id = u.id
        LEFT JOIN services se ON sb.service_id = se.id
        LEFT JOIN admins a ON sb.assigned_admin_id = a.admin_id
        WHERE sb.id = ?
    ");
    $stmt->execute([$booking_id]);
    $booking = $stmt->fetch();
    
    if (!$booking) {
        header('Location: bookings.php');
        exit();
    }
    
    // Get available agents for assignment
    $stmt = $db->prepare("SELECT admin_id as id, first_name, last_name, email FROM admins WHERE role IN ('agent', 'service-admin', 'super-admin') AND status = 'active' ORDER BY first_name, last_name");
    $stmt->execute();
    $agents = $stmt->fetchAll();
    
    // Get booking history/activity log (if exists)
    $activity_log = []; // Placeholder for future implementation
    
    // Get payment information if exists
    $stmt = $db->prepare("SELECT * FROM payments WHERE booking_id = ? ORDER BY created_at DESC");
    $stmt->execute([$booking_id]);
    $payments = $stmt->fetchAll();
    
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}

// Helper function to safely format currency values
function formatCurrency($value, $decimals = 2) {
    // Convert to float, defaulting to 0 if null, empty, or non-numeric
    $numericValue = is_numeric($value) ? (float)$value : 0;
    return number_format($numericValue, $decimals);
}

function getStatusBadge($status) {
    $badges = [
        'pending' => '<span class="badge bg-warning"><i class="fas fa-clock"></i> Pending</span>',
        'confirmed' => '<span class="badge bg-info"><i class="fas fa-check"></i> Confirmed</span>',
        'waiting_approval' => '<span class="badge bg-warning"><i class="fas fa-hourglass-half"></i> Waiting Approval</span>',
        'approved' => '<span class="badge bg-success"><i class="fas fa-thumbs-up"></i> Approved</span>',
        'payment_pending' => '<span class="badge bg-warning"><i class="fas fa-credit-card"></i> Payment Pending</span>',
        'paid' => '<span class="badge bg-success"><i class="fas fa-money-check"></i> Paid</span>',
        'in_progress' => '<span class="badge bg-primary"><i class="fas fa-spinner"></i> In Progress</span>',
        'completed' => '<span class="badge bg-success"><i class="fas fa-check-circle"></i> Completed</span>',
        'cancelled' => '<span class="badge bg-danger"><i class="fas fa-times-circle"></i> Cancelled</span>'
    ];
    
    return $badges[$status] ?? '<span class="badge bg-secondary">' . ucfirst($status) . '</span>';
}

function getUrgencyBadge($urgency) {
    $badges = [
        'low' => '<span class="badge bg-info"><i class="fas fa-info-circle"></i> Low</span>',
        'medium' => '<span class="badge bg-warning"><i class="fas fa-exclamation"></i> Medium</span>',
        'high' => '<span class="badge bg-danger"><i class="fas fa-exclamation-triangle"></i> High</span>',
        'urgent' => '<span class="badge bg-danger"><i class="fas fa-fire"></i> Urgent</span>'
    ];
    
    return $badges[$urgency] ?? '<span class="badge bg-secondary">' . ucfirst($urgency) . '</span>';
}

// Build the content
ob_start();
?>

<style>
.booking-details-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 2rem;
    border-radius: 10px;
    margin-bottom: 2rem;
}

.info-card {
    border: none;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    border-radius: 10px;
    margin-bottom: 1.5rem;
}

.info-card .card-header {
    background-color: #f8f9fa;
    border-bottom: 1px solid #dee2e6;
    font-weight: 600;
}

.timeline-item {
    padding: 1rem;
    border-left: 3px solid #007bff;
    margin-left: 1rem;
    position: relative;
}

.timeline-item::before {
    content: '';
    position: absolute;
    left: -8px;
    top: 1.5rem;
    width: 12px;
    height: 12px;
    border-radius: 50%;
    background: #007bff;
}

.status-form {
    background: #f8f9fa;
    padding: 1.5rem;
    border-radius: 8px;
    margin-top: 1rem;
}

.pricing-display {
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
    color: white;
    padding: 1.5rem;
    border-radius: 10px;
    text-align: center;
}
</style>

<?php if (isset($success_message)): ?>
<div class="alert alert-success alert-dismissible fade show" role="alert">
    <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success_message); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
<?php endif; ?>

<!-- Booking Header -->
<div class="booking-details-header">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1 class="mb-0">Booking #<?php echo htmlspecialchars($booking['booking_reference']); ?></h1>
            <p class="mb-0 opacity-75">Created on <?php echo date('F j, Y \a\t g:i A', strtotime($booking['created_at'])); ?></p>
        </div>
        <div class="text-end">
            <?php echo getStatusBadge($booking['status']); ?>
            <?php echo getUrgencyBadge($booking['urgency_level']); ?>
        </div>
    </div>
</div>

<!-- Action Buttons -->
<div class="row mb-4">
    <div class="col-md-12">
        <a href="bookings.php" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left"></i> Back to Bookings
        </a>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#statusModal">
            <i class="fas fa-edit"></i> Update Status
        </button>
        <button type="button" class="btn btn-info" data-bs-toggle="modal" data-bs-target="#assignModal">
            <i class="fas fa-user-plus"></i> Assign Agent
        </button>
        <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#pricingModal">
            <i class="fas fa-dollar-sign"></i> Update Pricing
        </button>
    </div>
</div>

<div class="row">
    <!-- Left Column -->
    <div class="col-md-8">
        <!-- Client Information -->
        <div class="card info-card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-user"></i> Client Information</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <table class="table table-borderless">
                            <tr>
                                <td><strong>Name:</strong></td>
                                <td><?php echo htmlspecialchars($booking['client_name']); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Email:</strong></td>
                                <td><?php echo htmlspecialchars($booking['client_email']); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Phone:</strong></td>
                                <td><?php echo htmlspecialchars($booking['client_phone'] ?: 'Not provided'); ?></td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <?php if ($booking['user_id']): ?>
                        <table class="table table-borderless">
                            <tr>
                                <td><strong>Registered User:</strong></td>
                                <td>
                                    <a href="user-details.php?id=<?php echo $booking['user_id']; ?>" class="btn btn-sm btn-outline-primary">
                                        <?php echo htmlspecialchars($booking['user_first_name'] . ' ' . $booking['user_last_name']); ?>
                                    </a>
                                </td>
                            </tr>
                            <tr>
                                <td><strong>User Email:</strong></td>
                                <td><?php echo htmlspecialchars($booking['user_email']); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Joined:</strong></td>
                                <td><?php echo date('M j, Y', strtotime($booking['user_joined'])); ?></td>
                            </tr>
                        </table>
                        <?php else: ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> This booking was made by a guest user.
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Service Information -->
        <div class="card info-card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-cogs"></i> Service Information</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <table class="table table-borderless">
                            <tr>
                                <td><strong>Service:</strong></td>
                                <td><?php echo htmlspecialchars($booking['service_title']); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Category:</strong></td>
                                <td><?php echo htmlspecialchars($booking['service_category']); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Base Price:</strong></td>
                                <td>$<?php echo formatCurrency($booking['service_base_price']); ?></td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <table class="table table-borderless">
                            <tr>
                                <td><strong>Booking Date:</strong></td>
                                <td><?php echo date('F j, Y \a\t g:i A', strtotime($booking['booking_date'])); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Urgency:</strong></td>
                                <td><?php echo getUrgencyBadge($booking['urgency_level']); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Reference:</strong></td>
                                <td><code><?php echo htmlspecialchars($booking['booking_reference']); ?></code></td>
                            </tr>
                        </table>
                    </div>
                </div>
                
                <?php if ($booking['service_details']): ?>
                <div class="row">
                    <div class="col-12">
                        <h6><strong>Service Details:</strong></h6>
                        <div class="alert alert-light">
                            <?php echo nl2br(htmlspecialchars($booking['service_details'])); ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if ($booking['service_description']): ?>
                <div class="row">
                    <div class="col-12">
                        <h6><strong>Service Description:</strong></h6>
                        <p class="text-muted"><?php echo nl2br(htmlspecialchars($booking['service_description'])); ?></p>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Admin Notes -->
        <?php if ($booking['admin_notes']): ?>
        <div class="card info-card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-sticky-note"></i> Admin Notes</h5>
            </div>
            <div class="card-body">
                <div class="alert alert-warning">
                    <i class="fas fa-sticky-note"></i>
                    <?php echo nl2br(htmlspecialchars($booking['admin_notes'])); ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Payment Information -->
        <?php if (!empty($payments)): ?>
        <div class="card info-card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-credit-card"></i> Payment Information</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Amount</th>
                                <th>Method</th>
                                <th>Status</th>
                                <th>Reference</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($payments as $payment): ?>
                            <tr>
                                <td><?php echo date('M j, Y', strtotime($payment['created_at'])); ?></td>
                                <td>$<?php echo formatCurrency($payment['amount']); ?></td>
                                <td><?php echo htmlspecialchars($payment['payment_method']); ?></td>
                                <td>
                                    <span class="badge bg-<?php echo $payment['payment_status'] === 'completed' ? 'success' : 'warning'; ?>">
                                        <?php echo ucfirst($payment['payment_status']); ?>
                                    </span>
                                </td>
                                <td><code><?php echo htmlspecialchars($payment['payment_reference'] ?? 'N/A'); ?></code></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Right Column -->
    <div class="col-md-4">
        <!-- Pricing Information -->
        <div class="pricing-display">
            <h5 class="mb-3"><i class="fas fa-dollar-sign"></i> Pricing Details</h5>
            <div class="row text-center">
                <div class="col-12 mb-2">
                    <div class="h6">Quoted Price</div>
                    <div class="h4">$<?php echo formatCurrency($booking['quoted_price'] ?? 0); ?></div>
                </div>
                <div class="col-12 mb-2">
                    <div class="h6">Agent Fee</div>
                    <div class="h5">$<?php echo formatCurrency($booking['agent_fee'] ?? 0); ?></div>
                </div>
                <hr class="text-white">
                <div class="col-12">
                    <div class="h6">Total Amount</div>
                    <div class="h3"><strong>$<?php echo formatCurrency($booking['total_amount'] ?? 0); ?></strong></div>
                </div>
            </div>
        </div>

        <!-- Assigned Agent -->
        <div class="card info-card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-user-tie"></i> Assigned Agent</h5>
            </div>
            <div class="card-body">
                <?php if ($booking['assigned_admin_id']): ?>
                    <div class="d-flex align-items-center">
                        <div class="avatar-placeholder rounded-circle me-3" style="width: 50px; height: 50px; background: #007bff; color: white; display: flex; align-items: center; justify-content: center;">
                            <?php echo strtoupper(substr($booking['agent_first_name'], 0, 1) . substr($booking['agent_last_name'], 0, 1)); ?>
                        </div>
                        <div>
                            <div class="fw-bold"><?php echo htmlspecialchars($booking['agent_first_name'] . ' ' . $booking['agent_last_name']); ?></div>
                            <div class="text-muted small"><?php echo htmlspecialchars($booking['agent_email']); ?></div>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="text-center text-muted">
                        <i class="fas fa-user-slash fa-2x mb-2"></i>
                        <p>No agent assigned yet</p>
                        <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#assignModal">
                            Assign Agent
                        </button>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Timeline -->
        <div class="card info-card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-clock"></i> Timeline</h5>
            </div>
            <div class="card-body">
                <div class="timeline">
                    <div class="timeline-item">
                        <div class="fw-bold">Booking Created</div>
                        <div class="text-muted small"><?php echo date('M j, Y g:i A', strtotime($booking['created_at'])); ?></div>
                    </div>
                    
                    <?php if ($booking['confirmation_sent_at']): ?>
                    <div class="timeline-item">
                        <div class="fw-bold">Confirmation Sent</div>
                        <div class="text-muted small"><?php echo date('M j, Y g:i A', strtotime($booking['confirmation_sent_at'])); ?></div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($booking['approved_at']): ?>
                    <div class="timeline-item">
                        <div class="fw-bold">Approved</div>
                        <div class="text-muted small"><?php echo date('M j, Y g:i A', strtotime($booking['approved_at'])); ?></div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($booking['started_at']): ?>
                    <div class="timeline-item">
                        <div class="fw-bold">Work Started</div>
                        <div class="text-muted small"><?php echo date('M j, Y g:i A', strtotime($booking['started_at'])); ?></div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($booking['completed_at']): ?>
                    <div class="timeline-item">
                        <div class="fw-bold">Completed</div>
                        <div class="text-muted small"><?php echo date('M j, Y g:i A', strtotime($booking['completed_at'])); ?></div>
                    </div>
                    <?php endif; ?>
                    
                    <div class="timeline-item">
                        <div class="fw-bold">Last Updated</div>
                        <div class="text-muted small"><?php echo date('M j, Y g:i A', strtotime($booking['updated_at'])); ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Status Update Modal -->
<div class="modal fade" id="statusModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Update Booking Status</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="update_status">
                    <div class="mb-3">
                        <label for="status" class="form-label">Status</label>
                        <select name="status" id="status" class="form-select" required>
                            <option value="pending" <?php echo $booking['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="confirmed" <?php echo $booking['status'] === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                            <option value="waiting_approval" <?php echo $booking['status'] === 'waiting_approval' ? 'selected' : ''; ?>>Waiting Approval</option>
                            <option value="approved" <?php echo $booking['status'] === 'approved' ? 'selected' : ''; ?>>Approved</option>
                            <option value="payment_pending" <?php echo $booking['status'] === 'payment_pending' ? 'selected' : ''; ?>>Payment Pending</option>
                            <option value="paid" <?php echo $booking['status'] === 'paid' ? 'selected' : ''; ?>>Paid</option>
                            <option value="in_progress" <?php echo $booking['status'] === 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                            <option value="completed" <?php echo $booking['status'] === 'completed' ? 'selected' : ''; ?>>Completed</option>
                            <option value="cancelled" <?php echo $booking['status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="admin_notes" class="form-label">Admin Notes</label>
                        <textarea name="admin_notes" id="admin_notes" class="form-control" rows="3" placeholder="Add notes about this status change..."><?php echo htmlspecialchars($booking['admin_notes']); ?></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Status</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Assign Agent Modal -->
<div class="modal fade" id="assignModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Assign Agent</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="assign_agent">
                    <div class="mb-3">
                        <label for="agent_id" class="form-label">Select Agent</label>
                        <select name="agent_id" id="agent_id" class="form-select" required>
                            <option value="">Choose an agent...</option>
                            <?php foreach ($agents as $agent): ?>
                            <option value="<?php echo $agent['id']; ?>" <?php echo $booking['assigned_admin_id'] == $agent['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($agent['first_name'] . ' ' . $agent['last_name'] . ' (' . $agent['email'] . ')'); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Assign Agent</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Pricing Modal -->
<div class="modal fade" id="pricingModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Update Pricing</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="update_pricing">
                    <div class="mb-3">
                        <label for="quoted_price" class="form-label">Quoted Price</label>
                        <input type="number" name="quoted_price" id="quoted_price" class="form-control" step="0.01" value="<?php echo $booking['quoted_price']; ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="agent_fee" class="form-label">Agent Fee</label>
                        <input type="number" name="agent_fee" id="agent_fee" class="form-control" step="0.01" value="<?php echo $booking['agent_fee']; ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="total_amount" class="form-label">Total Amount</label>
                        <input type="number" name="total_amount" id="total_amount" class="form-control" step="0.01" value="<?php echo $booking['total_amount']; ?>" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Pricing</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Auto-calculate total amount in pricing modal
document.addEventListener('DOMContentLoaded', function() {
    const quotedPrice = document.getElementById('quoted_price');
    const agentFee = document.getElementById('agent_fee');
    const totalAmount = document.getElementById('total_amount');
    
    function calculateTotal() {
        const quoted = parseFloat(quotedPrice.value) || 0;
        const fee = parseFloat(agentFee.value) || 0;
        totalAmount.value = (quoted + fee).toFixed(2);
    }
    
    quotedPrice.addEventListener('input', calculateTotal);
    agentFee.addEventListener('input', calculateTotal);
});
</script>

<?php
$content = ob_get_clean();
echo renderAdminLayout('Booking Details', 'bookings', $content);
?>
