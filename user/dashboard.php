<?php
session_start();
require_once '../config/database.php';
require_once 'includes/user-helpers.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    // Get user info
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    
    // Get user's bookings with payment and chat info
    $stmt = $pdo->prepare("
        SELECT 
            sb.*,
            se.title as service_title,
            se.category as service_category,
            au.id as agent_id,
            CONCAT(au.first_name, ' ', au.last_name) as agent_name,
            au.profile_image as agent_image,
            p.payment_id,
            p.amount as payment_amount,
            p.payment_method,
            p.payment_status,
            p.paid_at,
            cr.room_id as chat_room_id,
            cr.status as chat_status
        FROM service_bookings sb
        LEFT JOIN services_enhanced se ON sb.service_id = se.id
        LEFT JOIN admin_users au ON sb.assigned_admin_id = au.id
        LEFT JOIN payments p ON sb.id = p.booking_id
        LEFT JOIN chat_rooms cr ON sb.id = cr.booking_id
        WHERE sb.user_id = ?
        ORDER BY sb.created_at DESC
    ");
    $stmt->execute([$user_id]);
    $bookings = $stmt->fetchAll();
    
    // Get statistics
    $total_bookings = count($bookings);
    $completed_bookings = count(array_filter($bookings, function($b) { return $b['status'] === 'completed'; }));
    $pending_bookings = count(array_filter($bookings, function($b) { return $b['status'] === 'waiting_approval'; }));
    $in_progress_bookings = count(array_filter($bookings, function($b) { return $b['status'] === 'in_progress'; }));
    $approved_bookings = count(array_filter($bookings, function($b) { return $b['status'] === 'approved'; }));
    $cancelled_bookings = count(array_filter($bookings, function($b) { return $b['status'] === 'cancelled'; }));
    
    // Calculate total amount spent
    $total_spent = array_sum(array_map(function($b) { 
        return $b['status'] === 'completed' ? ($b['total_amount'] ?: 0) : 0; 
    }, $bookings));
    
    // Count pending payments
    $pending_payments = count(array_filter($bookings, function($b) { 
        return in_array($b['status'], ['approved', 'payment_pending']) && 
               ($b['payment_status'] !== 'completed' || !$b['payment_status']); 
    }));
    
    // Count active chats
    $active_chats = count(array_filter($bookings, function($b) { 
        return $b['chat_room_id'] && $b['chat_status'] === 'active'; 
    }));
    
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}

function getStatusBadge($status) {
    switch ($status) {
        case 'waiting_approval':
            return '<span class="badge bg-warning"><i class="fas fa-clock"></i> Waiting Approval</span>';
        case 'approved':
            return '<span class="badge bg-info"><i class="fas fa-check"></i> Approved</span>';
        case 'completed':
            return '<span class="badge bg-success"><i class="fas fa-check-circle"></i> Completed</span>';
        case 'rejected':
            return '<span class="badge bg-danger"><i class="fas fa-times"></i> Rejected</span>';
        default:
            return '<span class="badge bg-secondary">' . ucfirst($status) . '</span>';
    }
}

