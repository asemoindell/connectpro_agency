<?php
session_start();
require_once 'config/database.php';
require_once 'includes/EmailNotification.php';

$database = new Database();
$db = $database->getConnection();
$emailNotification = new EmailNotification($db);

$error_message = '';
$success_message = '';
$booking = null;

// Get booking by reference
$booking_ref = $_GET['ref'] ?? '';
if ($booking_ref) {
    $stmt = $db->prepare("
        SELECT b.*, s.name as service_name, s.description as service_description 
        FROM service_bookings b 
        JOIN services s ON b.service_id = s.id 
        WHERE b.booking_reference = ? AND b.status IN ('approved', 'payment_pending')
    ");
    $stmt->execute([$booking_ref]);
    $booking = $stmt->fetch();
}

if (!$booking) {
    $error_message = 'Booking not found or not ready for payment.';
}

// Handle payment processing
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $booking) {
    $gateway = $_POST['gateway'] ?? '';
    $amount = $booking['total_amount'];
    
    if ($gateway) {
        // Generate payment reference
        $payment_ref = 'PAY' . date('Ymd') . strtoupper(substr(uniqid(), -6));
        
        // Insert payment record
        $payment_stmt = $db->prepare("
            INSERT INTO payments (booking_id, payment_reference, payment_method, amount, payment_status) 
            VALUES (?, ?, ?, ?, 'pending')
        ");
        
        if ($payment_stmt->execute([$booking['id'], $payment_ref, $gateway, $amount])) {
            $payment_id = $db->lastInsertId();
            
            // Update booking status
            $update_stmt = $db->prepare("UPDATE service_bookings SET status = 'payment_pending' WHERE id = ?");
            $update_stmt->execute([$booking['id']]);
            
            // Redirect to payment gateway simulation
            header("Location: process-payment.php?payment_ref={$payment_ref}&gateway={$gateway}");
            exit;
        } else {
            $error_message = 'Failed to initialize payment. Please try again.';
        }
    } else {
        $error_message = 'Please select a payment method.';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment - ConnectPro Agency</title>
    <link rel="stylesheet" href="css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .payment-container {
            max-width: 600px;
            margin: 2rem auto;
            padding: 2rem;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        
        .payment-header {
            text-align: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #eee;
        }
        
        .payment-header h1 {
            color: #667eea;
            margin-bottom: 0.5rem;
        }
        
        .booking-summary {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 10px;
            margin-bottom: 2rem;
        }
        
        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.5rem;
        }
        
        .summary-row.total {
            border-top: 1px solid #dee2e6;
            padding-top: 0.5rem;
            margin-top: 1rem;
            font-weight: bold;
            font-size: 1.2rem;
            color: #28a745;
        }
        
        .payment-methods {
            display: grid;
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .payment-method {
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .payment-method:hover {
            border-color: #667eea;
            background: #f8f9ff;
        }
        
        .payment-method.selected {
            border-color: #667eea;
            background: #f8f9ff;
        }
        
        .payment-method input[type="radio"] {
            display: none;
        }
        
        .payment-icon {
            font-size: 2rem;
            width: 60px;
            text-align: center;
        }
        
        .payment-info h4 {
            margin: 0 0 0.25rem 0;
            color: #333;
        }
        
        .payment-info p {
            margin: 0;
            color: #666;
            font-size: 0.9rem;
        }
        
        .stripe { color: #635bff; }
        .paypal { color: #0070ba; }
        .crypto { color: #f7931a; }
        .bank { color: #28a745; }
        
        .btn-pay {
            width: 100%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1rem 2rem;
            border: none;
            border-radius: 10px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.3s ease;
        }
        
        .btn-pay:hover {
            transform: translateY(-2px);
        }
        
        .btn-pay:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }
        
        .security-info {
            background: #e7f3ff;
            border: 1px solid #b3d9ff;
            border-radius: 8px;
            padding: 1rem;
            margin-top: 1rem;
            text-align: center;
        }
        
        .security-info i {
            color: #0066cc;
            margin-right: 0.5rem;
        }
        
        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
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
        
        .booking-details {
            background: white;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1rem;
        }
        
        .booking-details h4 {
            color: #667eea;
            margin-bottom: 0.5rem;
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="payment-container">
        <div class="payment-header">
            <h1><i class="fas fa-credit-card"></i> Payment</h1>
            <?php if ($booking): ?>
                <p>Complete payment for booking: <strong><?php echo htmlspecialchars($booking['booking_reference']); ?></strong></p>
            <?php endif; ?>
        </div>
        
        <?php if ($error_message): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($success_message): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($booking): ?>
            <!-- Booking Summary -->
            <div class="booking-summary">
                <h3><i class="fas fa-receipt"></i> Order Summary</h3>
                
                <div class="booking-details">
                    <h4><?php echo htmlspecialchars($booking['service_name']); ?></h4>
                    <p><?php echo htmlspecialchars($booking['service_description']); ?></p>
                    <p><strong>Reference:</strong> <?php echo htmlspecialchars($booking['booking_reference']); ?></p>
                </div>
                
                <div class="summary-row">
                    <span>Service Price:</span>
                    <span>$<?php echo number_format($booking['quoted_price'], 2); ?></span>
                </div>
                <div class="summary-row">
                    <span>Agent Fee:</span>
                    <span>$<?php echo number_format($booking['agent_fee'], 2); ?></span>
                </div>
                <div class="summary-row total">
                    <span>Total Amount:</span>
                    <span>$<?php echo number_format($booking['total_amount'], 2); ?></span>
                </div>
            </div>
            
            <!-- Payment Methods -->
            <form method="POST" id="paymentForm">
                <h3><i class="fas fa-credit-card"></i> Choose Payment Method</h3>
                
                <div class="payment-methods">
                    <div class="payment-method" onclick="selectPayment('stripe', this)">
                        <input type="radio" name="gateway" value="stripe">
                        <div class="payment-icon stripe">
                            <i class="fab fa-stripe"></i>
                        </div>
                        <div class="payment-info">
                            <h4>Credit/Debit Card</h4>
                            <p>Pay securely with Stripe - Visa, Mastercard, Amex</p>
                        </div>
                    </div>
                    
                    <div class="payment-method" onclick="selectPayment('paypal', this)">
                        <input type="radio" name="gateway" value="paypal">
                        <div class="payment-icon paypal">
                            <i class="fab fa-paypal"></i>
                        </div>
                        <div class="payment-info">
                            <h4>PayPal</h4>
                            <p>Pay with your PayPal account or card</p>
                        </div>
                    </div>
                    
                    <div class="payment-method" onclick="selectPayment('usdt', this)">
                        <input type="radio" name="gateway" value="usdt">
                        <div class="payment-icon crypto">
                            <i class="fab fa-bitcoin"></i>
                        </div>
                        <div class="payment-info">
                            <h4>USDT (Tether)</h4>
                            <p>Pay with USDT cryptocurrency</p>
                        </div>
                    </div>
                    
                    <div class="payment-method" onclick="selectPayment('bitcoin', this)">
                        <input type="radio" name="gateway" value="bitcoin">
                        <div class="payment-icon crypto">
                            <i class="fab fa-bitcoin"></i>
                        </div>
                        <div class="payment-info">
                            <h4>Bitcoin</h4>
                            <p>Pay with Bitcoin cryptocurrency</p>
                        </div>
                    </div>
                    
                    <div class="payment-method" onclick="selectPayment('bank_transfer', this)">
                        <input type="radio" name="gateway" value="bank_transfer">
                        <div class="payment-icon bank">
                            <i class="fas fa-university"></i>
                        </div>
                        <div class="payment-info">
                            <h4>Bank Transfer</h4>
                            <p>Direct bank transfer with instructions</p>
                        </div>
                    </div>
                </div>
                
                <button type="submit" class="btn-pay" id="payButton" disabled>
                    <i class="fas fa-lock"></i> Pay $<?php echo number_format($booking['total_amount'], 2); ?>
                </button>
            </form>
            
            <div class="security-info">
                <i class="fas fa-shield-alt"></i>
                <strong>Secure Payment:</strong> Your payment information is encrypted and secure. We never store your payment details.
            </div>
            
        <?php else: ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-triangle"></i>
                <strong>Booking Not Found</strong><br>
                The booking reference is invalid or the booking is not ready for payment.
                <br><br>
                <a href="book-service.php" class="btn" style="display: inline-block; margin-top: 1rem; background: #667eea; color: white; padding: 0.5rem 1rem; text-decoration: none; border-radius: 5px;">
                    <i class="fas fa-plus"></i> Make New Booking
                </a>
            </div>
        <?php endif; ?>
    </div>
    
    <?php include 'includes/footer.php'; ?>
    
    <script>
        function selectPayment(gateway, element) {
            // Remove selected class from all payment methods
            document.querySelectorAll('.payment-method').forEach(method => {
                method.classList.remove('selected');
            });
            
            // Add selected class to clicked method
            element.classList.add('selected');
            
            // Set radio button
            element.querySelector('input[type="radio"]').checked = true;
            
            // Enable pay button
            document.getElementById('payButton').disabled = false;
        }
        
        // Form validation
        document.getElementById('paymentForm').addEventListener('submit', function(e) {
            const selectedPayment = document.querySelector('input[name="gateway"]:checked');
            
            if (!selectedPayment) {
                e.preventDefault();
                alert('Please select a payment method.');
                return false;
            }
            
            // Show loading state
            const payButton = document.getElementById('payButton');
            payButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
            payButton.disabled = true;
        });
    </script>
</body>
</html>
