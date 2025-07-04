<?php
// Enhanced upload-payment-proof.php with better debugging
// Suppress ALL errors and warnings from output
error_reporting(0);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', '../uploads/payment_proofs/upload_errors.log');

// Start output buffering to catch any unwanted output
ob_start();

// Start session
session_start();

// Include database connection
require_once '../config/database.php';

// Clean any previous output and set JSON header
if (ob_get_level()) {
    ob_clean();
}

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate');
header('Pragma: no-cache');

// Function to log debug info
function logDebug($message) {
    error_log(date('Y-m-d H:i:s') . ' - ' . $message . PHP_EOL, 3, '../uploads/payment_proofs/upload_debug.log');
}

// Function to send JSON response and exit
function sendJsonResponse($data) {
    // Clear any output buffer
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    // Log the response
    logDebug('Sending response: ' . json_encode($data));
    
    // Send response
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit();
}

// Log start of request
logDebug('Upload request started');
logDebug('Session ID: ' . session_id());
logDebug('Session data: ' . print_r($_SESSION, true));
logDebug('POST data: ' . print_r($_POST, true));
logDebug('FILES data: ' . print_r($_FILES, true));

// Check if user is logged in
if (!isset($_SESSION['user_id']) && !isset($_SESSION['agent_logged_in'])) {
    logDebug('User not logged in');
    sendJsonResponse(['success' => false, 'error' => 'Unauthorized']);
}

$response = ['success' => false, 'error' => ''];

