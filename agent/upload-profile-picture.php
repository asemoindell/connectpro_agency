<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

// Check if agent is logged in
if (!isset($_SESSION['agent_logged_in'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

$agent_id = $_SESSION['agent_id'];
$response = ['success' => false, 'error' => ''];

try {
    if (!isset($_FILES['profile_image'])) {
        throw new Exception('No file uploaded');
    }
    
    $file = $_FILES['profile_image'];
    
    // Validate file
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('File upload error');
    }
    
    // Check file size (2MB max)
    if ($file['size'] > 2 * 1024 * 1024) {
        throw new Exception('File size too large. Maximum 2MB allowed.');
    }
    
    // Check file type
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $file_type = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($file_type, $allowed_types)) {
        throw new Exception('Invalid file type. Only JPG, PNG, GIF, and WebP are allowed.');
    }
    
    // Create uploads directory if it doesn't exist
    $upload_dir = '../uploads/agents/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    // Generate unique filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'agent_' . $agent_id . '_' . time() . '.' . $extension;
    $filepath = $upload_dir . $filename;
    
    // Move uploaded file
    if (!move_uploaded_file($file['tmp_name'], $filepath)) {
        throw new Exception('Failed to save uploaded file');
    }
    
    // Update database
    $database = new Database();
    $db = $database->getConnection();
    
    // Get old image to delete it
    $stmt = $db->prepare("SELECT profile_image FROM admin_users WHERE id = ?");
    $stmt->execute([$agent_id]);
    $old_image = $stmt->fetchColumn();
    
    // Update with new image path
    $relative_path = 'uploads/agents/' . $filename;
    $stmt = $db->prepare("UPDATE admin_users SET profile_image = ?, updated_at = NOW() WHERE id = ?");
    $result = $stmt->execute([$relative_path, $agent_id]);
    
    if (!$result) {
        // Delete uploaded file if database update fails
        unlink($filepath);
        throw new Exception('Failed to update database');
    }
    
    // Delete old image file if it exists
    if ($old_image && file_exists('../' . $old_image)) {
        unlink('../' . $old_image);
    }
    
    $response = [
        'success' => true,
        'message' => 'Profile picture updated successfully',
        'image_path' => $relative_path
    ];
    
} catch (Exception $e) {
    $response['error'] = $e->getMessage();
}

echo json_encode($response);
?>
