<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

// Check if agent is logged in
if (!isset($_SESSION['agent_logged_in'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit();
}

$agent_id = $_SESSION['agent_id'];

// Check if required parameters are provided
if (!isset($_POST['booking_id']) || !isset($_POST['status'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Booking ID and status are required']);
    exit();
}

$booking_id = intval($_POST['booking_id']);
$new_status = trim($_POST['status']);

// Validate status
$valid_statuses = ['waiting_approval', 'approved', 'in_progress', 'completed', 'cancelled'];
if (!in_array($new_status, $valid_statuses)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid status']);
    exit();
}

try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    // First, verify that this booking belongs to this agent
    $stmt = $pdo->prepare("SELECT id, status, user_id FROM service_bookings WHERE id = ? AND assigned_admin_id = ?");
    $stmt->execute([$booking_id, $agent_id]);
    $booking = $stmt->fetch();
    
    if (!$booking) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Booking not found or you do not have permission to update it']);
        exit();
    }
    
    // Check if the status change is valid
    $current_status = $booking['status'];
    $valid_transitions = [
        'waiting_approval' => ['approved', 'cancelled'],
        'approved' => ['in_progress', 'cancelled'],
        'in_progress' => ['completed', 'cancelled'],
        'completed' => [], // Cannot change from completed
        'cancelled' => []  // Cannot change from cancelled
    ];
    
    if (!isset($valid_transitions[$current_status]) || 
        !in_array($new_status, $valid_transitions[$current_status])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid status transition']);
        exit();
    }
    
    // Update the booking status
    $stmt = $pdo->prepare("UPDATE service_bookings SET status = ?, updated_at = NOW() WHERE id = ?");
    $result = $stmt->execute([$new_status, $booking_id]);
    
    if ($result) {
        // Create a notification for the user if needed
        if ($booking['user_id']) {
            $notification_message = '';
            switch ($new_status) {
                case 'approved':
                    $notification_message = 'Your booking has been approved!';
                    break;
                case 'in_progress':
                    $notification_message = 'Your booking is now in progress.';
                    break;
                case 'completed':
                    $notification_message = 'Your booking has been completed!';
                    break;
                case 'cancelled':
                    $notification_message = 'Your booking has been cancelled.';
                    break;
            }
            
            if ($notification_message) {
                // Insert notification (you can create a notifications table later)
                try {
                    $stmt = $pdo->prepare("
                        INSERT INTO notifications (user_id, title, message, type, created_at) 
                        VALUES (?, ?, ?, ?, NOW())
                    ");
                    $stmt->execute([
                        $booking['user_id'],
                        'Booking Status Update',
                        $notification_message,
                        'booking_status'
                    ]);
                } catch (Exception $e) {
                    // Notification failed but that's okay, don't fail the main operation
                    error_log("Notification creation failed: " . $e->getMessage());
                }
            }
        }
        
        echo json_encode([
            'success' => true, 
            'message' => 'Booking status updated successfully',
            'old_status' => $current_status,
            'new_status' => $new_status
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to update booking status']);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
?>
