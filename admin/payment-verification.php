<?php
session_start();
require_once '../config/database.php';
require_once '../config/crypto_config.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

$crypto_config = include '../config/crypto_config.php';

try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    // Get pending payments
    $stmt = $pdo->prepare("
        SELECT cp.*, sb.booking_reference, sb.client_name, sb.client_email, 
               sb.total_amount, s.title as service_title
        FROM crypto_payments cp
        LEFT JOIN service_bookings sb ON cp.booking_id = sb.id
        LEFT JOIN services s ON sb.service_id = s.id
        WHERE cp.status IN ('verifying', 'cancelled')
        ORDER BY cp.created_at DESC
    ");
    $stmt->execute();
    $pending_payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get all payments for history
    $stmt = $pdo->prepare("
        SELECT cp.*, sb.booking_reference, sb.client_name, sb.client_email, 
               sb.total_amount, s.title as service_title, au.first_name as verified_by_name
        FROM crypto_payments cp
        LEFT JOIN service_bookings sb ON cp.booking_id = sb.id
        LEFT JOIN services s ON sb.service_id = s.id
        LEFT JOIN admin_users au ON cp.verified_by = au.id
        ORDER BY cp.created_at DESC
        LIMIT 50
    ");
    $stmt->execute();
    $all_payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    die('Database error: ' . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Verification - ConnectPro Agency</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .payment-card {
            border: 1px solid #dee2e6;
            border-radius: 10px;
            margin-bottom: 1rem;
            transition: all 0.3s;
        }
        .payment-card:hover {
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .payment-proof {
            max-width: 300px;
            border-radius: 10px;
            cursor: pointer;
        }
        .status-badge {
            font-size: 0.9rem;
            padding: 0.5rem 1rem;
        }
        .crypto-icon {
            font-size: 2rem;
            margin-right: 0.5rem;
        }
        .proof-modal .modal-dialog {
            max-width: 90%;
        }
        .proof-modal img {
            max-width: 100%;
            height: auto;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 bg-dark text-white p-3">
                <h4><i class="fas fa-shield-alt"></i> Admin Panel</h4>
                <ul class="nav nav-pills flex-column">
                    <li class="nav-item">
                        <a class="nav-link active" href="payment-verification.php">
                            <i class="fas fa-credit-card"></i> Payment Verification
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">
                            <i class="fas fa-tachometer-alt"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="bookings.php">
                            <i class="fas fa-calendar-alt"></i> Bookings
                        </a>
                    </li>
                </ul>
            </div>
            
            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><i class="fas fa-coins"></i> Cryptocurrency Payment Verification</h2>
                    <div>
                        <span class="badge bg-warning fs-6"><?php echo count($pending_payments); ?> Pending</span>
                    </div>
                </div>
                
                <!-- Pending Payments -->
                <?php if (!empty($pending_payments)): ?>
                    <div class="mb-5">
                        <h3><i class="fas fa-hourglass-half"></i> Pending Verification</h3>
                        <?php foreach ($pending_payments as $payment): ?>
                            <div class="payment-card">
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-8">
                                            <div class="d-flex align-items-center mb-3">
                                                <i class="<?php echo $payment['payment_method'] === 'btc' ? 'fab fa-bitcoin text-warning' : 'fas fa-dollar-sign text-success'; ?> crypto-icon"></i>
                                                <div>
                                                    <h5 class="mb-1"><?php echo htmlspecialchars($payment['service_title']); ?></h5>
                                                    <p class="text-muted mb-0">
                                                        Booking: <?php echo htmlspecialchars($payment['booking_reference']); ?> | 
                                                        Client: <?php echo htmlspecialchars($payment['client_name']); ?>
                                                    </p>
                                                </div>
                                            </div>
                                            
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <p><strong>Amount:</strong> $<?php echo number_format($payment['amount'], 2); ?></p>
                                                    <p><strong>Method:</strong> <?php echo strtoupper($payment['payment_method']); ?></p>
                                                    <p><strong>Address:</strong> <br>
                                                        <code><?php echo htmlspecialchars($payment['payment_address']); ?></code>
                                                    </p>
                                                </div>
                                                <div class="col-md-6">
                                                    <p><strong>Status:</strong> 
                                                        <span class="badge bg-info status-badge"><?php echo $crypto_config['payment_status'][$payment['status']]; ?></span>
                                                    </p>
                                                    <p><strong>Submitted:</strong> <?php echo date('M j, Y g:i A', strtotime($payment['created_at'])); ?></p>
                                                    <p><strong>Client Email:</strong> <?php echo htmlspecialchars($payment['client_email']); ?></p>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-4 text-center">
                                            <h6>Payment Actions</h6>
                                            <div class="mt-3">
                                                <button class="btn btn-success me-2" onclick="verifyPayment(<?php echo $payment['id']; ?>, 'confirmed')">
                                                    <i class="fas fa-check"></i> Approve
                                                </button>
                                                <button class="btn btn-danger" onclick="verifyPayment(<?php echo $payment['id']; ?>, 'failed')">
                                                    <i class="fas fa-times"></i> Reject
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> No pending payments to verify.
                    </div>
                <?php endif; ?>
                
                <!-- Payment History -->
                <div class="mt-5">
                    <h3><i class="fas fa-history"></i> Payment History</h3>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Booking</th>
                                    <th>Client</th>
                                    <th>Amount</th>
                                    <th>Method</th>
                                    <th>Status</th>
                                    <th>Verified By</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($all_payments as $payment): ?>
                                    <tr>
                                        <td><?php echo date('M j, Y', strtotime($payment['created_at'])); ?></td>
                                        <td><?php echo htmlspecialchars($payment['booking_reference']); ?></td>
                                        <td><?php echo htmlspecialchars($payment['client_name']); ?></td>
                                        <td>$<?php echo number_format($payment['amount'], 2); ?></td>
                                        <td>
                                            <i class="<?php echo $payment['payment_method'] === 'btc' ? 'fab fa-bitcoin text-warning' : 'fas fa-dollar-sign text-success'; ?>"></i>
                                            <?php echo strtoupper($payment['payment_method']); ?>
                                        </td>
                                        <td>
                                            <?php
                                            $status_class = 'secondary';
                                            switch($payment['status']) {
                                                case 'confirmed': $status_class = 'success'; break;
                                                case 'failed': $status_class = 'danger'; break;
                                                case 'cancelled': $status_class = 'secondary'; break;
                                                case 'verifying': $status_class = 'info'; break;
                                                case 'verifying': $status_class = 'warning'; break;
                                            }
                                            ?>
                                            <span class="badge bg-<?php echo $status_class; ?>">
                                                <?php echo $crypto_config['payment_status'][$payment['status']]; ?>
                                            </span>
                                        </td>
                                        <td><?php echo htmlspecialchars($payment['verified_by_name'] ?? 'N/A'); ?></td>
                                        <td>
                                            <span class="text-muted">No proof required</span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Payment Proof Modal -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function verifyPayment(paymentId, status) {
            if (confirm(`Are you sure you want to ${status === 'confirmed' ? 'approve' : 'reject'} this payment?`)) {
                updatePaymentStatus(paymentId, status, '');
            }
        }
        
        function updatePaymentStatus(paymentId, status, notes) {
            fetch('verify-payment.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    payment_id: paymentId,
                    status: status,
                    notes: notes
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Error: ' + data.error);
                }
            })
            .catch(error => {
                alert('Error updating payment status: ' + error);
            });
        }
    </script>
</body>
</html>
