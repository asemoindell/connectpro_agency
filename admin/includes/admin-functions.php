<?php
/**
 * Admin Functions
 * Common functions used across admin pages
 */

// Check if admin is logged in
function isAdminLoggedIn() {
    return isset($_SESSION['admin_id']) && !empty($_SESSION['admin_id']);
}

// Get current admin info
function getCurrentAdmin() {
    if (!isAdminLoggedIn()) {
        return null;
    }
    
    try {
        $database = new Database();
        $pdo = $database->getConnection();
        $stmt = $pdo->prepare("SELECT * FROM admins WHERE admin_id = ?");
        $stmt->execute([$_SESSION['admin_id']]);
        return $stmt->fetch();
    } catch (Exception $e) {
        return null;
    }
}

// Redirect to login if not authenticated
function requireAdminLogin() {
    if (!isAdminLoggedIn()) {
        header('Location: login.php');
        exit();
    }
}

// Check admin role permissions
function hasAdminRole($requiredRole = null) {
    if (!isAdminLoggedIn()) {
        return false;
    }
    
    $admin = getCurrentAdmin();
    if (!$admin) {
        return false;
    }
    
    if ($requiredRole === null) {
        return true; // Any admin role is sufficient
    }
    
    $roleHierarchy = [
        'super-admin' => 3,
        'content-admin' => 2,
        'service-admin' => 1
    ];
    
    $adminLevel = $roleHierarchy[$admin['role']] ?? 0;
    $requiredLevel = $roleHierarchy[$requiredRole] ?? 0;
    
    return $adminLevel >= $requiredLevel;
}

// Format currency
function formatCurrency($amount, $currency = 'USD') {
    if (!is_numeric($amount)) {
        return $amount;
    }
    
    switch ($currency) {
        case 'USD':
            return '$' . number_format((float)$amount, 2);
        case 'EUR':
            return '€' . number_format((float)$amount, 2);
        case 'GBP':
            return '£' . number_format((float)$amount, 2);
        default:
            return number_format((float)$amount, 2) . ' ' . $currency;
    }
}

// Format date for admin display
function formatAdminDate($date, $format = 'M j, Y') {
    if (empty($date) || $date === '0000-00-00 00:00:00') {
        return 'N/A';
    }
    
    try {
        return date($format, strtotime($date));
    } catch (Exception $e) {
        return 'Invalid Date';
    }
}

// Format datetime for admin display
function formatAdminDateTime($datetime, $format = 'M j, Y H:i') {
    if (empty($datetime) || $datetime === '0000-00-00 00:00:00') {
        return 'N/A';
    }
    
    try {
        return date($format, strtotime($datetime));
    } catch (Exception $e) {
        return 'Invalid Date';
    }
}

