<?php
session_start();
require_once '../config/database.php';
require_once 'includes/admin-functions.php';

// Check if admin is logged in
if (!isAdminLoggedIn()) {
    header('Location: login.php');
    exit();
}

$database = new Database();
$db = $database->getConnection();

$user_id = $_GET['user_id'] ?? 0;

// Get user details
$stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    die("User not found.");
}

// Get user's bookings with service details
$bookings_stmt = $db->prepare("
    SELECT 
        sb.*,
        se.title as service_name,
        se.description as service_description,
        se.category,
        sa.name as agent_name,
        sa.email as agent_email,
        p.payment_status,
        p.payment_method,
        p.amount as payment_amount,
        p.payment_reference
    FROM service_bookings sb
    LEFT JOIN services_enhanced se ON sb.service_id = se.id
    LEFT JOIN service_agents sa ON se.assigned_agent_id = sa.id
    LEFT JOIN payments p ON sb.id = p.booking_id
    WHERE sb.user_id = ?
    ORDER BY sb.booking_date DESC
");
$bookings_stmt->execute([$user_id]);
$bookings = $bookings_stmt->fetchAll();

// Get booking statistics
$stats_stmt = $db->prepare("
    SELECT 
        COUNT(*) as total_bookings,
        COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_bookings,
        COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_bookings,
        COUNT(CASE WHEN status = 'cancelled' THEN 1 END) as cancelled_bookings,
        SUM(total_amount) as total_spent,
        AVG(total_amount) as avg_booking_value
    FROM service_bookings 
    WHERE user_id = ?
");
$stats_stmt->execute([$user_id]);
$stats = $stats_stmt->fetch();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bookings - <?php echo htmlspecialchars(trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''))); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/admin-unified.css">
    <style>
        body {
            margin: 0;
            padding: 20px;
            background: var(--bg-primary);
            font-family: 'Inter', sans-serif;
        }
        .bookings-container {
            max-width: 1200px;
            margin: 0 auto;
        }
        .page-header {
            background: var(--bg-card);
            padding: 2rem;
            border-radius: var(--border-radius);
            margin-bottom: 2rem;
            box-shadow: var(--shadow-sm);
        }
        .user-info {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        .user-avatar {
            width: 60px;
            height: 60px;
            background: var(--primary-color);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 1.5rem;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        .stat-card {
            background: var(--bg-card);
            padding: 1.5rem;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-sm);
            text-align: center;
        }
        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 0.5rem;
        }
        .stat-label {
            color: var(--text-muted);
            font-size: 0.9rem;
        }
        .bookings-list {
            background: var(--bg-card);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-sm);
            overflow: hidden;
        }
        .list-header {
            background: var(--bg-secondary);
            padding: 1.5rem;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .booking-item {
            padding: 1.5rem;
            border-bottom: 1px solid var(--border-color);
            transition: background-color 0.2s;
        }
        .booking-item:hover {
            background: var(--bg-secondary);
        }
        .booking-item:last-child {
            border-bottom: none;
        }
        .booking-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
        }
        .booking-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
        }
        .booking-ref {
            font-size: 0.875rem;
            color: var(--text-muted);
            font-family: monospace;
        }
        .booking-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin: 1rem 0;
        }
        .detail-item {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }
        .detail-label {
            font-size: 0.8rem;
            color: var(--text-muted);
            text-transform: uppercase;
            font-weight: 500;
        }
        .detail-value {
            color: var(--text-primary);
            font-weight: 500;
        }
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: var(--text-muted);
        }
        .empty-state i {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }
        .close-btn {
            position: fixed;
            top: 20px;
            right: 20px;
            background: var(--primary-color);
            color: white;
            border: none;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            font-size: 1.2rem;
            cursor: pointer;
            box-shadow: var(--shadow-lg);
            z-index: 1000;
        }
        .close-btn:hover {
            background: var(--primary-dark);
        }
    </style>
