<?php
/**
 * Admin Logout Service
 * Handles secure logout for admin users with session cleanup and activity logging
 */

session_start();
require_once '../config/database.php';

// Function to log admin logout activity
function logAdminLogoutActivity($db, $admin_id, $email) {
    try {
        $stmt = $db->prepare("INSERT INTO admin_activity_log (admin_id, action, ip_address, user_agent, created_at) VALUES (?, ?, ?, ?, NOW())");
        $stmt->execute([
            $admin_id,
            'logout',
            $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ]);
    } catch (Exception $e) {
        // If admin_activity_log doesn't exist, try generic activity log
        try {
            $stmt = $db->prepare("INSERT INTO user_activity_log (user_id, action, ip_address, user_agent, created_at) VALUES (?, ?, ?, ?, NOW())");
            $stmt->execute([
                $admin_id,
                'admin_logout',
                $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
            ]);
        } catch (Exception $e2) {
            // Log error but don't prevent logout
            error_log("Failed to log admin logout activity: " . $e2->getMessage());
        }
    }
}

// Check if admin is logged in and log the logout
if (isset($_SESSION['admin_id']) && isset($_SESSION['admin_email'])) {
    try {
        $database = new Database();
        $db = $database->getConnection();
        
        // Log the logout activity
        logAdminLogoutActivity($db, $_SESSION['admin_id'], $_SESSION['admin_email']);
        
        // Update admin last activity
        $stmt = $db->prepare("UPDATE admin_users SET last_activity = NOW() WHERE id = ?");
        $stmt->execute([$_SESSION['admin_id']]);
        
    } catch (Exception $e) {
        // Don't prevent logout if logging fails
        error_log("Admin logout logging error: " . $e->getMessage());
    }
}

// Store admin status for redirect decision
$was_admin_logged_in = isset($_SESSION['admin_id']);

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

// Clear any admin remember me cookies
$admin_cookies = ['remember_admin_email', 'remember_admin_token', 'admin_preference', 'admin_session'];
foreach ($admin_cookies as $cookie) {
    if (isset($_COOKIE[$cookie])) {
        setcookie($cookie, '', time() - 3600, '/');
        setcookie($cookie, '', time() - 3600, '/Agency/');
        setcookie($cookie, '', time() - 3600, '/Agency/admin/');
    }
}

// Clear browser cache for security
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Redirect based on logout status
if ($was_admin_logged_in) {
    // Successful admin logout - redirect to admin login with success message
    header('Location: login.php?logout=success&message=' . urlencode('Admin logout successful. Please log in again to access the admin panel.'));
} else {
    // Not logged in - redirect to admin login
    header('Location: login.php?message=' . urlencode('Please log in to access the admin panel.'));
}
exit();
?>