function getPaymentStatusBadge($status) {
    switch ($status) {
        case 'completed':
            return '<span class="badge bg-success"><i class="fas fa-check-circle"></i> Paid</span>';
        case 'pending':
            return '<span class="badge bg-warning"><i class="fas fa-clock"></i> Pending</span>';
        case 'failed':
            return '<span class="badge bg-danger"><i class="fas fa-times"></i> Failed</span>';
        default:
            return '<span class="badge bg-secondary">No Payment</span>';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Dashboard - ConnectPro Agency</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../css/mobile-responsive.css" rel="stylesheet">
    <style>
        .dashboard-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }
        
        .dashboard-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .stat-card-success {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
        }
        
        .stat-card-warning {
            background: linear-gradient(135deg, #ffc107 0%, #fd7e14 100%);
            color: white;
        }
        
        .stat-card-info {
            background: linear-gradient(135deg, #17a2b8 0%, #6f42c1 100%);
            color: white;
        }
        
        .stat-card-primary {
            background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
            color: white;
        }
        
        .stat-card-danger {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            color: white;
        }
        
        .stat-card-chat {
            background: linear-gradient(135deg, #6f42c1 0%, #e83e8c 100%);
            color: white;
        }
        
        .stat-card-secondary {
            background: linear-gradient(135deg, #6c757d 0%, #495057 100%);
            color: white;
        }
        
        .stat-card .card-body,
        .stat-card-success .card-body,
        .stat-card-warning .card-body,
        .stat-card-info .card-body,
        .stat-card-primary .card-body,
        .stat-card-danger .card-body,
        .stat-card-chat .card-body,
        .stat-card-secondary .card-body {
            text-align: center;
            padding: 2rem;
        }
        
        .stat-icon {
            font-size: 2rem;
            margin-bottom: 1rem;
            opacity: 0.8;
        }
        
        .user-avatar {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 1.2rem;
            margin: 0 auto;
        }
        
        .booking-timeline {
            position: relative;
            padding-left: 2rem;
        }
        
        .timeline-item {
            position: relative;
            padding-bottom: 2rem;
            border-left: 2px solid #e9ecef;
        }
        
        .timeline-item:last-child {
            border-left: none;
        }
        
        .timeline-item:before {
            content: '';
            position: absolute;
            left: -6px;
            top: 8px;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: #667eea;
        }
        
        .timeline-item .card {
            margin-left: 20px;
            border: none;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .quick-actions {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }
        
        .quick-action {
            flex: 1;
            min-width: 150px;
            text-align: center;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 10px;
            text-decoration: none;
            color: #333;
            transition: all 0.3s ease;
        }
        
        .quick-action:hover {
            background: #667eea;
            color: white;
            transform: translateY(-2px);
        }
        
        .quick-action i {
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
        }
        
        /* Mobile responsive improvements */
        @media (max-width: 768px) {
            .dashboard-header {
                text-align: center;
            }
            
            .dashboard-header .btn {
                width: 100%;
                margin-top: 1rem;
            }
            
            .stat-card .card-body {
                padding: 1rem;
            }
            
            .stat-icon {
                font-size: 1.5rem;
            }
            
            .booking-timeline {
                padding-left: 1rem;
            }
            
            .timeline-item .card {
                margin-left: 10px;
            }
            
            .quick-actions {
                flex-direction: column;
            }
            
            .quick-action {
                min-width: 100%;
            }
        }
        
        @media (max-width: 480px) {
            .dashboard-header h2 {
                font-size: 1.5rem;
            }
            
            .stat-card .card-body {
                padding: 0.75rem;
            }
            
            .stat-icon {
                font-size: 1.25rem;
            }
            
            .booking-timeline {
                padding-left: 0.5rem;
            }
            
            .timeline-item .card {
                margin-left: 5px;
            }
        }
    </style>
        }
        
        .booking-timeline::before {
            content: '';
            position: absolute;
            left: 15px;
            top: 0;
            bottom: 0;
            width: 2px;
            background: #e9ecef;
        }
        
        .timeline-item {
            position: relative;
            margin-bottom: 2rem;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 1.5rem;
        }
        
        .timeline-item::before {
            content: '';
            position: absolute;
            left: -2.3rem;
            top: 1.5rem;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: #667eea;
            border: 3px solid white;
            box-shadow: 0 0 0 3px #e9ecef;
        }
        
        .welcome-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
        }
        
        .action-buttons .btn {
            margin: 0.25rem;
        }
        
        .logout-btn {
            color: #dc3545 !important;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .logout-btn:hover {
            color: #c82333 !important;
            background-color: rgba(220, 53, 69, 0.1);
            border-radius: 5px;
        }
        
        .logout-btn i {
            margin-right: 8px;
        }

        /* Chat Widget Styles */
        .chat-notification-widget {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 1000;
        }
        
        .chat-fab {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 60px;
            height: 60px;
            background: #667eea;
            color: white;
            border-radius: 50%;
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
            border: none;
            outline: none;
            cursor: pointer;
            transition: background 0.3s ease;
        }
        
        .chat-fab:hover {
            background: #5a6fca;
        }
        
        .notification-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background: #dc3545;
            color: white;
            border-radius: 50%;
            padding: 2px 6px;
            font-size: 0.75rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .pulse-animation {
            animation: pulse 1s infinite;
        }
        
        @keyframes pulse {
            0% {
                transform: scale(1);
            }
            50% {
                transform: scale(1.1);
            }
            100% {
                transform: scale(1);
            }
        }
    </style>
</head>
<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="../index.php">
                <i class="fas fa-handshake"></i> ConnectPro Agency
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="../index.php">
                    <i class="fas fa-home"></i> Home
                </a>
                <a class="nav-link" href="book-service.php">
                    <i class="fas fa-plus"></i> Book Service
                </a>
                <a class="nav-link" href="chat.php">
                    <i class="fas fa-comments"></i> Chat
                    <span class="badge bg-danger ms-1" id="chat-unread-badge" style="display: none;">0</span>
                </a>
                <a class="nav-link logout-btn" href="logout.php" onclick="return confirmUserLogout()">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <!-- Welcome Section -->
        <div class="welcome-section bg-white rounded-3 p-4 mb-4 shadow-sm">
            <div class="row align-items-center">
                <div class="col-lg-8 col-md-12">
                    <h2><i class="fas fa-user-circle"></i> Welcome back, <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>!</h2>
                    <p class="mb-0">Manage your service bookings, payments, and chat with our agents.</p>
                </div>
                <div class="col-lg-4 col-md-12">
                    <a href="book-service.php" class="btn btn-light btn-lg w-100 mt-3 mt-lg-0">
                        <i class="fas fa-plus"></i> Book New Service
                    </a>
                </div>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-lg-3 col-md-6 col-sm-6 mb-3">
                <div class="card dashboard-card stat-card">
                    <div class="card-body text-center">
                        <div class="stat-icon">
                            <i class="fas fa-calendar-check"></i>
                        </div>
                        <div class="stat-number"><?php echo $total_bookings; ?></div>
                        <div>Total Bookings</div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 col-sm-6 mb-3">
                <div class="card dashboard-card stat-card-success">
                    <div class="card-body text-center">
                        <div class="stat-icon">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="stat-number"><?php echo $completed_bookings; ?></div>
                        <div>Completed</div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 col-sm-6 mb-3">
                <div class="card dashboard-card stat-card-warning">
                    <div class="card-body text-center">
                        <div class="stat-icon">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="stat-number"><?php echo $pending_bookings; ?></div>
                        <div>Waiting Approval</div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 col-sm-6 mb-3">
                <div class="card dashboard-card stat-card-info">
                    <div class="card-body text-center">
                        <div class="stat-icon">
                            <i class="fas fa-info-circle"></i>
                        </div>
                        <div class="stat-number"><?php echo $approved_bookings; ?></div>
                        <div>Approved</div>
                    </div>
                </div>
            </div>
        </div>
                            <i class="fas fa-spinner"></i>
                        </div>
                        <div class="stat-number"><?php echo $in_progress_bookings; ?></div>
                        <div>In Progress</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Additional Stats Row -->
        <div class="row mb-4">
            <div class="col-md-3 mb-3">
                <div class="card dashboard-card stat-card-primary">
                    <div class="card-body text-center">
                        <div class="stat-icon">
                            <i class="fas fa-dollar-sign"></i>
                        </div>
                        <div class="stat-number">$<?php echo formatCurrency($total_spent); ?></div>
                        <div>Total Spent</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card dashboard-card stat-card-danger">
                    <div class="card-body text-center">
                        <div class="stat-icon">
                            <i class="fas fa-credit-card"></i>
                        </div>
                        <div class="stat-number"><?php echo $pending_payments; ?></div>
                        <div>Pending Payments</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card dashboard-card stat-card-chat">
                    <div class="card-body text-center">
                        <div class="stat-icon">
                            <i class="fas fa-comments"></i>
                        </div>
                        <div class="stat-number"><?php echo $active_chats; ?></div>
                        <div>Active Chats</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card dashboard-card stat-card-secondary">
                    <div class="card-body text-center">
                        <div class="stat-icon">
                            <i class="fas fa-thumbs-up"></i>
                        </div>
                        <div class="stat-number"><?php echo $approved_bookings; ?></div>
                        <div>Approved</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Bookings Timeline -->
            <div class="col-lg-8">
                <div class="card dashboard-card">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">
                            <i class="fas fa-history"></i> My Bookings
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($bookings)): ?>
                            <div class="text-center py-5">
                                <i class="fas fa-calendar-plus fa-3x text-muted mb-3"></i>
                                <h5>No Bookings Yet</h5>
                                <p class="text-muted">Start by booking your first service!</p>
                                <a href="../book-service.php" class="btn btn-primary">
                                    <i class="fas fa-plus"></i> Book Service
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="booking-timeline">
                                <?php foreach ($bookings as $booking): ?>
                                    <div class="timeline-item">
                                        <div class="row">
                                            <div class="col-md-8">
                                                <h6 class="mb-1">
                                                    <i class="fas fa-briefcase"></i> 
                                                    <?php echo htmlspecialchars($booking['service_title'] ?: 'Service Request'); ?>
                                                </h6>
                                                <p class="text-muted mb-2">
                                                    Booking #<?php echo $booking['booking_reference']; ?> • 
                                                    <?php echo date('M j, Y', strtotime($booking['created_at'])); ?>
                                                    <?php if ($booking['service_category']): ?>
                                                        • <?php echo htmlspecialchars($booking['service_category']); ?>
                                                    <?php endif; ?>
                                                </p>
                                                
                                                <div class="mb-2">
                                                    <?php echo getStatusBadge($booking['status']); ?>
                                                    <?php if ($booking['payment_status']): ?>
                                                        <?php echo getPaymentStatusBadge($booking['payment_status']); ?>
                                                    <?php endif; ?>
                                                    <?php if ($booking['urgency_level'] === 'urgent'): ?>
                                                        <span class="badge bg-danger">
                                                            <i class="fas fa-exclamation"></i> Urgent
                                                        </span>
                                                    <?php elseif ($booking['urgency_level'] === 'high'): ?>
                                                        <span class="badge bg-warning">
                                                            <i class="fas fa-clock"></i> High Priority
                                                        </span>
                                                    <?php endif; ?>
                                                </div>

                                                <?php if (!empty($booking['service_details'])): ?>
                                                    <p class="small text-muted mb-2">
                                                        <?php echo htmlspecialchars(substr($booking['service_details'], 0, 100)); ?>
                                                        <?php if (strlen($booking['service_details']) > 100): ?>...<?php endif; ?>
                                                    </p>
                                                <?php endif; ?>

                                                <?php if ($booking['agent_name']): ?>
                                                    <p class="small mb-2">
                                                        <i class="fas fa-user-tie"></i> 
                                                        <strong>Agent:</strong> <?php echo htmlspecialchars($booking['agent_name']); ?>
                                                    </p>
                                                <?php endif; ?>

                                                <?php if ($booking['total_amount']): ?>
                                                    <p class="mb-0">
                                                        <strong>Amount:</strong> $<?php echo formatCurrency($booking['total_amount']); ?>
                                                        <?php if ($booking['payment_method']): ?>
                                                            via <?php echo ucfirst($booking['payment_method']); ?>
                                                        <?php endif; ?>
                                                    </p>
                                                <?php endif; ?>
                                            </div>
                                            <div class="col-md-4 text-end">
                                                <div class="action-buttons">
                                                    <?php if ($booking['status'] === 'approved' && $booking['payment_status'] !== 'completed'): ?>
                                                        <a href="../payment.php?booking=<?php echo $booking['id']; ?>" 
                                                           class="btn btn-success btn-sm">
                                                            <i class="fas fa-credit-card"></i> Pay Now
                                                        </a>
                                                    <?php endif; ?>
                                                    
                                                    <?php if ($booking['chat_room_id']): ?>
                                                        <a href="chat.php?room=<?php echo $booking['chat_room_id']; ?>" 
                                                           class="btn btn-info btn-sm">
                                                            <i class="fas fa-comments"></i> Chat
                                                        </a>
                                                    <?php elseif ($booking['agent_id'] && in_array($booking['status'], ['approved', 'in_progress', 'completed'])): ?>
                                                        <button onclick="createChatRoom(<?php echo $booking['id']; ?>, <?php echo $booking['agent_id']; ?>)" 
                                                                class="btn btn-outline-info btn-sm">
                                                            <i class="fas fa-comments"></i> Start Chat
                                                        </button>
                                                    <?php endif; ?>
                                                    
                                                    <?php if ($booking['chat_room_id'] && $booking['payment_status'] === 'completed'): ?>
                                                        <a href="../chat.php?room=<?php echo $booking['chat_room_id']; ?>" 
                                                           class="btn btn-primary btn-sm">
                                                            <i class="fas fa-comments"></i> Chat
                                                        </a>
                                                    <?php endif; ?>
                                                    
                                                    <button class="btn btn-outline-secondary btn-sm" 
                                                            onclick="toggleDetails(<?php echo $booking['booking_id']; ?>)">
                                                        <i class="fas fa-eye"></i> Details
                                                    </button>
                                                </div>
                                                
                                                <!-- Hidden Details -->
                                                <div id="details-<?php echo $booking['booking_id']; ?>" 
                                                     class="mt-3" style="display: none;">
                                                    <div class="card bg-light">
                                                        <div class="card-body p-3">
                                                            <h6>Booking Details</h6>
                                                            <p><strong>Email:</strong> <?php echo htmlspecialchars($booking['email']); ?></p>
                                                            <p><strong>Phone:</strong> <?php echo htmlspecialchars($booking['phone']); ?></p>
                                                            <?php if ($booking['description']): ?>
                                                                <p><strong>Beschreibung:</strong><br>
                                                                <?php echo nl2br(htmlspecialchars($booking['description'])); ?></p>
                                                            <?php endif; ?>
                                                            <?php if ($booking['approved_at']): ?>
                                                                <p><strong>Approved:</strong> <?php echo date('M j, Y g:i A', strtotime($booking['approved_at'])); ?></p>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Quick Actions & Info -->
            <div class="col-lg-4">
                <!-- Quick Actions -->
                <div class="card dashboard-card mb-4">
                    <div class="card-header bg-white">
                        <h6 class="mb-0">
                            <i class="fas fa-bolt"></i> Quick Actions
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <a href="../book-service.php" class="btn btn-primary">
                                <i class="fas fa-plus"></i> Book New Service
                            </a>
                            
                            <?php if ($pending_payments > 0): ?>
                                <a href="#" onclick="scrollToPendingPayments()" class="btn btn-warning">
                                    <i class="fas fa-credit-card"></i> Complete Payments (<?php echo $pending_payments; ?>)
                                </a>
                            <?php endif; ?>
                            
                            <?php if ($active_chats > 0): ?>
                                <a href="#" onclick="scrollToActiveChats()" class="btn btn-info">
                                    <i class="fas fa-comments"></i> View Chats (<?php echo $active_chats; ?>)
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Account Info -->
                <div class="card dashboard-card">
                    <div class="card-header bg-white">
                        <h6 class="mb-0">
                            <i class="fas fa-user"></i> Account Information
                        </h6>
                    </div>
                    <div class="card-body">
                        <p><strong>Name:</strong> <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></p>
                        <p><strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?></p>
                        <p><strong>Phone:</strong> <?php echo htmlspecialchars($user['phone']); ?></p>
                        <p><strong>Member Since:</strong> <?php echo date('M Y', strtotime($user['created_at'])); ?></p>
                        
                        <hr>
                        
                        <div class="d-grid">
                            <a href="profile.php" class="btn btn-outline-primary btn-sm">
                                <i class="fas fa-edit"></i> Update Profile
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Summary Section -->
        <div class="row mb-4">
            <div class="col-md-8">
                <div class="card dashboard-card">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">
                            <i class="fas fa-chart-line"></i> Your Activity Summary
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h6>Recent Activity</h6>
                                <ul class="list-unstyled">
                                    <?php if ($total_bookings > 0): ?>
                                        <li><i class="fas fa-calendar-plus text-primary"></i> 
                                            Last booking: <?php echo date('M j, Y', strtotime($bookings[0]['created_at'])); ?>
                                        </li>
                                        <?php if ($completed_bookings > 0): ?>
                                            <li><i class="fas fa-check-circle text-success"></i> 
                                                <?php echo $completed_bookings; ?> service<?php echo $completed_bookings > 1 ? 's' : ''; ?> completed
                                            </li>
                                        <?php endif; ?>
                                        <?php if ($pending_bookings > 0): ?>
                                            <li><i class="fas fa-clock text-warning"></i> 
                                                <?php echo $pending_bookings; ?> booking<?php echo $pending_bookings > 1 ? 's' : ''; ?> awaiting approval
                                            </li>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <li><i class="fas fa-info-circle text-muted"></i> No bookings yet</li>
                                    <?php endif; ?>
                                </ul>
                            </div>
                            <div class="col-md-6">
                                <h6>Quick Actions</h6>
                                <div class="d-grid gap-2">
                                    <a href="book-service.php" class="btn btn-primary btn-sm">
                                        <i class="fas fa-plus"></i> Book New Service
                                    </a>
                                    <?php if ($active_chats > 0): ?>
                                        <a href="chat.php" class="btn btn-info btn-sm">
                                            <i class="fas fa-comments"></i> Open Chat (<?php echo $active_chats; ?>)
                                        </a>
                                    <?php endif; ?>
                                    <?php if ($pending_payments > 0): ?>
                                        <button onclick="scrollToPendingPayments()" class="btn btn-success btn-sm">
                                            <i class="fas fa-credit-card"></i> Pay Pending (<?php echo $pending_payments; ?>)
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card dashboard-card">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">
                            <i class="fas fa-user-circle"></i> Your Profile
                        </h5>
                    </div>
                    <div class="card-body text-center">
                        <div class="mb-3">
                            <div class="user-avatar">
                                <?php echo strtoupper(substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1)); ?>
                            </div>
                        </div>
                        <h6><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h6>
                        <p class="text-muted small mb-3"><?php echo htmlspecialchars($user['email']); ?></p>
                        <a href="profile.php" class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-edit"></i> Edit Profile
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Chat Widget -->
    <div class="chat-notification-widget">
        <button class="chat-fab" onclick="openChat()" title="Open Chat">
            <i class="fas fa-comments"></i>
            <span class="notification-badge" id="chat-notification-badge" style="display: none;">0</span>
        </button>
    </div>

    <link href="../css/chat-widget.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Chat widget functionality
        function openChat() {
            window.location.href = 'chat.php';
        }
        
        // Check for unread messages
        function checkUnreadMessages() {
            fetch('../api/chat.php?action=get_unread_count')
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.unread_count > 0) {
                        document.getElementById('chat-notification-badge').textContent = data.unread_count;
                        document.getElementById('chat-notification-badge').style.display = 'flex';
                        document.querySelector('.chat-fab').classList.add('pulse-animation');
                        
                        // Update navigation badge
                        document.getElementById('chat-unread-badge').textContent = data.unread_count;
                        document.getElementById('chat-unread-badge').style.display = 'inline';
                    } else {
                        document.getElementById('chat-notification-badge').style.display = 'none';
                        document.querySelector('.chat-fab').classList.remove('pulse-animation');
                        
                        // Hide navigation badge
                        document.getElementById('chat-unread-badge').style.display = 'none';
                    }
                })
                .catch(error => console.log('Chat check failed:', error));
        }
        
        // Check for messages every 30 seconds
        setInterval(checkUnreadMessages, 30000);
        checkUnreadMessages(); // Initial check
        
        function toggleDetails(bookingId) {
            const details = document.getElementById('details-' + bookingId);
            if (details.style.display === 'none') {
                details.style.display = 'block';
            } else {
                details.style.display = 'none';
            }
        }

        function scrollToPendingPayments() {
            // Find first pending payment and scroll to it
            const timelineItems = document.querySelectorAll('.timeline-item');
            timelineItems.forEach(item => {
                if (item.innerHTML.includes('Pay Now')) {
                    item.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    // Highlight the item briefly
                    item.style.border = '2px solid #28a745';
                    setTimeout(() => {
                        item.style.border = '';
                    }, 3000);
                    return false;
                }
            });
        }

        function scrollToActiveChats() {
            // Find first active chat and scroll to it
            const timelineItems = document.querySelectorAll('.timeline-item');
            timelineItems.forEach(item => {
                if (item.innerHTML.includes('Chat')) {
                    item.scrollIntoView({ behavior: 'smooth' });
                    return false;
                }
            });
        }
        
        // User logout confirmation
        function confirmUserLogout() {
            return confirm('Are you sure you want to logout from your user account?\n\nYou will be redirected to the home page.');
        }
        
        // Create chat room function
        function createChatRoom(bookingId, agentId) {
            const data = {
                action: 'create_room',
                booking_id: bookingId,
                agent_id: agentId
            };
            
            fetch('../api/chat.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Redirect to chat page
                    window.location.href = 'chat.php';
                } else {
                    alert('Error creating chat room: ' + (data.error || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error creating chat room');
            });
        }
    </script>
    
    <style>
        .logout-btn {
            color: #dc3545 !important;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .logout-btn:hover {
            color: #c82333 !important;
            background-color: rgba(220, 53, 69, 0.1);
            border-radius: 5px;
        }
        
        .logout-btn i {
            margin-right: 8px;
        }
    </style>
</body>
</html>