</head>
<body>
    <button class="close-btn" onclick="window.close();" title="Close Window">
        <i class="fas fa-times"></i>
    </button>
    
    <div class="bookings-container">
        <!-- Page Header -->
        <div class="page-header">
            <div class="user-info">
                <div class="user-avatar">
                    <?php 
                    $full_name = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
                    echo strtoupper(substr($full_name ?: 'U', 0, 2)); 
                    ?>
                </div>
                <div>
                    <h1 style="margin: 0; color: var(--text-primary);">
                        <?php echo htmlspecialchars($full_name); ?>
                    </h1>
                    <p style="margin: 0; color: var(--text-muted);">
                        <i class="fas fa-envelope"></i> <?php echo htmlspecialchars($user['email']); ?>
                        <?php if ($user['phone']): ?>
                            &nbsp;&nbsp;<i class="fas fa-phone"></i> <?php echo htmlspecialchars($user['phone']); ?>
                        <?php endif; ?>
                    </p>
                </div>
            </div>
            <h2 style="margin: 0; color: var(--text-secondary);">
                <i class="fas fa-calendar-check"></i> Booking History
            </h2>
        </div>
        
        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo number_format($stats['total_bookings'] ?? 0); ?></div>
                <div class="stat-label">Total Bookings</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo number_format($stats['completed_bookings'] ?? 0); ?></div>
                <div class="stat-label">Completed</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo number_format($stats['pending_bookings'] ?? 0); ?></div>
                <div class="stat-label">Pending</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">$<?php echo number_format($stats['total_spent'] ?? 0, 2); ?></div>
                <div class="stat-label">Total Spent</div>
            </div>
        </div>
        
        <!-- Bookings List -->
        <div class="bookings-list">
            <div class="list-header">
                <h3 style="margin: 0;"><i class="fas fa-list"></i> All Bookings</h3>
                <span class="text-muted"><?php echo count($bookings); ?> total</span>
            </div>
            
            <?php if (empty($bookings)): ?>
                <div class="empty-state">
                    <i class="fas fa-calendar-times"></i>
                    <h3>No Bookings Found</h3>
                    <p>This user hasn't made any bookings yet.</p>
                </div>
            <?php else: ?>
                <?php foreach ($bookings as $booking): ?>
                    <div class="booking-item">
                        <div class="booking-header">
                            <div>
                                <div class="booking-title">
                                    <?php echo htmlspecialchars($booking['service_name'] ?: 'Service Not Found'); ?>
                                </div>
                                <div class="booking-ref">
                                    Ref: <?php echo htmlspecialchars($booking['booking_reference']); ?>
                                </div>
                            </div>
                            <div>
                                <?php echo createStatusBadge($booking['status']); ?>
                            </div>
                        </div>
                        
                        <div class="booking-details">
                            <div class="detail-item">
                                <div class="detail-label">Booking Date</div>
                                <div class="detail-value">
                                    <?php echo date('M j, Y H:i', strtotime($booking['booking_date'])); ?>
                                </div>
                            </div>
                            
                            <div class="detail-item">
                                <div class="detail-label">Service Category</div>
                                <div class="detail-value">
                                    <?php echo htmlspecialchars($booking['category'] ?: 'N/A'); ?>
                                </div>
                            </div>
                            
                            <?php if ($booking['agent_name']): ?>
                            <div class="detail-item">
                                <div class="detail-label">Assigned Agent</div>
                                <div class="detail-value">
                                    <?php echo htmlspecialchars($booking['agent_name']); ?>
                                </div>
                            </div>
                            <?php endif; ?>
                            
                            <div class="detail-item">
                                <div class="detail-label">Amount</div>
                                <div class="detail-value">
                                    $<?php echo number_format($booking['total_amount'] ?? 0, 2); ?>
                                </div>
                            </div>
                            
                            <?php if ($booking['payment_status']): ?>
                            <div class="detail-item">
                                <div class="detail-label">Payment Status</div>
                                <div class="detail-value">
                                    <?php echo createStatusBadge($booking['payment_status']); ?>
                                </div>
                            </div>
                            <?php endif; ?>
                            
                            <?php if ($booking['payment_method']): ?>
                            <div class="detail-item">
                                <div class="detail-label">Payment Method</div>
                                <div class="detail-value">
                                    <?php
                                    $icons = [
                                        'stripe' => 'fab fa-stripe',
                                        'paypal' => 'fab fa-paypal',
                                        'usdt' => 'fab fa-bitcoin',
                                        'bitcoin' => 'fab fa-bitcoin',
                                        'bank_transfer' => 'fas fa-university'
                                    ];
                                    $icon = $icons[$booking['payment_method']] ?? 'fas fa-credit-card';
                                    ?>
                                    <i class="<?php echo $icon; ?>"></i>
                                    <?php echo ucfirst(str_replace('_', ' ', $booking['payment_method'])); ?>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <?php if ($booking['service_details']): ?>
                            <div style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid var(--border-color);">
                                <div class="detail-label">Service Details</div>
                                <div class="detail-value" style="margin-top: 0.5rem;">
                                    <?php echo nl2br(htmlspecialchars($booking['service_details'])); ?>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($booking['admin_notes']): ?>
                            <div style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid var(--border-color);">
                                <div class="detail-label">Admin Notes</div>
                                <div class="detail-value" style="margin-top: 0.5rem;">
                                    <?php echo nl2br(htmlspecialchars($booking['admin_notes'])); ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Auto-close functionality
        window.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                window.close();
            }
        });
    </script>
</body>
</html>
