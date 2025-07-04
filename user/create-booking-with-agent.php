<?php
session_start();
require_once '../config/database.php';
require_once '../includes/EmailNotification.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit();
}

$database = new Database();
$db = $database->getConnection();
$emailNotification = new EmailNotification($db);

$user_id = $_SESSION['user_id'];

// Get user info
$user_stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$user_stmt->execute([$user_id]);
$user_info = $user_stmt->fetch();

if (!$user_info) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'User not found']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $service_id = $input['service_id'] ?? '';
    $selected_agent_id = $input['selected_agent_id'] ?? '';
    $service_details = $input['service_details'] ?? 'Service booked via agent selection';
    $urgency_level = $input['urgency_level'] ?? 'medium';
    
    if ($service_id) {
        try {
            // Generate unique booking reference
            $booking_reference = 'CP' . date('Y') . strtoupper(substr(uniqid(), -6));
            
            // Get service details for pricing
            $service_stmt = $db->prepare("SELECT * FROM services_enhanced WHERE id = ? AND status = 'active'");
            $service_stmt->execute([$service_id]);
            $service = $service_stmt->fetch();
            
            if ($service) {
                // Use selected agent or default assigned agent
                $final_agent_id = $selected_agent_id ?: $service['assigned_agent_id'];
                
                // Calculate approval deadline (3-4 days)
                $approval_deadline = date('Y-m-d H:i:s', strtotime('+4 days'));
                
                // Calculate pricing based on service settings
                $quoted_price = $service['base_price'];
                $agent_fee = $service['enable_agent_fee'] ? $service['agent_fee'] : 0;
                $processing_fee = $service['enable_processing_fee'] ? $service['processing_fee'] : 0;
                $subtotal = $quoted_price + $agent_fee + $processing_fee;
                
                // Calculate VAT and Tax
                $vat_amount = $service['enable_vat'] ? ($subtotal * $service['vat_rate'] / 100) : 0;
                $tax_amount = $service['enable_tax'] ? ($subtotal * $service['tax_rate'] / 100) : 0;
                $total_amount = $subtotal + $vat_amount + $tax_amount;
                
                // Insert booking with selected agent
                $stmt = $db->prepare("INSERT INTO service_bookings 
                    (booking_reference, service_id, user_id, client_name, client_email, client_phone, 
                     service_details, urgency_level, status, quoted_price, total_amount, 
                     approval_deadline, assigned_admin_id, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?, ?, ?, ?, NOW())");
                
                $result = $stmt->execute([
                    $booking_reference,
                    $service_id,
                    $user_id,
                    $user_info['first_name'] . ' ' . $user_info['last_name'],
                    $user_info['email'],
                    $user_info['phone'],
                    $service_details,
                    $urgency_level,
                    $quoted_price,
                    $total_amount,
                    $approval_deadline,
                    $final_agent_id
                ]);
                
                if ($result) {
                    $booking_id = $db->lastInsertId();
                    
                    // Send notification email
                    try {
                        $emailNotification->sendBookingConfirmation(
                            $user_info['email'],
                            $user_info['first_name'],
                            $booking_reference,
                            $service['title'],
                            $service_details
                        );
                    } catch (Exception $e) {
                        // Log email error but don't fail the booking
                        error_log("Email notification failed: " . $e->getMessage());
                    }
                    
                    echo json_encode([
                        'success' => true,
                        'booking_id' => $booking_id,
                        'booking_reference' => $booking_reference,
                        'total_amount' => $total_amount,
                        'redirect_url' => "../payment/crypto-payment.php?booking_id=$booking_id&method=btc"
                    ]);
                } else {
                    echo json_encode(['success' => false, 'error' => 'Failed to create booking']);
                }
            } else {
                echo json_encode(['success' => false, 'error' => 'Service not found']);
            }
        } catch (Exception $e) {
            error_log("Booking creation error: " . $e->getMessage());
            echo json_encode(['success' => false, 'error' => 'Database error occurred']);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'Missing service ID']);
    }
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
}
?>
