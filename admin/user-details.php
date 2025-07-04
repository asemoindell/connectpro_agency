<?php
session_start();
require_once '../config/database.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

$database = new Database();
$db = $database->getConnection();

$user_id = $_GET['id'] ?? 0;

// Get user details
$stmt = $db->prepare("
    SELECT 
        u.*,
        CONCAT(au.first_name, ' ', au.last_name) as approved_by_name,
        ul.country, ul.state, ul.city, ul.timezone,
        COUNT(sb.id) as total_bookings,
        COUNT(CASE WHEN sb.status = 'completed' THEN 1 END) as completed_bookings,
        COUNT(CASE WHEN sb.status = 'pending' THEN 1 END) as pending_bookings,
        SUM(sb.total_amount) as total_spent
    FROM users u
    LEFT JOIN admin_users au ON u.approved_by = au.id
    LEFT JOIN user_locations ul ON u.id = ul.user_id AND ul.is_primary = 1
    LEFT JOIN service_bookings sb ON u.id = sb.user_id
    WHERE u.id = ?
    GROUP BY u.id
");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    die("User not found.");
}

// Get user's login history
$login_stmt = $db->prepare("
    SELECT * FROM user_login_logs 
    WHERE user_id = ? 
    ORDER BY login_time DESC 
    LIMIT 20
");
$login_stmt->execute([$user_id]);
$login_history = $login_stmt->fetchAll();

// Get user's activity log
$activity_stmt = $db->prepare("
    SELECT * FROM user_activity_log 
    WHERE user_id = ? 
    ORDER BY created_at DESC 
    LIMIT 30
");
$activity_stmt->execute([$user_id]);
$activities = $activity_stmt->fetchAll();

// Get user's bookings
$bookings_stmt = $db->prepare("
    SELECT 
        sb.*,
        se.title as service_name,
        sa.name as agent_name
    FROM service_bookings sb
    JOIN services_enhanced se ON sb.service_id = se.id
    LEFT JOIN service_agents sa ON se.assigned_agent_id = sa.id
    WHERE sb.user_id = ?
    ORDER BY sb.booking_date DESC
");
$bookings_stmt->execute([$user_id]);
$bookings = $bookings_stmt->fetchAll();

// Get user's service preferences
$preferences_stmt = $db->prepare("
    SELECT 
        usp.*,
        se.title as service_name,
        sa.name as preferred_agent_name
    FROM user_service_preferences usp
    JOIN services_enhanced se ON usp.service_id = se.id
    LEFT JOIN service_agents sa ON usp.preferred_agent_id = sa.id
    WHERE usp.user_id = ?
    ORDER BY usp.interest_level DESC, usp.updated_at DESC
");
$preferences_stmt->execute([$user_id]);
$preferences = $preferences_stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Details - <?php echo htmlspecialchars(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .user-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 2rem;
            font-weight: bold;
        }
        
        .status-badge {
            padding: 0.5rem 1rem;
            border-radius: 0.375rem;
            font-weight: 600;
        }
        
        .status-pending { background-color: #fff3cd; color: #856404; }
        .status-approved { background-color: #d1e7dd; color: #0f5132; }
        .status-rejected { background-color: #f8d7da; color: #721c24; }
        .status-suspended { background-color: #f0f0f0; color: #495057; }
        
        .activity-item {
            border-left: 3px solid #007bff;
            padding-left: 1rem;
            margin-bottom: 1rem;
        }
        
        .timeline-item {
            position: relative;
            padding-left: 2rem;
            margin-bottom: 1.5rem;
        }
        
        .timeline-item::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0.5rem;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: #007bff;
        }
        
        .stats-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px;
            padding: 1.5rem;
        }
        
        .info-card {
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <div class="container-fluid p-4">
        <div class="row">
            <!-- User Profile Header -->
            <div class="col-12 mb-4">
                <div class="card info-card">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-md-2 text-center">
                                <div class="user-avatar mx-auto">
                                    <?php 
                                    $full_name = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
                                    echo strtoupper(substr($full_name ?: 'U', 0, 2)); 
                                    ?>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <h2 class="mb-1"><?php echo htmlspecialchars(trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''))); ?></h2>
                                <p class="text-muted mb-2">
                                    <i class="fas fa-envelope"></i> <?php echo htmlspecialchars($user['email']); ?>
                                </p>
                                <?php if ($user['phone']): ?>
                                    <p class="text-muted mb-2">
                                        <i class="fas fa-phone"></i> <?php echo htmlspecialchars($user['phone']); ?>
                                    </p>
                                <?php endif; ?>
                                <span class="status-badge status-<?php echo $user['status']; ?>">
                                    <?php echo ucfirst($user['status']); ?>
                                </span>
                            </div>
                            <div class="col-md-4 text-end">
                                <div class="mb-2">
                                    <strong>User ID:</strong> <?php echo $user['id']; ?>
                                </div>
                                <div class="mb-2">
                                    <strong>Registered:</strong> <?php echo date('M j, Y', strtotime($user['created_at'])); ?>
                                </div>
                                <?php if ($user['last_activity']): ?>
                                    <div class="mb-2">
                                        <strong>Last Activity:</strong> <?php echo date('M j, Y H:i', strtotime($user['last_activity'])); ?>
                                    </div>
                                <?php endif; ?>
                                <?php if ($user['approved_by_name']): ?>
                                    <div class="mb-2">
                                        <strong>Approved by:</strong> <?php echo htmlspecialchars($user['approved_by_name']); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Statistics Cards -->
            <div class="col-md-3 mb-4">
                <div class="stats-card text-center">
                    <h3><?php echo $user['total_bookings']; ?></h3>
                    <p>Total Bookings</p>
                </div>
            </div>
            <div class="col-md-3 mb-4">
                <div class="stats-card text-center">
                    <h3><?php echo $user['completed_bookings']; ?></h3>
                    <p>Completed</p>
                </div>
            </div>
            <div class="col-md-3 mb-4">
                <div class="stats-card text-center">
                    <h3><?php echo $user['pending_bookings']; ?></h3>
                    <p>Pending</p>
                </div>
            </div>
            <div class="col-md-3 mb-4">
                <div class="stats-card text-center">
                    <h3>$<?php echo formatCurrency($user['total_spent'] ?? 0); ?></h3>
                    <p>Total Spent</p>
                </div>
            </div>
            
            <!-- Location & Activity -->
            <div class="col-md-6 mb-4">
                <div class="card info-card">
                    <div class="card-header">
                        <h5><i class="fas fa-map-marker-alt"></i> Location & Login History</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($user['country']): ?>
                            <p><strong>Primary Location:</strong> 
                               <?php echo htmlspecialchars($user['city'] . ', ' . $user['state'] . ', ' . $user['country']); ?>
                            </p>
                            <?php if ($user['timezone']): ?>
                                <p><strong>Timezone:</strong> <?php echo htmlspecialchars($user['timezone']); ?></p>
                            <?php endif; ?>
                        <?php else: ?>
                            <p class="text-muted">No location information available</p>
                        <?php endif; ?>
                        
                        <hr>
                        <h6>Recent Logins:</h6>
                        <?php if ($login_history): ?>
                            <?php foreach (array_slice($login_history, 0, 5) as $login): ?>
                                <div class="timeline-item">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <strong><?php echo date('M j, Y H:i', strtotime($login['login_time'])); ?></strong>
                                            <br>
                                            <small class="text-muted">
                                                <i class="fas fa-globe"></i> <?php echo htmlspecialchars($login['ip_address']); ?>
                                                <?php if ($login['location']): ?>
                                                    | <?php echo htmlspecialchars($login['location']); ?>
                                                <?php endif; ?>
                                            </small>
                                        </div>
                                        <span class="badge bg-success">Success</span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="text-muted">No login history available</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Recent Activities -->
            <div class="col-md-6 mb-4">
                <div class="card info-card">
                    <div class="card-header">
                        <h5><i class="fas fa-clock"></i> Recent Activities</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($activities): ?>
                            <?php foreach (array_slice($activities, 0, 10) as $activity): ?>
                                <div class="activity-item">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <strong><?php echo ucfirst(str_replace('_', ' ', $activity['action'])); ?></strong>
                                            <?php if ($activity['description']): ?>
                                                <br><small class="text-muted"><?php echo htmlspecialchars($activity['description']); ?></small>
                                            <?php endif; ?>
                                        </div>
                                        <small class="text-muted">
                                            <?php echo date('M j, H:i', strtotime($activity['created_at'])); ?>
                                        </small>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="text-muted">No recent activities</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Service Preferences -->
            <div class="col-md-6 mb-4">
                <div class="card info-card">
                    <div class="card-header">
                        <h5><i class="fas fa-heart"></i> Service Preferences</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($preferences): ?>
                            <?php foreach ($preferences as $pref): ?>
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <div>
                                        <strong><?php echo htmlspecialchars($pref['service_name']); ?></strong>
                                        <?php if ($pref['preferred_agent_name']): ?>
                                            <br><small class="text-muted">Prefers: <?php echo htmlspecialchars($pref['preferred_agent_name']); ?></small>
                                        <?php endif; ?>
                                    </div>
                                    <span class="badge bg-<?php echo $pref['interest_level'] === 'high' ? 'success' : ($pref['interest_level'] === 'medium' ? 'warning' : 'secondary'); ?>">
                                        <?php echo ucfirst($pref['interest_level']); ?>
                                    </span>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="text-muted">No service preferences set</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Bookings -->
            <div class="col-md-6 mb-4">
                <div class="card info-card">
                    <div class="card-header">
                        <h5><i class="fas fa-calendar"></i> Recent Bookings</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($bookings): ?>
                            <?php foreach (array_slice($bookings, 0, 5) as $booking): ?>
                                <div class="timeline-item">
                                    <div>
                                        <strong><?php echo htmlspecialchars($booking['service_name']); ?></strong>
                                        <span class="badge bg-<?php echo $booking['status'] === 'completed' ? 'success' : ($booking['status'] === 'pending' ? 'warning' : 'primary'); ?> ms-2">
                                            <?php echo ucfirst($booking['status']); ?>
                                        </span>
                                        <br>
                                        <small class="text-muted">
                                            Ref: <?php echo htmlspecialchars($booking['booking_reference']); ?>
                                            | $<?php echo formatCurrency($booking['total_amount']); ?>
                                            | <?php echo date('M j, Y', strtotime($booking['booking_date'])); ?>
                                        </small>
                                        <?php if ($booking['agent_name']): ?>
                                            <br><small class="text-muted">Agent: <?php echo htmlspecialchars($booking['agent_name']); ?></small>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="text-muted">No bookings yet</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Action Buttons -->
        <div class="row">
            <div class="col-12">
                <div class="card info-card">
                    <div class="card-body text-center">
                        <button class="btn btn-primary me-2" onclick="window.open('user-bookings.php?user_id=<?php echo $user_id; ?>', '_blank')">
                            <i class="fas fa-calendar"></i> View All Bookings
                        </button>
                        <button class="btn btn-info me-2" onclick="sendEmail(<?php echo $user_id; ?>)">
                            <i class="fas fa-envelope"></i> Send Email
                        </button>
                        <?php if ($user['status'] === 'pending'): ?>
                            <button class="btn btn-success me-2" onclick="approveUser(<?php echo $user_id; ?>)">
                                <i class="fas fa-check"></i> Approve User
                            </button>
                            <button class="btn btn-danger me-2" onclick="rejectUser(<?php echo $user_id; ?>)">
                                <i class="fas fa-times"></i> Reject User
                            </button>
                        <?php endif; ?>
                        <button class="btn btn-secondary" onclick="window.close()">
                            <i class="fas fa-times"></i> Close
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function sendEmail(userId) {
            // Open email compose window
            window.open('compose-email.php?user_id=' + userId, 'email_compose', 'width=800,height=600');
        }
        
        function approveUser(userId) {
            if (confirm('Approve this user?')) {
                window.location.href = 'users.php?approve=' + userId;
            }
        }
        
        function rejectUser(userId) {
            const reason = prompt('Enter rejection reason:');
            if (reason) {
                window.location.href = 'users.php?reject=' + userId + '&reason=' + encodeURIComponent(reason);
            }
        }
    </script>
</body>
</html>
