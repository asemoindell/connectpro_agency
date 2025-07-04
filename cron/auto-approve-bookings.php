<?php
/**
 * Auto-Approval Cron Job
 * This script should be run daily to auto-approve bookings that have been waiting for 3-4 days
 * 
 * To set up as a cron job, add this line to your crontab:
 * 0 9 * * * /usr/bin/php /path/to/your/project/cron/auto-approve-bookings.php
 * 
 * This will run daily at 9 AM
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/EmailNotification.php';

// Set timezone
date_default_timezone_set('America/New_York'); // Adjust as needed

try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    echo "Starting auto-approval process at " . date('Y-m-d H:i:s') . "\n";
    
    // Find bookings that are waiting for approval and are 4 days old or more
    $stmt = $pdo->prepare("
        SELECT * FROM bookings 
        WHERE status = 'waiting_approval' 
        AND booking_date <= DATE_SUB(NOW(), INTERVAL 4 DAY)
        ORDER BY booking_date ASC
    ");
    $stmt->execute();
    $bookings_to_approve = $stmt->fetchAll();
    
    if (empty($bookings_to_approve)) {
        echo "No bookings found for auto-approval.\n";
        exit(0);
    }
    
    echo "Found " . count($bookings_to_approve) . " bookings for auto-approval.\n";
    
    $email_notifier = new EmailNotification();
    $approved_count = 0;
    
    foreach ($bookings_to_approve as $booking) {
        try {
            // Update booking status to approved
            $update_stmt = $pdo->prepare("
                UPDATE bookings 
                SET status = 'approved', 
                    approved_at = NOW(),
                    admin_notes = CONCAT(IFNULL(admin_notes, ''), 
                                       IF(admin_notes IS NULL OR admin_notes = '', '', '\n'),
                                       'Auto-approved after 4 days on " . date('Y-m-d H:i:s') . "')
                WHERE booking_id = ?
            ");
            $update_stmt->execute([$booking['booking_id']]);
            
            // Set default pricing if not set
            $pricing = $booking['quoted_price'] ?: calculateDefaultPricing($booking['service_name'], $booking['urgency_level']);
            
            if (!$booking['quoted_price']) {
                $price_stmt = $pdo->prepare("UPDATE bookings SET quoted_price = ? WHERE booking_id = ?");
                $price_stmt->execute([$pricing, $booking['booking_id']]);
            }
            
            // Send approval email
            $email_result = $email_notifier->sendBookingApprovalEmail(
                $booking['email'],
                $booking['name'],
                $booking['booking_id'],
                $booking['service_name'],
                $pricing
            );
            
            if ($email_result) {
                echo "✓ Auto-approved booking #{$booking['booking_id']} for {$booking['name']} - {$booking['service_name']}\n";
                $approved_count++;
            } else {
                echo "✗ Failed to send approval email for booking #{$booking['booking_id']}\n";
            }
            
        } catch (Exception $e) {
            echo "✗ Error processing booking #{$booking['booking_id']}: " . $e->getMessage() . "\n";
        }
    }
    
    echo "\nAuto-approval completed. Approved {$approved_count} out of " . count($bookings_to_approve) . " bookings.\n";
    echo "Process finished at " . date('Y-m-d H:i:s') . "\n";
    
} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage() . "\n";
    exit(1);
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}

/**
 * Calculate default pricing based on service type and urgency
 */
function calculateDefaultPricing($service_name, $urgency) {
    // Base pricing structure
    $base_prices = [
        // Business Services
        'Business Registration' => 299,
        'Tax Consultation' => 199,
        'Business Plan Development' => 899,
        'Financial Advisory' => 399,
        'Compliance Review' => 499,
        
        // Legal Services
        'Contract Review' => 349,
        'Legal Consultation' => 249,
        'Document Preparation' => 199,
        'Intellectual Property' => 599,
        'Litigation Support' => 799,
        
        // Digital Services
        'Website Development' => 1299,
        'SEO Optimization' => 699,
        'Social Media Management' => 599,
        'Digital Marketing Strategy' => 899,
        'E-commerce Setup' => 999,
        
        // Consulting
        'Strategic Planning' => 799,
        'Market Research' => 599,
        'Risk Assessment' => 499,
        'Process Improvement' => 699,
        'Training & Development' => 399
    ];
    
    $base_price = isset($base_prices[$service_name]) ? $base_prices[$service_name] : 399;
    
    // Apply urgency multiplier
    if ($urgency === 'urgent') {
        $base_price *= 1.5; // 50% surcharge for urgent requests
    }
    
    return round($base_price, 2);
}
?>
