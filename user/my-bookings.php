<?php
session_start();
require_once '../config/database.php';
require_once 'includes/user-helpers.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    // Get user's bookings with payment status
    $stmt = $pdo->prepare("
        SELECT sb.*, 
               s.title as service_title,
               s.category as service_category,
               au.first_name as agent_first_name,
               au.last_name as agent_last_name,
               cp.status as payment_status,
               cp.payment_method,
               cp.id as payment_id,
               CASE 
                   WHEN cp.status = 'confirmed' THEN 1
                   ELSE 0
               END as can_chat
        FROM service_bookings sb
        LEFT JOIN services s ON sb.service_id = s.id
        LEFT JOIN admin_users au ON sb.assigned_admin_id = au.id
        LEFT JOIN crypto_payments cp ON sb.id = cp.booking_id
        WHERE sb.user_id = ?
        ORDER BY sb.created_at DESC
    ");
    $stmt->execute([$user_id]);
    $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    die('Database error: ' . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Bookings - ConnectPro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../css/mobile-responsive.css" rel="stylesheet">
    <style>
        .booking-card {
            border: 1px solid #dee2e6;
            border-radius: 10px;
            margin-bottom: 1.5rem;
            transition: all 0.3s;
            background: #fff;
        }
        .booking-card:hover {
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .status-badge {
            font-size: 0.9rem;
            padding: 0.5rem 1rem;
        }
        .payment-status {
            border-radius: 20px;
            padding: 0.3rem 0.8rem;
            font-size: 0.8rem;
            font-weight: 600;
        }
        .action-buttons .btn {
            margin: 0.2rem;
        }
        
        /* Mobile responsive improvements */
        @media (max-width: 768px) {
            .booking-card {
                margin-bottom: 1rem;
            }
            
            .booking-card .card-body {
                padding: 1rem;
            }
            
            .action-buttons {
                flex-direction: column;
                gap: 0.5rem;
            }
            
            .action-buttons .btn {
                width: 100%;
                margin: 0.2rem 0;
            }
            
            .status-badge {
                font-size: 0.8rem;
                padding: 0.4rem 0.8rem;
            }
            
            .booking-details {
                font-size: 0.9rem;
            }
        }
        
        @media (max-width: 480px) {
            .container {
                padding: 0 10px;
            }
            
            .booking-card .card-body {
                padding: 0.75rem;
            }
            
            .booking-details {
                font-size: 0.85rem;
            }
            
            .status-badge {
                font-size: 0.75rem;
                padding: 0.3rem 0.6rem;
            }
        }
    </style>
</head>
<body>
    <div class="container mt-4">
        <div class="row">
            <div class="col-md-12">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-center mb-4">
                    <h2 class="mb-3 mb-md-0"><i class="fas fa-calendar-alt"></i> My Bookings</h2>
                    <a href="../book-service.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> New Booking
                    </a>
                </div>
                
                <?php if (empty($bookings)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-calendar-times fa-4x text-muted mb-3"></i>
                        <h4 class="text-muted">No Bookings Yet</h4>
                        <p class="text-muted">You haven't made any bookings yet. Book a service to get started!</p>
                        <a href="../book-service.php" class="btn btn-primary">Book a Service</a>
                    </div>
                <?php else: ?>
                    <?php foreach ($bookings as $booking): ?>
                        <div class="booking-card">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-8">
                                        <div class="d-flex align-items-center mb-2">
                                            <h5 class="mb-0 me-3"><?php echo htmlspecialchars($booking['service_title']); ?></h5>
                                            <?php
                                            $status_class = 'secondary';
                                            switch($booking['status']) {
                                                case 'waiting_approval': $status_class = 'warning'; break;
                                                case 'approved': $status_class = 'info'; break;
                                                case 'in_progress': $status_class = 'primary'; break;
                                                case 'completed': $status_class = 'success'; break;
                                                case 'cancelled': $status_class = 'danger'; break;
                                            }
                                            ?>
                                            <span class="badge bg-<?php echo $status_class; ?> status-badge">
                                                <?php echo ucwords(str_replace('_', ' ', $booking['status'])); ?>
                                            </span>
                                        </div>
                                        
                                        <p class="text-muted mb-2">
                                            <strong>Reference:</strong> <?php echo htmlspecialchars($booking['booking_reference']); ?> |
                                            <strong>Date:</strong> <?php echo date('M j, Y', strtotime($booking['created_at'])); ?>
                                        </p>
                                        
                                        <?php if ($booking['agent_first_name']): ?>
                                            <p class="mb-2">
                                                <i class="fas fa-user-tie"></i> 
                                                <strong>Agent:</strong> <?php echo htmlspecialchars($booking['agent_first_name'] . ' ' . $booking['agent_last_name']); ?>
                                            </p>
                                        <?php endif; ?>
                                        
                                        <p class="mb-2">
                                            <strong>Amount:</strong> $<?php echo formatCurrency($booking['total_amount']); ?>
                                        </p>
                                        
                                        <!-- Payment Status -->
                                        <div class="mb-3">
                                            <strong>Payment Status:</strong>
                                            <?php if ($booking['payment_status']): ?>
                                                <?php
                                                $payment_class = 'secondary';
                                                $payment_text = 'Unknown';
                                                switch($booking['payment_status']) {
                                                    case 'pending': 
                                                        $payment_class = 'warning'; 
                                                        $payment_text = 'Payment Required';
                                                        break;
                                                    case 'verifying': 
                                                        $payment_class = 'info'; 
                                                        $payment_text = 'Verifying Payment';
                                                        break;
                                                    case 'cancelled': 
                                                        $payment_class = 'secondary'; 
                                                        $payment_text = 'Payment Cancelled';
                                                        break;
                                                    case 'confirmed': 
                                                        $payment_class = 'success'; 
                                                        $payment_text = 'Payment Confirmed';
                                                        break;
                                                    case 'failed': 
                                                        $payment_class = 'danger'; 
                                                        $payment_text = 'Payment Failed';
                                                        break;
                                                }
                                                ?>
                                                <span class="payment-status bg-<?php echo $payment_class; ?> text-white">
                                                    <?php echo $payment_text; ?>
                                                </span>
                                                
                                                <?php if ($booking['payment_method']): ?>
                                                    <small class="text-muted ms-2">
                                                        via <?php echo strtoupper($booking['payment_method']); ?>
                                                    </small>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span class="payment-status bg-warning text-white">Payment Required</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-4 text-end">
                                        <div class="action-buttons">
                                            <!-- Payment Action -->
                                            <?php if (!$booking['payment_status'] || $booking['payment_status'] === 'pending' || $booking['payment_status'] === 'failed' || $booking['payment_status'] === 'cancelled'): ?>
                                                <a href="../payment/crypto-payment.php?booking_id=<?php echo $booking['id']; ?>&method=btc" 
                                                   class="btn btn-warning">
                                                    <i class="fas fa-credit-card"></i> Pay Now
                                                </a>
                                            <?php elseif ($booking['payment_status'] === 'verifying'): ?>
                                                <button class="btn btn-info" disabled>
                                                    <i class="fas fa-clock"></i> Verifying...
                                                </button>
                                            <?php endif; ?>
                                            
                                            <!-- Chat Action -->
                                            <?php if ($booking['can_chat'] && $booking['agent_first_name']): ?>
                                                <a href="chat.php?booking_id=<?php echo $booking['id']; ?>" 
                                                   class="btn btn-success">
                                                    <i class="fas fa-comments"></i> Chat with Agent
                                                </a>
                                            <?php elseif ($booking['payment_status'] === 'confirmed' && !$booking['agent_first_name']): ?>
                                                <button class="btn btn-secondary" disabled>
                                                    <i class="fas fa-user-clock"></i> Awaiting Agent Assignment
                                                </button>
                                            <?php endif; ?>
                                            
                                            <!-- View Details -->
                                            <button class="btn btn-outline-primary" 
                                                    onclick="showBookingDetails(<?php echo htmlspecialchars(json_encode($booking)); ?>)">
                                                <i class="fas fa-eye"></i> Details
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Booking Details Modal -->
    <div class="modal fade" id="bookingDetailsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Booking Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="booking-details-content">
                    <!-- Content will be loaded here -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function showBookingDetails(booking) {
            const content = `
                <div class="row">
                    <div class="col-md-6">
                        <h6>Service Information</h6>
                        <p><strong>Service:</strong> ${booking.service_title}</p>
                        <p><strong>Category:</strong> ${booking.service_category || 'N/A'}</p>
                        <p><strong>Reference:</strong> ${booking.booking_reference}</p>
                        <p><strong>Status:</strong> ${booking.status.replace('_', ' ')}</p>
                    </div>
                    <div class="col-md-6">
                        <h6>Contact Information</h6>
                        <p><strong>Client Name:</strong> ${booking.client_name}</p>
                        <p><strong>Email:</strong> ${booking.client_email}</p>
                        <p><strong>Phone:</strong> ${booking.client_phone || 'N/A'}</p>
                        <p><strong>Urgency:</strong> ${booking.urgency_level}</p>
                    </div>
                </div>
                
                <div class="row mt-3">
                    <div class="col-md-12">
                        <h6>Service Details</h6>
                        <p>${booking.service_details || 'No additional details provided.'}</p>
                    </div>
                </div>
                
                <div class="row mt-3">
                    <div class="col-md-6">
                        <h6>Pricing</h6>
                        <p><strong>Quoted Price:</strong> $${parseFloat(booking.quoted_price).toFixed(2)}</p>
                        <p><strong>Agent Fee:</strong> $${parseFloat(booking.agent_fee).toFixed(2)}</p>
                        <p><strong>Total Amount:</strong> $${parseFloat(booking.total_amount).toFixed(2)}</p>
                    </div>
                    <div class="col-md-6">
                        <h6>Timeline</h6>
                        <p><strong>Booking Date:</strong> ${new Date(booking.created_at).toLocaleDateString()}</p>
                        <p><strong>Approval Deadline:</strong> ${booking.approval_deadline ? new Date(booking.approval_deadline).toLocaleDateString() : 'N/A'}</p>
                        ${booking.agent_first_name ? `<p><strong>Agent:</strong> ${booking.agent_first_name} ${booking.agent_last_name}</p>` : ''}
                    </div>
                </div>
            `;
            
            document.getElementById('booking-details-content').innerHTML = content;
            new bootstrap.Modal(document.getElementById('bookingDetailsModal')).show();
        }
    </script>
</body>
</html>