try {
    if (!isset($_POST['payment_id']) || !isset($_FILES['proof_image'])) {
        logDebug('Missing required data - payment_id or proof_image');
        throw new Exception('Missing required data');
    }
    
    $payment_id = intval($_POST['payment_id']);
    $file = $_FILES['proof_image'];
    
    logDebug('Processing payment_id: ' . $payment_id);
    logDebug('File info: ' . print_r($file, true));
    
    // Validate file
    if ($file['error'] !== UPLOAD_ERR_OK) {
        logDebug('File upload error: ' . $file['error']);
        throw new Exception('File upload error: ' . $file['error']);
    }
    
    // Get file type first
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $file_type = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    logDebug('File type detected: ' . $file_type);
    
    // Check file size (10MB max for documents, 5MB for images)
    $max_size = 10 * 1024 * 1024; // 10MB default
    if (strpos($file_type, 'image/') === 0) {
        $max_size = 5 * 1024 * 1024; // 5MB for images
    }
    
    if ($file['size'] > $max_size) {
        $max_size_mb = round($max_size / 1024 / 1024);
        logDebug('File too large: ' . $file['size'] . ' bytes, max allowed: ' . $max_size . ' bytes');
        throw new Exception("File size too large. Maximum {$max_size_mb}MB allowed.");
    }
    
    // Check file type - Allow images, PDFs, and text files
    $allowed_types = [
        // Image formats
        'image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp', 'image/bmp', 'image/tiff',
        // PDF format
        'application/pdf',
        // Text formats
        'text/plain', 'text/txt',
        // Document formats
        'application/msword', // .doc
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document', // .docx
        // Additional formats that might be used as proof
        'application/rtf', // Rich Text Format
        'application/vnd.ms-excel', // .xls
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' // .xlsx
    ];
    
    // File type already detected above
    
    // Also check file extension as a fallback
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'tiff', 'pdf', 'txt', 'doc', 'docx', 'rtf', 'xls', 'xlsx'];
    
    logDebug('File extension: ' . $extension);
    
    if (!in_array($file_type, $allowed_types) && !in_array($extension, $allowed_extensions)) {
        logDebug('Invalid file type: ' . $file_type . ' and extension: ' . $extension);
        throw new Exception('Invalid file type. Allowed formats: Images (JPG, PNG, GIF, WebP, BMP, TIFF), PDF, Text files (TXT), and Documents (DOC, DOCX, RTF, XLS, XLSX).');
    }
    
    // Create uploads directory if it doesn't exist
    $upload_dir = realpath(__DIR__ . '/../uploads/payment_proofs/') . '/';
    
    // If realpath fails, use absolute path
    if (!$upload_dir || $upload_dir === '/') {
        $upload_dir = '/Applications/XAMPP/xamppfiles/htdocs/Agency/uploads/payment_proofs/';
    }
    
    logDebug('Upload directory resolved to: ' . $upload_dir);
    
    if (!is_dir($upload_dir)) {
        logDebug('Creating upload directory: ' . $upload_dir);
        mkdir($upload_dir, 0755, true);
    }
    
    // Generate unique filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'payment_proof_' . $payment_id . '_' . time() . '.' . $extension;
    $filepath = $upload_dir . $filename;
    
    logDebug('Target filepath: ' . $filepath);
    
    // Move uploaded file
    logDebug('Checking file before move:');
    logDebug('- tmp_name exists: ' . (file_exists($file['tmp_name']) ? 'Yes' : 'No'));
    logDebug('- tmp_name is uploaded: ' . (is_uploaded_file($file['tmp_name']) ? 'Yes' : 'No'));
    logDebug('- target directory exists: ' . (is_dir($upload_dir) ? 'Yes' : 'No'));
    logDebug('- target directory writable: ' . (is_writable($upload_dir) ? 'Yes' : 'No'));
    logDebug('- target filepath: ' . $filepath);
    
    // Temporarily enable error reporting for the move operation
    $old_error_reporting = error_reporting();
    error_reporting(E_ALL);
    
    if (!move_uploaded_file($file['tmp_name'], $filepath)) {
        $last_error = error_get_last();
        logDebug('move_uploaded_file failed');
        logDebug('Last error: ' . print_r($last_error, true));
        logDebug('Source file exists after failed move: ' . (file_exists($file['tmp_name']) ? 'Yes' : 'No'));
        logDebug('Target file exists after failed move: ' . (file_exists($filepath) ? 'Yes' : 'No'));
        
        // Restore error reporting
        error_reporting($old_error_reporting);
        
        throw new Exception('Failed to save uploaded file');
    }
    
    // Restore error reporting
    error_reporting($old_error_reporting);
    
    logDebug('File moved successfully');
    
    // Update database
    $database = new Database();
    $db = $database->getConnection();
    
    // Get current payment info
    $stmt = $db->prepare("SELECT * FROM crypto_payments WHERE id = ?");
    $stmt->execute([$payment_id]);
    $payment = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$payment) {
        logDebug('Payment not found for ID: ' . $payment_id);
        unlink($filepath);
        throw new Exception('Payment not found');
    }
    
    logDebug('Payment found: ' . print_r($payment, true));
    
    // Delete old proof image if exists
    if ($payment['proof_image'] && file_exists($upload_dir . $payment['proof_image'])) {
        unlink($upload_dir . $payment['proof_image']);
        logDebug('Old proof image deleted: ' . $payment['proof_image']);
    }
    
    // Update payment record
    $stmt = $db->prepare("
        UPDATE crypto_payments 
        SET proof_image = ?, status = 'proof_uploaded', updated_at = NOW()
        WHERE id = ?
    ");
    $result = $stmt->execute([$filename, $payment_id]);
    
    if (!$result) {
        logDebug('Database update failed');
        unlink($filepath);
        throw new Exception('Failed to update database');
    }
    
    logDebug('Database updated successfully');
    
    // Update booking status
    $stmt = $db->prepare("
        UPDATE service_bookings 
        SET payment_status = 'processing', updated_at = NOW()
        WHERE id = ?
    ");
    $stmt->execute([$payment['booking_id']]);
    
    logDebug('Booking status updated');
    
    // Send notification to admins (optional)
    $stmt = $db->prepare("
        INSERT INTO notifications (user_type, user_id, title, message, type, created_at)
        SELECT 'admin', id, 'Payment Proof Uploaded', 
               CONCAT('Payment proof uploaded for booking #', ?), 
               'payment', NOW()
        FROM admin_users WHERE role = 'admin'
    ");
    $stmt->execute([$payment['booking_id']]);
    
    logDebug('Notification sent to admins');
    
    $response = [
        'success' => true,
        'message' => 'Payment proof uploaded successfully. Your payment is now being verified.',
        'filename' => $filename
    ];
    
} catch (Exception $e) {
    logDebug('Exception: ' . $e->getMessage());
    $response['error'] = $e->getMessage();
} catch (Error $e) {
    logDebug('Error: ' . $e->getMessage());
    $response['error'] = 'System error occurred';
} catch (Throwable $e) {
    logDebug('Throwable: ' . $e->getMessage());
    $response['error'] = 'Unexpected error occurred';
}

sendJsonResponse($response);
?>
