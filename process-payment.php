<?php
session_start();
require_once 'config/database.php';
require_once 'user/includes/user-helpers.php';
require_once 'includes/EmailNotification.php';

$database = new Database();
$db = $database->getConnection();
$emailNotification = new EmailNotification($db);

$payment_ref = $_GET['payment_ref'] ?? '';
$gateway = $_GET['gateway'] ?? '';
$error_message = '';
$success_message = '';

// Get payment details
$payment = null;
if ($payment_ref) {
    $stmt = $db->prepare("
        SELECT p.*, b.booking_reference, b.client_name, b.client_email, b.total_amount as booking_amount 
        FROM payments p 
        JOIN service_bookings b ON p.booking_id = b.id 
        WHERE p.payment_reference = ?
    ");
    $stmt->execute([$payment_ref]);
    $payment = $stmt->fetch();
}

if (!$payment) {
    $error_message = 'Payment not found.';
}

// Handle payment confirmation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $payment) {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'confirm_payment') {
        // Simulate payment processing
        $gateway_transaction_id = 'TXN' . date('Ymd') . strtoupper(substr(uniqid(), -8));
        
        // Update payment status
        $update_payment = $db->prepare("
            UPDATE payments 
            SET payment_status = 'completed', gateway_transaction_id = ?, paid_at = NOW() 
            WHERE payment_id = ?
        ");
        
        if ($update_payment->execute([$gateway_transaction_id, $payment['payment_id']])) {
            // Update booking status
            $update_booking = $db->prepare("UPDATE service_bookings SET status = 'paid' WHERE id = ?");
            $update_booking->execute([$payment['booking_id']]);
            
            // Create chat room
            $chat_token = 'CHAT' . strtoupper(substr(uniqid(), -10));
            $chat_stmt = $db->prepare("
                INSERT INTO chat_rooms (booking_id, room_token, client_id, admin_id) 
                VALUES (?, ?, ?, 1)
            ");
            $chat_stmt->execute([$payment['booking_id'], $chat_token, $_SESSION['user_id'] ?? null]);
            
            // Update booking status to in_progress
            $update_progress = $db->prepare("UPDATE service_bookings SET status = 'in_progress', started_at = NOW() WHERE id = ?");
            $update_progress->execute([$payment['booking_id']]);
            
            // Send chat invitation email
            $emailNotification->sendChatInvitation($payment['booking_id'], $chat_token);
            
            $success_message = 'Payment successful! You will receive a chat invitation shortly.';
            
            // Redirect to success page after 3 seconds
            header("refresh:3;url=payment-success.php?ref={$payment['booking_reference']}&chat_token={$chat_token}");
        } else {
            $error_message = 'Payment processing failed. Please try again.';
        }
    }
}

// Gateway-specific information
$gateway_info = [
    'stripe' => [
        'name' => 'Stripe',
        'icon' => 'fab fa-stripe',
        'color' => '#635bff',
        'description' => 'Secure credit card processing'
    ],
    'paypal' => [
        'name' => 'PayPal',
        'icon' => 'fab fa-paypal',
        'color' => '#0070ba',
        'description' => 'PayPal secure payment'
    ],
    'usdt' => [
        'name' => 'USDT (Tether)',
        'icon' => 'fab fa-bitcoin',
        'color' => '#26a17b',
        'description' => 'Cryptocurrency payment'
    ],
    'bitcoin' => [
        'name' => 'Bitcoin',
        'icon' => 'fab fa-bitcoin',
        'color' => '#f7931a',
        'description' => 'Bitcoin cryptocurrency'
    ],
    'bank_transfer' => [
        'name' => 'Bank Transfer',
        'icon' => 'fas fa-university',
        'color' => '#28a745',
        'description' => 'Direct bank transfer'
    ]
];

$current_gateway = $gateway_info[$gateway] ?? $gateway_info['stripe'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Process Payment - ConnectPro Agency</title>
    <link rel="stylesheet" href="css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .payment-process-container {
            max-width: 500px;
            margin: 2rem auto;
            padding: 2rem;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .gateway-header {
            margin-bottom: 2rem;
        }
        
        .gateway-icon {
            font-size: 4rem;
            margin-bottom: 1rem;
        }
        
        .payment-simulation {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 2rem;
            margin: 2rem 0;
        }
        
        .amount-display {
            font-size: 2rem;
            font-weight: bold;
            color: #28a745;
            margin: 1rem 0;
        }
        
        .btn-confirm {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            padding: 1rem 2rem;
            border: none;
            border-radius: 10px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.3s ease;
            width: 100%;
            margin-top: 1rem;
        }
        
        .btn-confirm:hover {
            transform: translateY(-2px);
        }
        
        .btn-cancel {
            background: #6c757d;
            color: white;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            margin-top: 1rem;
            text-decoration: none;
            display: inline-block;
        }
        
        .crypto-address {
            background: #e9ecef;
            padding: 1rem;
            border-radius: 8px;
            font-family: monospace;
            word-break: break-all;
            margin: 1rem 0;
            position: relative;
            border: 2px solid #007bff;
        }
        
        .btn-copy {
            background: #007bff;
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 5px;
            cursor: pointer;
            margin-top: 0.5rem;
            font-size: 0.9rem;
        }
        
        .btn-copy:hover {
            background: #0056b3;
        }
        
        .crypto-amount {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 8px;
            text-align: center;
            margin: 1rem 0;
        }
        
        .amount-display-crypto {
            font-size: 1.5rem;
            font-weight: bold;
            color: #f7931a;
            margin: 0.5rem 0;
        }
        
        .usd-equivalent {
            color: #6c757d;
            font-size: 0.9rem;
        }
        
        .crypto-instructions {
            background: #e7f3ff;
            padding: 1rem;
            border-radius: 8px;
            margin: 1rem 0;
        }
        
        .crypto-instructions h5 {
            margin-top: 0;
            color: #0066cc;
        }
        
        .crypto-instructions ol {
            margin: 0.5rem 0;
            padding-left: 1.2rem;
        }
        
        .crypto-instructions li {
            margin-bottom: 0.5rem;
        }
        
        .crypto-warnings {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 8px;
            padding: 1rem;
            margin: 1rem 0;
        }
        
        .warning-item {
            display: flex;
            align-items: flex-start;
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
        }
        
        .warning-item:last-child {
            margin-bottom: 0;
        }
        
        .warning-item i {
            color: #856404;
            margin-right: 0.5rem;
            margin-top: 0.1rem;
            flex-shrink: 0;
        }
        
        .bank-details {
            background: #e9ecef;
            padding: 1rem;
            border-radius: 8px;
            text-align: left;
            margin: 1rem 0;
        }
        
        .security-notice {
            background: #e7f3ff;
            border: 1px solid #b3d9ff;
            border-radius: 8px;
            padding: 1rem;
            margin-top: 1rem;
            font-size: 0.9rem;
        }
        
        .processing-animation {
            display: none;
        }
        
        .processing-animation.show {
            display: block;
            margin: 2rem 0;
        }
        
        .spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #667eea;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 0 auto 1rem auto;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
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
    </style>
</head>
<body>
    <div class="payment-process-container">
        <?php if ($error_message): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo htmlspecialchars($error_message); ?>
            </div>
            <a href="book-service.php" class="btn-cancel">
                <i class="fas fa-arrow-left"></i> Back to Booking
            </a>
        <?php elseif ($success_message): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?php echo htmlspecialchars($success_message); ?>
            </div>
            <div class="processing-animation show">
                <div class="spinner"></div>
                <p>Redirecting to chat...</p>
            </div>
        <?php elseif ($payment): ?>
            <div class="gateway-header">
                <div class="gateway-icon" style="color: <?php echo $current_gateway['color']; ?>">
                    <i class="<?php echo $current_gateway['icon']; ?>"></i>
                </div>
                <h2><?php echo $current_gateway['name']; ?></h2>
                <p><?php echo $current_gateway['description']; ?></p>
            </div>
            
            <div class="payment-simulation">
                <h3>Payment Details</h3>
                <p><strong>Booking:</strong> <?php echo htmlspecialchars($payment['booking_reference']); ?></p>
                <p><strong>Payment ID:</strong> <?php echo htmlspecialchars($payment['payment_reference']); ?></p>
                
                <div class="amount-display">
                    $<?php echo formatCurrency($payment['amount']); ?>
                </div>
                
                <?php if ($gateway === 'stripe'): ?>
                    <div class="card-form">
                        <h4>Card Information</h4>
                        <p><em>This is a demo - no real payment will be processed</em></p>
                        <input type="text" placeholder="4242 4242 4242 4242" style="width: 100%; padding: 0.75rem; margin: 0.5rem 0; border: 1px solid #ddd; border-radius: 5px;" readonly>
                        <div style="display: flex; gap: 1rem;">
                            <input type="text" placeholder="MM/YY" style="flex: 1; padding: 0.75rem; border: 1px solid #ddd; border-radius: 5px;" readonly>
                            <input type="text" placeholder="CVC" style="flex: 1; padding: 0.75rem; border: 1px solid #ddd; border-radius: 5px;" readonly>
                        </div>
                    </div>
                
                <?php elseif ($gateway === 'paypal'): ?>
                    <div class="paypal-info">
                        <h4>PayPal Login</h4>
                        <p><em>You would be redirected to PayPal to complete payment</em></p>
                        <i class="fab fa-paypal" style="font-size: 3rem; color: #0070ba; margin: 1rem 0;"></i>
                    </div>
                
                <?php elseif (in_array($gateway, ['usdt', 'bitcoin'])): ?>
                    <?php
                    // Get crypto wallet address from database
                    $wallet_stmt = $db->prepare("SELECT address FROM crypto_wallets WHERE currency = ? AND is_active = 1 LIMIT 1");
                    $wallet_stmt->execute([$gateway]);
                    $wallet = $wallet_stmt->fetch();
                    $wallet_address = $wallet ? $wallet['address'] : ($gateway === 'bitcoin' ? '1A1zP1eP5QGefi2DMPTfTL5SLmv7DivfNa' : 'TQNDzxPm9qNcfEuaGc7w2YW8nA5K8v5Z2m');
                    
                    // Calculate crypto amount (simplified - in real implementation, use live exchange rates)
                    $btc_rate = 45000; // USD per BTC (example rate)
                    $usdt_rate = 1;    // USD per USDT
                    $crypto_amount = $gateway === 'bitcoin' ? 
                        round($payment['amount'] / $btc_rate, 8) : 
                        round($payment['amount'] / $usdt_rate, 2);
                    ?>
                    <div class="crypto-payment">
                        <h4>Send <?php echo strtoupper($gateway); ?> to:</h4>
                        <div class="crypto-address" id="cryptoAddress">
                            <?php echo $wallet_address; ?>
                        </div>
                        <button onclick="copyAddress()" class="btn-copy">
                            <i class="fas fa-copy"></i> Copy Address
                        </button>
                        
                        <div class="crypto-amount">
                            <h5>Amount to Send:</h5>
                            <div class="amount-display-crypto">
                                <?php echo $crypto_amount . ' ' . strtoupper($gateway); ?>
                            </div>
                            <div class="usd-equivalent">
                                ≈ $<?php echo formatCurrency($payment['amount']); ?> USD
                            </div>
                        </div>
                        
                        <div class="crypto-instructions">
                            <h5>Payment Instructions:</h5>
                            <ol>
                                <li>Copy the wallet address above</li>
                                <li>Send exactly <strong><?php echo $crypto_amount . ' ' . strtoupper($gateway); ?></strong> to this address</li>
                                <li>Include the payment reference: <strong><?php echo $payment['payment_reference']; ?></strong> in the transaction memo (if supported)</li>
                                <li>Click "Confirm Payment" after sending</li>
                            </ol>
                        </div>
                        
                        <div class="crypto-warnings">
                            <div class="warning-item">
                                <i class="fas fa-exclamation-triangle"></i>
                                <strong>Important:</strong> Send only <?php echo strtoupper($gateway); ?> to this address. Other cryptocurrencies will be lost.
                            </div>
                            <?php if ($gateway === 'usdt'): ?>
                                <div class="warning-item">
                                    <i class="fas fa-info-circle"></i>
                                    <strong>Network:</strong> This is a TRC20 (Tron) USDT address. Make sure to select TRC20 network in your wallet.
                                </div>
                            <?php endif; ?>
                            <div class="warning-item">
                                <i class="fas fa-clock"></i>
                                <strong>Processing Time:</strong> <?php echo $gateway === 'bitcoin' ? '10-60 minutes' : '1-5 minutes'; ?> after network confirmation.
                            </div>
                        </div>
                        
                        <p><em>Demo Mode: No real cryptocurrency payment required for testing</em></p>
                    </div>
                
                <?php elseif ($gateway === 'bank_transfer'): ?>
                    <div class="bank-transfer">
                        <h4>Bank Transfer Details</h4>
                        <div class="bank-details">
                            <p><strong>Bank:</strong> ConnectPro Bank</p>
                            <p><strong>Account Name:</strong> ConnectPro Agency Ltd</p>
                            <p><strong>Account Number:</strong> 123456789</p>
                            <p><strong>Routing Number:</strong> 021000021</p>
                            <p><strong>Reference:</strong> <?php echo $payment['payment_reference']; ?></p>
                        </div>
                        <p><em>Include the reference number in your transfer</em></p>
                    </div>
                <?php endif; ?>
                
                <form method="POST" id="confirmForm">
                    <input type="hidden" name="action" value="confirm_payment">
                    <button type="submit" class="btn-confirm" onclick="processPayment()">
                        <i class="fas fa-check"></i> Confirm Payment
                    </button>
                </form>
                
                <a href="payment.php?ref=<?php echo $payment['booking_reference']; ?>" class="btn-cancel">
                    <i class="fas fa-arrow-left"></i> Back to Payment Options
                </a>
            </div>
            
            <div class="security-notice">
                <i class="fas fa-shield-alt"></i>
                <strong>Demo Mode:</strong> This is a demonstration. No real payment will be processed.
            </div>
            
            <div class="processing-animation" id="processingAnimation">
                <div class="spinner"></div>
                <p>Processing payment...</p>
            </div>
            
        <?php endif; ?>
    </div>
    
    <script>
        function processPayment() {
            // Show processing animation
            document.getElementById('processingAnimation').classList.add('show');
            
            // Hide the form
            document.getElementById('confirmForm').style.display = 'none';
            
            // Simulate processing delay
            setTimeout(function() {
                document.getElementById('confirmForm').submit();
            }, 2000);
        }
        
        function copyAddress() {
            const addressElement = document.getElementById('cryptoAddress');
            const address = addressElement.textContent.trim();
            
            if (navigator.clipboard) {
                navigator.clipboard.writeText(address).then(function() {
                    showCopySuccess();
                }).catch(function(err) {
                    console.error('Failed to copy: ', err);
                    fallbackCopy(address);
                });
            } else {
                fallbackCopy(address);
            }
        }
        
        function fallbackCopy(text) {
            const textArea = document.createElement('textarea');
            textArea.value = text;
            textArea.style.position = 'fixed';
            textArea.style.left = '-999999px';
            textArea.style.top = '-999999px';
            document.body.appendChild(textArea);
            textArea.focus();
            textArea.select();
            
            try {
                document.execCommand('copy');
                showCopySuccess();
            } catch (err) {
                console.error('Fallback copy failed: ', err);
                alert('Copy failed. Please manually copy the address.');
            }
            
            document.body.removeChild(textArea);
        }
        
        function showCopySuccess() {
            const copyBtn = document.querySelector('.btn-copy');
            const originalText = copyBtn.innerHTML;
            copyBtn.innerHTML = '<i class="fas fa-check"></i> Copied!';
            copyBtn.style.background = '#28a745';
            
            setTimeout(function() {
                copyBtn.innerHTML = originalText;
                copyBtn.style.background = '#007bff';
            }, 2000);
        }
    </script>
</body>
</html>
