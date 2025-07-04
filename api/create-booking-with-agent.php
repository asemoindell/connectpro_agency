<?php
require_once 'config/database.php';
require_once 'includes/EmailNotification.php';

$database = new Database();
$db = $database->getConnection();
$emailNotification = new EmailNotification($db);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $service_id = $input['service_id'] ?? '';
    $client_name = $input['client_name'] ?? '';
    $client_email = $input['client_email'] ?? '';
    $client_phone = $input['client_phone'] ?? '';
    $service_details = $input['service_details'] ?? 'Service booked via agent selection';
    $urgency_level = $input['urgency_level'] ?? 'medium';
    $selected_agent_id = $input['selected_agent_id'] ?? '';
    
    if ($service_id && $client_name && $client_email && $service_details) {
        try {
            // Generate unique booking reference
            $booking_reference = 'CP' . date('Y') . strtoupper(substr(uniqid(), -6));
            
            // Get service details for pricing
            $service_stmt = $db->prepare("SELECT * FROM services WHERE id = ? AND status = 'active'");
            $service_stmt->execute([$service_id]);
            $service = $service_stmt->fetch();
            
            if ($service) {
                // Calculate approval deadline (3-4 days)
                $approval_deadline = date('Y-m-d H:i:s', strtotime('+4 days'));
                
                // For now, use a simple pricing model
                $quoted_price = 100; // Base price
                $total_amount = $quoted_price;
                
                // Insert booking
                $stmt = $db->prepare("INSERT INTO service_bookings 
                    (booking_reference, service_id, client_name, client_email, client_phone, 
                     service_details, urgency_level, status, quoted_price, total_amount, 
                     approval_deadline, assigned_admin_id, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', ?, ?, ?, ?, NOW())");
                
                $result = $stmt->execute([
                    $booking_reference,
                    $service_id,
                    $client_name,
                    $client_email,
                    $client_phone,
                    $service_details,
                    $urgency_level,
                    $quoted_price,
                    $total_amount,
                    $approval_deadline,
                    $selected_agent_id
                ]);
                
                if ($result) {
                    $booking_id = $db->lastInsertId();
                    
                    // Send notification email
                    try {
                        $emailNotification->sendBookingConfirmation(
                            $client_email,
                            $client_name,
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
                        'redirect_url' => "payment/crypto-payment.php?booking_id=$booking_id&method=btc"
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
        echo json_encode(['success' => false, 'error' => 'Missing required fields']);
    }
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
}
?>