// Sanitize and validate input
function sanitizeInput($input, $type = 'string') {
    if ($input === null) {
        return null;
    }
    
    switch ($type) {
        case 'email':
            return filter_var(trim($input), FILTER_SANITIZE_EMAIL);
        case 'int':
            return filter_var($input, FILTER_SANITIZE_NUMBER_INT);
        case 'float':
            return filter_var($input, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
        case 'url':
            return filter_var(trim($input), FILTER_SANITIZE_URL);
        case 'string':
        default:
            return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }
}

// Generate pagination
function generatePagination($currentPage, $totalPages, $baseUrl, $params = []) {
    if ($totalPages <= 1) {
        return '';
    }
    
    $html = '<div class="pagination">';
    
    // Previous button
    if ($currentPage > 1) {
        $prevUrl = $baseUrl . '?' . http_build_query(array_merge($params, ['page' => $currentPage - 1]));
        $html .= '<a href="' . $prevUrl . '" class="pagination-btn">&laquo; Previous</a>';
    }
    
    // Page numbers
    $start = max(1, $currentPage - 2);
    $end = min($totalPages, $currentPage + 2);
    
    if ($start > 1) {
        $firstUrl = $baseUrl . '?' . http_build_query(array_merge($params, ['page' => 1]));
        $html .= '<a href="' . $firstUrl . '" class="pagination-btn">1</a>';
        if ($start > 2) {
            $html .= '<span class="pagination-dots">...</span>';
        }
    }
    
    for ($i = $start; $i <= $end; $i++) {
        if ($i == $currentPage) {
            $html .= '<span class="pagination-btn active">' . $i . '</span>';
        } else {
            $pageUrl = $baseUrl . '?' . http_build_query(array_merge($params, ['page' => $i]));
            $html .= '<a href="' . $pageUrl . '" class="pagination-btn">' . $i . '</a>';
        }
    }
    
    if ($end < $totalPages) {
        if ($end < $totalPages - 1) {
            $html .= '<span class="pagination-dots">...</span>';
        }
        $lastUrl = $baseUrl . '?' . http_build_query(array_merge($params, ['page' => $totalPages]));
        $html .= '<a href="' . $lastUrl . '" class="pagination-btn">' . $totalPages . '</a>';
    }
    
    // Next button
    if ($currentPage < $totalPages) {
        $nextUrl = $baseUrl . '?' . http_build_query(array_merge($params, ['page' => $currentPage + 1]));
        $html .= '<a href="' . $nextUrl . '" class="pagination-btn">Next &raquo;</a>';
    }
    
    $html .= '</div>';
    return $html;
}

// Log admin action
function logAdminAction($action, $details = '', $targetId = null) {
    if (!isAdminLoggedIn()) {
        return false;
    }
    
    try {
        $database = new Database();
        $pdo = $database->getConnection();
        
        // Create admin_logs table if it doesn't exist
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS admin_logs (
                id INT PRIMARY KEY AUTO_INCREMENT,
                admin_id INT NOT NULL,
                action VARCHAR(100) NOT NULL,
                details TEXT,
                target_id INT,
                ip_address VARCHAR(45),
                user_agent TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_admin_id (admin_id),
                INDEX idx_action (action),
                INDEX idx_created_at (created_at)
            )
        ");
        
        $stmt = $pdo->prepare("
            INSERT INTO admin_logs (admin_id, action, details, target_id, ip_address, user_agent) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        
        return $stmt->execute([
            $_SESSION['admin_id'],
            $action,
            $details,
            $targetId,
            $ipAddress,
            $userAgent
        ]);
    } catch (Exception $e) {
        error_log("Failed to log admin action: " . $e->getMessage());
        return false;
    }
}

// Flash message functions
function setFlashMessage($type, $message) {
    if (!isset($_SESSION['flash_messages'])) {
        $_SESSION['flash_messages'] = [];
    }
    $_SESSION['flash_messages'][] = ['type' => $type, 'message' => $message];
}

function getFlashMessages() {
    $messages = $_SESSION['flash_messages'] ?? [];
    unset($_SESSION['flash_messages']);
    return $messages;
}

function hasFlashMessages() {
    return !empty($_SESSION['flash_messages']);
}

// Render flash messages HTML
function renderFlashMessages() {
    $messages = getFlashMessages();
    $html = '';
    
    foreach ($messages as $message) {
        $type = $message['type'];
        $text = $message['message'];
        $icon = '';
        
        switch ($type) {
            case 'success':
                $icon = 'fas fa-check-circle';
                break;
            case 'error':
            case 'danger':
                $icon = 'fas fa-exclamation-circle';
                break;
            case 'warning':
                $icon = 'fas fa-exclamation-triangle';
                break;
            case 'info':
                $icon = 'fas fa-info-circle';
                break;
        }
        
        $html .= '<div class="alert alert-' . $type . ' alert-dismissible">';
        if ($icon) {
            $html .= '<i class="' . $icon . '"></i> ';
        }
        $html .= htmlspecialchars($text);
        $html .= '<button type="button" class="close" onclick="this.parentElement.remove()">&times;</button>';
        $html .= '</div>';
    }
    
    return $html;
}

// Export data to CSV
function exportToCSV($data, $filename, $headers = []) {
    if (empty($data)) {
        return false;
    }
    
    // Set headers for download
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
    
    $output = fopen('php://output', 'w');
    
    // Write headers if provided
    if (!empty($headers)) {
        fputcsv($output, $headers);
    } else {
        // Use first row keys as headers
        fputcsv($output, array_keys($data[0]));
    }
    
    // Write data
    foreach ($data as $row) {
        fputcsv($output, $row);
    }
    
    fclose($output);
    return true;
}

// Validate admin permissions for specific actions
function validateAdminPermission($action, $targetId = null) {
    if (!isAdminLoggedIn()) {
        return false;
    }
    
    $admin = getCurrentAdmin();
    if (!$admin) {
        return false;
    }
    
    // Super admins can do everything
    if ($admin['role'] === 'super-admin') {
        return true;
    }
    
    // Define permission rules
    $permissions = [
        'content-admin' => [
            'view_users', 'edit_users', 'view_bookings', 'edit_bookings',
            'view_services', 'edit_services', 'view_payments', 'view_email_templates',
            'edit_email_templates'
        ],
        'service-admin' => [
            'view_bookings', 'edit_bookings', 'view_services', 'edit_services',
            'view_payments'
        ]
    ];
    
    $adminPermissions = $permissions[$admin['role']] ?? [];
    
    return in_array($action, $adminPermissions);
}

// Get database statistics for dashboard
function getAdminStats() {
    try {
        $database = new Database();
        $pdo = $database->getConnection();
        
        $stats = [];
        
        // Total bookings
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM service_bookings");
        $stats['total_bookings'] = $stmt->fetch()['count'] ?? 0;
        
        // Pending approvals
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM service_bookings WHERE status = 'waiting_approval'");
        $stats['pending_approvals'] = $stmt->fetch()['count'] ?? 0;
        
        // Completed bookings
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM service_bookings WHERE status = 'completed'");
        $stats['completed_bookings'] = $stmt->fetch()['count'] ?? 0;
        
        // Total users
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
        $stats['total_users'] = $stmt->fetch()['count'] ?? 0;
        
        // Active users
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE status = 'active'");
        $stats['active_users'] = $stmt->fetch()['count'] ?? 0;
        
        // Total revenue from payments
        $stmt = $pdo->query("SELECT SUM(amount) as total FROM payments WHERE payment_status = 'completed'");
        $stats['total_revenue'] = $stmt->fetch()['total'] ?? 0;
        
        // Crypto payments
        $stmt = $pdo->query("
            SELECT 
                COUNT(*) as crypto_payments_count,
                SUM(amount) as crypto_revenue
            FROM payments 
            WHERE payment_method IN ('usdt', 'bitcoin') AND payment_status = 'completed'
        ");
        $crypto_stats = $stmt->fetch();
        $stats['crypto_payments'] = $crypto_stats['crypto_payments_count'] ?? 0;
        $stats['crypto_revenue'] = $crypto_stats['crypto_revenue'] ?? 0;
        
        return $stats;
    } catch (Exception $e) {
        error_log("Failed to get admin stats: " . $e->getMessage());
        return [
            'total_bookings' => 0,
            'pending_approvals' => 0,
            'completed_bookings' => 0,
            'total_users' => 0,
            'active_users' => 0,
            'total_revenue' => 0,
            'crypto_payments' => 0,
            'crypto_revenue' => 0
        ];
    }
}
?>
