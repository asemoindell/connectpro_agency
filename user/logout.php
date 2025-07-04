<?php
/**
 * User Logout Service
 * Handles secure logout for regular users with session cleanup and activity logging
 */

session_start();
require_once '../config/database.php';

// Function to log logout activity
function logLogoutActivity($db, $user_id, $email) {
    try {
        $stmt = $db->prepare("INSERT INTO user_activity_log (user_id, action, ip_address, user_agent, created_at) VALUES (?, ?, ?, ?, NOW())");
        $stmt->execute([
            $user_id,
            'logout',
            $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ]);
    } catch (Exception $e) {
        // Log error but don't prevent logout
        error_log("Failed to log logout activity: " . $e->getMessage());
    }
}

// Check if user is logged in and log the logout
if (isset($_SESSION['user_id']) && isset($_SESSION['user_email'])) {
    try {
        $database = new Database();
        $db = $database->getConnection();
        
        // Log the logout activity
        logLogoutActivity($db, $_SESSION['user_id'], $_SESSION['user_email']);
        
        // Update last activity
        $stmt = $db->prepare("UPDATE users SET last_activity = NOW() WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        
    } catch (Exception $e) {
        // Don't prevent logout if logging fails
        error_log("Logout logging error: " . $e->getMessage());
    }
}

// Store user type for redirect decision
$was_logged_in = isset($_SESSION['user_id']);

// Clear all session variables
$_SESSION = array();

// Destroy the session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destroy the session
session_destroy();

// Clear any remember me cookies
$remember_cookies = ['remember_user_email', 'remember_user_token', 'user_preference'];
foreach ($remember_cookies as $cookie) {
    if (isset($_COOKIE[$cookie])) {
        setcookie($cookie, '', time() - 3600, '/');
        setcookie($cookie, '', time() - 3600, '/Agency/');
    }
}

// Clear browser cache for security
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Redirect based on how they accessed logout
if ($was_logged_in) {
    // Successful logout - redirect to home with success message
    header('Location: ../index.php?logout=success&message=' . urlencode('You have been successfully logged out.'));
} else {
    // Already logged out - redirect to login
    header('Location: login.php?message=' . urlencode('Please log in to access your account.'));
}
exit();
?>
