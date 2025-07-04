<?php
session_start();
require_once '../config/database.php';
require_once '../config/crypto_config.php';
require_once '../user/includes/user-helpers.php';

$crypto_config = include '../config/crypto_config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id']) && !isset($_SESSION['agent_logged_in'])) {
    header('Location: ../login.php');
    exit();
}

$booking_id = $_GET['booking_id'] ?? null;
$payment_method = $_GET['method'] ?? 'btc';

if (!$booking_id) {
    die('Booking ID is required');
}

try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    // Get booking details
    $stmt = $pdo->prepare("
        SELECT sb.*, s.title as service_title, s.price_range
        FROM service_bookings sb
        LEFT JOIN services s ON sb.service_id = s.id
        WHERE sb.id = ?
    ");
    $stmt->execute([$booking_id]);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$booking) {
        die('Booking not found');
    }
    
    // Get or create payment record
    $stmt = $pdo->prepare("SELECT * FROM crypto_payments WHERE booking_id = ?");
    $stmt->execute([$booking_id]);
    $payment = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$payment) {
        // Create new payment record
        $payment_address = $crypto_config['payment_methods'][$payment_method]['address'];
        $amount = $booking['total_amount'];
        
        $stmt = $pdo->prepare("
            INSERT INTO crypto_payments (booking_id, payment_method, amount, payment_address, status, created_at)
            VALUES (?, ?, ?, ?, 'pending', NOW())
        ");
        $stmt->execute([$booking_id, $payment_method, $amount, $payment_address]);
        
        $payment_id = $pdo->lastInsertId();
        
        // Get the newly created payment
        $stmt = $pdo->prepare("SELECT * FROM crypto_payments WHERE id = ?");
        $stmt->execute([$payment_id]);
        $payment = $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    $crypto_info = $crypto_config['payment_methods'][$payment_method];
    
} catch (PDOException $e) {
    die('Database error: ' . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crypto Payment - <?php echo htmlspecialchars($booking['service_title']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../css/mobile-responsive.css" rel="stylesheet">
    <style>
        .payment-container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        .crypto-card {
            border: 2px solid #e9ecef;
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .qr-container {
            text-align: center;
            background: white;
            padding: 2rem;
            border-radius: 10px;
            margin: 1rem 0;
        }
        .address-container {
            background: rgba(255,255,255,0.1);
            padding: 1rem;
            border-radius: 8px;
            margin: 1rem 0;
            word-break: break-all;
        }
        .payment-actions {
            display: flex;
            gap: 1rem;
            justify-content: center;
            margin-top: 2rem;
        }
        .payment-method-selector {
            display: flex;
            gap: 1rem;
            justify-content: center;
            margin-bottom: 2rem;
        }
        .payment-method-btn {
            padding: 0.75rem 1.5rem;
            border: 2px solid #667eea;
            border-radius: 8px;
            background: white;
            color: #667eea;
            text-decoration: none;
            transition: all 0.3s;
        }
        .payment-method-btn.active {
            background: #667eea;
            color: white;
        }
        .payment-method-btn:hover {
            background: #667eea;
            color: white;
        }
        
        /* Mobile responsive improvements */
        @media (max-width: 768px) {
            .payment-container {
                margin: 1rem auto;
                padding: 0 0.5rem;
            }
            
            .crypto-card {
                padding: 1.5rem;
                margin-bottom: 1.5rem;
            }
            
            .qr-container {
                padding: 1.5rem;
            }
            
            .address-container {
                padding: 0.75rem;
                font-size: 0.85rem;
            }
            
            .payment-actions {
                flex-direction: column;
                gap: 0.5rem;
            }
            
            .payment-method-selector {
                flex-direction: column;
                gap: 0.5rem;
            }
            
            .payment-method-btn {
                width: 100%;
                text-align: center;
            }
        }
        
        @media (max-width: 480px) {
            .payment-container {
                margin: 0.5rem auto;
            }
            
            .crypto-card {
                padding: 1rem;
                margin-bottom: 1rem;
            }
            
            .qr-container {
                padding: 1rem;
            }
            
            .address-container {
                padding: 0.5rem;
                font-size: 0.8rem;
            }
            
            .payment-method-btn {
                padding: 0.5rem 1rem;
                font-size: 0.9rem;
            }
        }
        .copy-btn {
            background: rgba(255,255,255,0.2);
            border: 1px solid rgba(255,255,255,0.3);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s;
        }
        .copy-btn:hover {
            background: rgba(255,255,255,0.3);
        }
        .payment-info {
            background: white;
            color: #333;
            padding: 2rem;
            border-radius: 10px;
            margin-bottom: 2rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="payment-container">
            <!-- Payment Header -->
            <div class="text-center mb-4">
                <h2><i class="fab fa-bitcoin"></i> Cryptocurrency Payment</h2>
                <p class="text-muted">Secure payment with Bitcoin or USDT</p>
            </div>
            
            <!-- Payment Method Selection -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="card payment-method-card <?php echo $payment_method === 'btc' ? 'border-warning' : ''; ?>" 
                         onclick="selectPaymentMethod('btc')">
                        <div class="card-body text-center">
                            <i class="fab fa-bitcoin fa-3x text-warning mb-2"></i>
                            <h5>Bitcoin (BTC)</h5>
                            <p class="text-muted">Pay with Bitcoin</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card payment-method-card <?php echo $payment_method === 'usdt' ? 'border-success' : ''; ?>" 
                         onclick="selectPaymentMethod('usdt')">
                        <div class="card-body text-center">
                            <i class="fas fa-dollar-sign fa-3x text-success mb-2"></i>
                            <h5>Tether (USDT)</h5>
                            <p class="text-muted">Pay with USDT TRC20</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Payment Details -->
            <div class="payment-info">
                <h4><i class="fas fa-receipt"></i> Payment Details</h4>
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Service:</strong> <?php echo htmlspecialchars($booking['service_title']); ?></p>
                        <p><strong>Booking Reference:</strong> <?php echo htmlspecialchars($booking['booking_reference']); ?></p>
                        <p><strong>Status:</strong> <span class="badge bg-warning">Pending Payment</span></p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Amount:</strong> $<?php echo formatCurrency($booking['total_amount']); ?></p>
                        <p><strong>Payment Method:</strong> <?php echo strtoupper($payment_method); ?></p>
                        <p><strong>Network:</strong> <?php echo $crypto_info['network']; ?></p>
                    </div>
                </div>
            </div>
            
            <!-- Crypto Payment Card -->
            <div class="crypto-card">
                <div class="row">
                    <div class="col-md-6">
                        <h4><i class="<?php echo $payment_method === 'btc' ? 'fab fa-bitcoin' : 'fas fa-dollar-sign'; ?>"></i> 
                            Send <?php echo strtoupper($payment_method); ?></h4>
                        
                        <!-- QR Code -->
                        <div class="qr-container">
                            <img src="https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=<?php echo urlencode($payment['payment_address']); ?>" 
                                 alt="<?php echo strtoupper($payment_method); ?> QR Code" 
                                 class="img-fluid">
                            <p class="text-dark mt-2"><small>Scan QR code to pay</small></p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <h5>Payment Instructions</h5>
                        <ol>
                            <li>Send exactly <strong>$<?php echo formatCurrency($booking['total_amount']); ?></strong> worth of <?php echo strtoupper($payment_method); ?></li>
                            <li>Use the address below or scan the QR code</li>
                            <li>Payment will be automatically verified</li>
                            <li>Wait for confirmation</li>
                        </ol>
                        
                        <!-- Address -->
                        <div class="address-container">
                            <h6>Payment Address:</h6>
                            <div class="d-flex align-items-center">
                                <input type="text" class="form-control me-2" 
                                       value="<?php echo htmlspecialchars($payment['payment_address']); ?>" 
                                       readonly id="payment-address">
                                <button class="copy-btn" onclick="copyAddress()">
                                    <i class="fas fa-copy"></i> Copy
                                </button>
                            </div>
                        </div>
                        
                        <!-- Network Info -->
                        <div class="mt-3">
                            <p><i class="fas fa-info-circle"></i> <strong>Network:</strong> <?php echo $crypto_info['network']; ?></p>
                            <p><i class="fas fa-exclamation-triangle"></i> <strong>Important:</strong> Only send <?php echo strtoupper($payment_method); ?> to this address</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Payment Actions -->
            <?php if ($payment['status'] === 'pending'): ?>
            <div class="card mt-4">
                <div class="card-header">
                    <h5><i class="fas fa-credit-card"></i> Payment Actions</h5>
                </div>
                <div class="card-body">
                    <p class="text-muted mb-3">After making your payment, please confirm or cancel the transaction:</p>
                    <div class="row">
                        <div class="col-md-6">
                            <button class="btn btn-success btn-lg w-100" onclick="submitPayment()">
                                <i class="fas fa-check-circle"></i> Submit Payment
                            </button>
                            <small class="text-muted">Click after you've completed the payment</small>
                        </div>
                        <div class="col-md-6">
                            <button class="btn btn-danger btn-lg w-100" onclick="cancelPayment()">
                                <i class="fas fa-times-circle"></i> Cancel Payment
                            </button>
                            <small class="text-muted">Cancel this payment transaction</small>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Payment Status -->
            <div class="card mt-4">
                <div class="card-body text-center">
                    <h5>Payment Status</h5>
                    <div id="payment-status">
                        <?php 
                        $status_class = 'warning';
                        switch($payment['status']) {
                            case 'confirmed': $status_class = 'success'; break;
                            case 'failed': $status_class = 'danger'; break;
                            case 'cancelled': $status_class = 'secondary'; break;
                            case 'verifying': $status_class = 'info'; break;
                        }
                        ?>
                        <span class="badge bg-<?php echo $status_class; ?> fs-6">
                            <?php echo $crypto_config['payment_status'][$payment['status']]; ?>
                        </span>
                    </div>
                    <p class="mt-2 text-muted">
                        <?php if ($payment['status'] === 'confirmed'): ?>
                            Payment confirmed! You can now chat with your agent.
                        <?php elseif ($payment['status'] === 'verifying'): ?>
                            Your payment is being verified. You'll be notified once confirmed.
                        <?php elseif ($payment['status'] === 'cancelled'): ?>
                            Payment has been cancelled. You can create a new booking if needed.
                        <?php else: ?>
                            Complete your payment and click "Submit Payment" to proceed.
                        <?php endif; ?>
                    </p>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function selectPaymentMethod(method) {
            window.location.href = `?booking_id=<?php echo $booking_id; ?>&method=${method}`;
        }
        
        function copyAddress() {
            const addressInput = document.getElementById('payment-address');
            addressInput.select();
            document.execCommand('copy');
            
            // Show feedback
            const copyBtn = document.querySelector('.copy-btn');
            const originalText = copyBtn.innerHTML;
            copyBtn.innerHTML = '<i class="fas fa-check"></i> Copied!';
            setTimeout(() => {
                copyBtn.innerHTML = originalText;
            }, 2000);
        }
        
        function submitPayment() {
            if (confirm('Please confirm that you have completed the payment. Once confirmed, your payment will be marked for verification.')) {
                updatePaymentStatus('verifying');
            }
        }
        
        function cancelPayment() {
            if (confirm('Are you sure you want to cancel this payment? This action cannot be undone.')) {
                updatePaymentStatus('cancelled');
            }
        }
        
        function updatePaymentStatus(status) {
            const loadingDiv = document.createElement('div');
            loadingDiv.innerHTML = '<div class="text-center"><i class="fas fa-spinner fa-spin"></i> Processing...</div>';
            document.getElementById('payment-status').appendChild(loadingDiv);
            
            fetch('update-payment-status.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    payment_id: <?php echo $payment['id']; ?>,
                    status: status
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Error: ' + data.error);
                    loadingDiv.remove();
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while updating payment status');
                loadingDiv.remove();
            });
        }
        
        // Auto-refresh payment status
        setInterval(() => {
            fetch(`check-payment-status.php?payment_id=<?php echo $payment['id']; ?>`)
            .then(response => response.json())
            .then(data => {
                if (data.status !== '<?php echo $payment['status']; ?>') {
                    location.reload();
                }
            });
        }, 30000); // Check every 30 seconds
    </script>
</body>
</html>
