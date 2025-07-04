<?php
session_start();
require_once '../config/database.php';

// Enable error logging
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', '../uploads/payment_update_errors.log');

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id']) && !isset($_SESSION['agent_logged_in'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

try {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['payment_id']) || !isset($input['status'])) {
        throw new Exception('Missing payment_id or status');
    }
    
    $payment_id = intval($input['payment_id']);
    $status = $input['status'];
    
    // Validate status
    $allowed_statuses = ['verifying', 'cancelled'];
    if (!in_array($status, $allowed_statuses)) {
        error_log("Invalid status attempted: $status");
        throw new Exception('Invalid status');
    }
    
    $database = new Database();
    $pdo = $database->getConnection();
    
    // Enable SQL error mode
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get payment details
    $stmt = $pdo->prepare("SELECT * FROM crypto_payments WHERE id = ?");
    $stmt->execute([$payment_id]);
    $payment = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$payment) {
        throw new Exception('Payment not found');
    }
    
    // Only allow status change if payment is currently pending
    if ($payment['status'] !== 'pending') {
        throw new Exception('Payment status can only be changed when pending');
    }
    
    // Update payment status
    error_log("Updating crypto_payments: payment_id=$payment_id, status=$status");
    $stmt = $pdo->prepare("UPDATE crypto_payments SET status = ?, updated_at = NOW() WHERE id = ?");
    $result = $stmt->execute([$status, $payment_id]);
    
    if (!$result) {
        error_log("Failed to update crypto_payments table");
        throw new Exception('Failed to update payment status');
    }
    
    // Update booking status based on payment status
    $booking_status = 'pending';
    $payment_status = 'pending';
    
    if ($status === 'verifying') {
        $booking_status = 'payment_pending';
        $payment_status = 'processing';
    } elseif ($status === 'cancelled') {
        $booking_status = 'cancelled';
        $payment_status = 'cancelled';
    }
    
    // Update service booking
    error_log("Updating service_bookings: booking_id={$payment['booking_id']}, status=$booking_status, payment_status=$payment_status");
    $stmt = $pdo->prepare("UPDATE service_bookings SET status = ?, payment_status = ?, updated_at = NOW() WHERE id = ?");
    $result2 = $stmt->execute([$booking_status, $payment_status, $payment['booking_id']]);
    
    if (!$result2) {
        error_log("Failed to update service_bookings table");
        throw new Exception('Failed to update booking status');
    }
    
    // Send notification to admins if payment is submitted for verification
    if ($status === 'verifying') {
        $stmt = $pdo->prepare("
            INSERT INTO notifications (user_type, user_id, title, message, type, created_at)
            SELECT 'admin', id, 'Payment Submitted for Verification', 
                   CONCAT('Payment for booking #', ?, ' has been submitted for verification'), 
                   'payment', NOW()
            FROM admin_users WHERE role = 'admin'
        ");
        $stmt->execute([$payment['booking_id']]);
    }
    
    echo json_encode([
        'success' => true,
        'message' => $status === 'verifying' ? 'Payment submitted for verification' : 'Payment cancelled',
        'status' => $status
    ]);
    
} catch (PDOException $e) {
    error_log("Database error in update-payment-status.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    error_log("General error in update-payment-status.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
