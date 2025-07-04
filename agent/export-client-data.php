<?php
session_start();
require_once '../config/database.php';

// Check if agent is logged in
if (!isset($_SESSION['agent_logged_in'])) {
    header('Location: login.php');
    exit();
}

$agent_id = $_SESSION['agent_id'];

// Check if user_id is provided
if (!isset($_POST['user_id']) || empty($_POST['user_id'])) {
    header('Location: dashboard.php');
    exit();
}

$user_id = intval($_POST['user_id']);

try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    // First verify that this user is actually assigned to this agent
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count 
        FROM service_bookings 
        WHERE user_id = ? AND assigned_admin_id = ?
    ");
    $stmt->execute([$user_id, $agent_id]);
    $result = $stmt->fetch();
    
    if ($result['count'] == 0) {
        header('Location: dashboard.php');
        exit();
    }
    
    // Get user details
    $stmt = $pdo->prepare("
        SELECT 
            u.id,
            u.first_name,
            u.last_name,
            u.email,
            u.phone,
            u.created_at
        FROM users u
        WHERE u.id = ?
    ");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    
    // Get client's bookings with this agent
    $stmt = $pdo->prepare("
        SELECT 
            sb.id,
            sb.service_id,
            sb.booking_date,
            sb.booking_time,
            sb.status,
            sb.client_name,
            sb.client_email,
            sb.client_phone,
            sb.client_message,
            sb.created_at,
            sb.total_amount,
            se.title as service_title,
            se.category as service_category,
            se.description as service_description
        FROM service_bookings sb
        LEFT JOIN services_enhanced se ON sb.service_id = se.id
        WHERE sb.user_id = ? AND sb.assigned_admin_id = ?
        ORDER BY sb.created_at DESC
    ");
    $stmt->execute([$user_id, $agent_id]);
    $bookings = $stmt->fetchAll();
    
    // Get payment history
    $stmt = $pdo->prepare("
        SELECT 
            p.id,
            p.booking_id,
            p.amount,
            p.payment_method,
            p.payment_status,
            p.created_at,
            sb.service_id,
            se.title as service_title
        FROM payments p
        LEFT JOIN service_bookings sb ON p.booking_id = sb.id
        LEFT JOIN services_enhanced se ON sb.service_id = se.id
        WHERE p.user_id = ? AND sb.assigned_admin_id = ?
        ORDER BY p.created_at DESC
    ");
    $stmt->execute([$user_id, $agent_id]);
    $payments = $stmt->fetchAll();
    
    // Set headers for CSV download
    $filename = 'client_data_' . $user['first_name'] . '_' . $user['last_name'] . '_' . date('Y-m-d') . '.csv';
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    // Create CSV output
    $output = fopen('php://output', 'w');
    
    // Client Information Section
    fputcsv($output, ['CLIENT INFORMATION']);
    fputcsv($output, ['Name', $user['first_name'] . ' ' . $user['last_name']]);
    fputcsv($output, ['Email', $user['email']]);
    fputcsv($output, ['Phone', $user['phone']]);
    fputcsv($output, ['Member Since', date('Y-m-d', strtotime($user['created_at']))]);
    fputcsv($output, []);
    
    // Bookings Section
    fputcsv($output, ['BOOKINGS']);
    fputcsv($output, ['Booking ID', 'Service', 'Category', 'Date', 'Time', 'Status', 'Amount', 'Client Name', 'Client Email', 'Client Phone', 'Message', 'Created Date']);
    
    foreach ($bookings as $booking) {
        fputcsv($output, [
            $booking['id'],
            $booking['service_title'],
            $booking['service_category'],
            $booking['booking_date'],
            $booking['booking_time'],
            $booking['status'],
            $booking['total_amount'],
            $booking['client_name'],
            $booking['client_email'],
            $booking['client_phone'],
            $booking['client_message'],
            date('Y-m-d H:i:s', strtotime($booking['created_at']))
        ]);
    }
    
    fputcsv($output, []);
    
    // Payments Section
    fputcsv($output, ['PAYMENTS']);
    fputcsv($output, ['Payment ID', 'Booking ID', 'Service', 'Amount', 'Payment Method', 'Status', 'Date']);
    
    foreach ($payments as $payment) {
        fputcsv($output, [
            $payment['id'],
            $payment['booking_id'],
            $payment['service_title'],
            $payment['amount'],
            $payment['payment_method'],
            $payment['payment_status'],
            date('Y-m-d H:i:s', strtotime($payment['created_at']))
        ]);
    }
    
    fputcsv($output, []);
    
    // Summary Section
    $total_bookings = count($bookings);
    $total_spent = array_sum(array_column($bookings, 'total_amount'));
    $completed_bookings = count(array_filter($bookings, function($b) { return $b['status'] === 'completed'; }));
    $pending_bookings = count(array_filter($bookings, function($b) { return $b['status'] === 'waiting_approval'; }));
    
    fputcsv($output, ['SUMMARY']);
    fputcsv($output, ['Total Bookings', $total_bookings]);
    fputcsv($output, ['Total Spent', '$' . number_format($total_spent, 2)]);
    fputcsv($output, ['Completed Bookings', $completed_bookings]);
    fputcsv($output, ['Pending Bookings', $pending_bookings]);
    fputcsv($output, ['Export Date', date('Y-m-d H:i:s')]);
    
    fclose($output);
    
} catch (Exception $e) {
    error_log("Export error: " . $e->getMessage());
    header('Location: dashboard.php');
    exit();
}
?>
