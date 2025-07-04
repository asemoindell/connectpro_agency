<?php
session_start();
require_once '../config/database.php';

// Check if agent is logged in
if (!isset($_SESSION['agent_logged_in'])) {
    header('Location: login.php');
    exit();
}

$agent_id = $_SESSION['agent_id'];

try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    // Get agent info with updated profile data
    $stmt = $pdo->prepare("SELECT * FROM admin_users WHERE id = ?");
    $stmt->execute([$agent_id]);
    $agent = $stmt->fetch();
    
    if (!$agent) {
        session_destroy();
        header('Location: login.php');
        exit();
    }
    
    // Get agent's bookings with enhanced data
    $stmt = $pdo->prepare("
        SELECT 
            sb.*,
            se.title as service_title,
            se.category as service_category,
            u.first_name as client_first_name,
            u.last_name as client_last_name,
            u.email as client_email_db,
            u.phone as client_phone_db
        FROM service_bookings sb
        LEFT JOIN services se ON sb.service_id = se.id
        LEFT JOIN users u ON sb.user_id = u.id
        WHERE sb.assigned_admin_id = ?
        ORDER BY sb.created_at DESC
    ");
    $stmt->execute([$agent_id]);
    $bookings = $stmt->fetchAll();
    
    // Calculate statistics
    $total_bookings = count($bookings);
    $pending_bookings = count(array_filter($bookings, function($b) { return $b['status'] === 'waiting_approval'; }));
    $completed_bookings = count(array_filter($bookings, function($b) { return $b['status'] === 'completed'; }));
    $in_progress_bookings = count(array_filter($bookings, function($b) { return $b['status'] === 'in_progress'; }));
    
    // Calculate revenue and earnings
    $total_revenue = array_sum(array_map(function($b) { 
        return $b['status'] === 'completed' ? $b['total_amount'] : 0; 
    }, $bookings));
    
    // Calculate agent fees/commission earned
    $agent_fees_earned = array_sum(array_map(function($b) { 
        return $b['status'] === 'completed' ? $b['agent_fee'] : 0; 
    }, $bookings));
    
    // Calculate pending earnings (from approved/in-progress bookings)
    $pending_earnings = array_sum(array_map(function($b) { 
        return ($b['status'] === 'approved' || $b['status'] === 'in_progress') ? $b['agent_fee'] : 0; 
    }, $bookings));
    
    // Calculate total potential earnings
    $total_potential_earnings = array_sum(array_map(function($b) { 
        return $b['agent_fee'] ?? 0; 
    }, $bookings));
    
    // Get monthly earnings for current month
    $current_month_earnings = array_sum(array_map(function($b) { 
        return ($b['status'] === 'completed' && date('Y-m', strtotime($b['completed_at'])) === date('Y-m')) ? $b['agent_fee'] : 0; 
    }, $bookings));
    
    // Get recent activity (last 10 bookings)
    $recent_bookings = array_slice($bookings, 0, 10);
    
    // Get unique assigned users with their latest booking info
    $stmt = $pdo->prepare("
        SELECT 
            u.id as user_id,
            u.first_name,
            u.last_name,
            u.email,
            u.phone,
            u.created_at as user_joined,
            COUNT(sb.id) as total_bookings,
            SUM(CASE WHEN sb.status = 'completed' THEN sb.total_amount ELSE 0 END) as total_spent,
            MAX(sb.created_at) as last_booking_date,
            GROUP_CONCAT(DISTINCT se.title ORDER BY sb.created_at DESC SEPARATOR ', ') as services_booked,
            COUNT(CASE WHEN sb.status = 'waiting_approval' THEN 1 END) as pending_bookings,
            COUNT(CASE WHEN sb.status = 'in_progress' THEN 1 END) as active_bookings
        FROM users u
        INNER JOIN service_bookings sb ON u.id = sb.user_id
        LEFT JOIN services se ON sb.service_id = se.id
        WHERE sb.assigned_admin_id = ?
        GROUP BY u.id, u.first_name, u.last_name, u.email, u.phone, u.created_at
        ORDER BY MAX(sb.created_at) DESC
    ");
    $stmt->execute([$agent_id]);
    $assigned_users = $stmt->fetchAll();
    
    // Count unique users
    $total_assigned_users = count($assigned_users);
    
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}

function getStatusBadge($status) {
    switch ($status) {
        case 'waiting_approval':
            return '<span class="badge bg-warning"><i class="fas fa-clock"></i> Waiting Approval</span>';
        case 'approved':
            return '<span class="badge bg-info"><i class="fas fa-check"></i> Approved</span>';
        case 'in_progress':
            return '<span class="badge bg-primary"><i class="fas fa-spinner"></i> In Progress</span>';
        case 'completed':
            return '<span class="badge bg-success"><i class="fas fa-check-circle"></i> Completed</span>';
        case 'cancelled':
            return '<span class="badge bg-danger"><i class="fas fa-times"></i> Cancelled</span>';
        default:
            return '<span class="badge bg-secondary">' . ucfirst($status) . '</span>';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agent Dashboard - ConnectPro Agency</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Inter', sans-serif;
        }
        
        .navbar-brand {
            font-weight: 600;
        }
        
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
            text-align: center;
            padding: 2rem 1rem;
        }
        
        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }
        
        .welcome-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
        }
        
        .agent-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid white;
            box-shadow: 0 0 0 3px #e9ecef;
        }
        
        .agent-initials {
            width: 80px;
            height: 80px;
            background: rgba(255,255,255,0.2);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            font-weight: bold;
            border: 3px solid white;
            box-shadow: 0 0 0 3px #e9ecef;
        }
        
        .earnings-stat {
            text-align: center;
            padding: 1rem;
            border-radius: 10px;
            background: #f8f9fa;
            margin-bottom: 1rem;
        }
        
        .earnings-label {
            font-size: 0.9rem;
            color: #6c757d;
            margin-bottom: 0.5rem;
        }
        
        .earnings-value {
            font-size: 1.8rem;
            font-weight: bold;
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
        
        .table-custom {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .table-custom th {
            background: #f8f9fa;
            border: none;
            font-weight: 600;
            padding: 1rem;
        }
        
        .table-custom td {
            border: none;
            padding: 1rem;
            vertical-align: middle;
        }
        
        .booking-actions .btn {
            margin: 0.125rem;
        }

        /* Chat Widget Styles */
        .chat-notification-widget {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 1000;
        }
        
        .chat-fab {
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 50%;
            width: 60px;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
            transition: background-color 0.3s ease;
        }
        
        .chat-fab:hover {
            background-color: #0056b3;
        }
        
        .notification-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background-color: #dc3545;
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: bold;
        }

        .pulse-animation {
            animation: pulse 1.5s infinite;
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
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="../index.php">
                <i class="fas fa-handshake"></i> ConnectPro Agency
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="profile.php">
                    <i class="fas fa-user-edit"></i> My Profile
                </a>
                <a class="nav-link" href="bookings.php">
                    <i class="fas fa-calendar-check"></i> My Bookings
                </a>
                <a class="nav-link" href="chat.php">
                    <i class="fas fa-comments"></i> Chat
                    <span class="badge bg-danger ms-1" id="chat-unread-badge" style="display: none;">0</span>
                </a>
                <a class="nav-link logout-btn" href="logout.php" onclick="return confirm('Are you sure you want to logout?')">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <!-- Welcome Section -->
        <div class="welcome-section">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <div class="d-flex align-items-center">
                        <div class="me-3">
                            <?php if ($agent['profile_image']): ?>
                                <img src="../<?php echo htmlspecialchars($agent['profile_image']); ?>" 
                                     alt="Profile" class="agent-avatar">
                            <?php else: ?>
                                <div class="agent-initials">
                                    <?php echo strtoupper(substr($agent['first_name'], 0, 1) . substr($agent['last_name'], 0, 1)); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div>
                            <h2><i class="fas fa-user-tie"></i> Welcome back, <?php echo htmlspecialchars($agent['first_name']); ?>!</h2>
                            <p class="mb-1">@<?php echo htmlspecialchars($agent['username']); ?> • <?php echo ucfirst(str_replace('-', ' ', $agent['role'])); ?></p>
                            <?php if ($agent['specialization']): ?>
                                <p class="mb-0"><i class="fas fa-star"></i> <?php echo htmlspecialchars($agent['specialization']); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 text-end">
                    <div class="availability-status">
                        <span class="badge bg-<?php echo $agent['is_available'] ? 'success' : 'warning'; ?> fs-6 px-3 py-2">
                            <i class="fas fa-circle"></i> 
                            <?php echo $agent['is_available'] ? 'Available' : 'Busy'; ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-lg-2 col-md-4 mb-3">
                <div class="card dashboard-card stat-card">
                    <div class="card-body">
                        <div class="stat-number"><?php echo $total_assigned_users; ?></div>
                        <div>My Clients</div>
                    </div>
                </div>
            </div>
            <div class="col-lg-2 col-md-4 mb-3">
                <div class="card dashboard-card stat-card">
                    <div class="card-body">
                        <div class="stat-number"><?php echo $total_bookings; ?></div>
                        <div>Total Bookings</div>
                    </div>
                </div>
            </div>
            <div class="col-lg-2 col-md-4 mb-3">
                <div class="card dashboard-card stat-card">
                    <div class="card-body">
                        <div class="stat-number"><?php echo $pending_bookings; ?></div>
                        <div>Pending Approval</div>
                    </div>
                </div>
            </div>
            <div class="col-lg-2 col-md-4 mb-3">
                <div class="card dashboard-card stat-card">
                    <div class="card-body">
                        <div class="stat-number"><?php echo $in_progress_bookings; ?></div>
                        <div>In Progress</div>
                    </div>
                </div>
            </div>
            <div class="col-lg-2 col-md-4 mb-3">
                <div class="card dashboard-card stat-card">
                    <div class="card-body">
                        <div class="stat-number"><?php echo $completed_bookings; ?></div>
                        <div>Completed</div>
                    </div>
                </div>
            </div>
            <div class="col-lg-2 col-md-4 mb-3">
                <div class="card dashboard-card stat-card">
                    <div class="card-body">
                        <div class="stat-number">$<?php echo number_format($total_revenue, 0); ?></div>
                        <div>Total Revenue</div>
                    </div>
                </div>
            </div>
            <div class="col-lg-2 col-md-4 mb-3">
                <div class="card dashboard-card stat-card">
                    <div class="card-body">
                        <div class="stat-number text-success">$<?php echo number_format($agent_fees_earned, 0); ?></div>
                        <div>Earnings Paid</div>
                    </div>
                </div>
            </div>
            <div class="col-lg-2 col-md-4 mb-3">
                <div class="card dashboard-card stat-card">
                    <div class="card-body">
                        <div class="stat-number text-warning">$<?php echo number_format($pending_earnings, 0); ?></div>
                        <div>Pending Earnings</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Earnings Summary Section -->
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card dashboard-card">
                    <div class="card-header bg-transparent">
                        <h5 class="mb-0"><i class="fas fa-dollar-sign"></i> Earnings Summary</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="earnings-stat">
                                    <div class="earnings-label">This Month</div>
                                    <div class="earnings-value text-success">$<?php echo number_format($current_month_earnings, 2); ?></div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="earnings-stat">
                                    <div class="earnings-label">Total Earned</div>
                                    <div class="earnings-value text-success">$<?php echo number_format($agent_fees_earned, 2); ?></div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="earnings-stat">
                                    <div class="earnings-label">Pending</div>
                                    <div class="earnings-value text-warning">$<?php echo number_format($pending_earnings, 2); ?></div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="earnings-stat">
                                    <div class="earnings-label">Total Potential</div>
                                    <div class="earnings-value text-info">$<?php echo number_format($total_potential_earnings, 2); ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- My Assigned Clients Section -->
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card dashboard-card">
                    <div class="card-header bg-transparent">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0"><i class="fas fa-users"></i> My Assigned Clients</h5>
                            <span class="badge bg-primary"><?php echo $total_assigned_users; ?> Client<?php echo $total_assigned_users != 1 ? 's' : ''; ?></span>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <?php if (empty($assigned_users)): ?>
                            <div class="text-center py-5">
                                <i class="fas fa-user-friends fa-3x text-muted mb-3"></i>
                                <h5 class="text-muted">No Assigned Clients Yet</h5>
                                <p class="text-muted">You don't have any clients assigned to you yet. Clients will appear here once they book your services.</p>
                            </div>
                        <?php else: ?>
                            <!-- Search/Filter Bar -->
                            <div class="p-3 bg-light border-bottom">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fas fa-search"></i></span>
                                            <input type="text" class="form-control" id="clientSearchInput" placeholder="Search clients by name, email, or phone...">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <select class="form-select" id="clientSortSelect">
                                            <option value="name">Sort by Name</option>
                                            <option value="spent">Sort by Total Spent</option>
                                            <option value="bookings">Sort by Bookings</option>
                                            <option value="recent">Sort by Recent</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <button class="btn btn-outline-secondary" onclick="clearClientFilters()">
                                            <i class="fas fa-times"></i> Clear
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-custom mb-0" id="clientsTable">
                                    <thead>
                                        <tr>
                                            <th>Client</th>
                                            <th>Contact</th>
                                            <th>Member Since</th>
                                            <th>Total Bookings</th>
                                            <th>Total Spent</th>
                                            <th>Last Booking</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($assigned_users as $user): ?>
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="user-avatar me-2">
                                                            <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" 
                                                                 style="width: 40px; height: 40px; font-size: 14px; font-weight: bold;">
                                                                <?php echo strtoupper(substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1)); ?>
                                                            </div>
                                                        </div>
                                                        <div>
                                                            <strong><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></strong>
                                                            <br>
                                                            <small class="text-muted">Client ID: #<?php echo $user['user_id']; ?></small>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div>
                                                        <small><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($user['email']); ?></small>
                                                        <br>
                                                        <?php if ($user['phone']): ?>
                                                            <small><i class="fas fa-phone"></i> <?php echo htmlspecialchars($user['phone']); ?></small>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                                <td>
                                                    <small><?php echo date('M d, Y', strtotime($user['user_joined'])); ?></small>
                                                </td>
                                                <td>
                                                    <div class="text-center">
                                                        <span class="badge bg-info"><?php echo $user['total_bookings']; ?></span>
                                                    </div>
                                                </td>
                                                <td>
                                                    <strong>$<?php echo number_format($user['total_spent'], 2); ?></strong>
                                                </td>
                                                <td>
                                                    <small><?php echo date('M d, Y', strtotime($user['last_booking_date'])); ?></small>
                                                </td>
                                                <td>
                                                    <?php if ($user['pending_bookings'] > 0): ?>
                                                        <span class="badge bg-warning"><i class="fas fa-clock"></i> <?php echo $user['pending_bookings']; ?> Pending</span>
                                                    <?php elseif ($user['active_bookings'] > 0): ?>
                                                        <span class="badge bg-primary"><i class="fas fa-spinner"></i> <?php echo $user['active_bookings']; ?> Active</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-success"><i class="fas fa-check-circle"></i> Up to Date</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <div class="d-flex gap-1">
                                                        <a href="chat.php?user_id=<?php echo $user['user_id']; ?>" 
                                                           class="btn btn-sm btn-outline-info" title="Start Chat">
                                                            <i class="fas fa-comments"></i>
                                                        </a>
                                                        <button class="btn btn-sm btn-outline-secondary" 
                                                                onclick="showClientDetails(<?php echo $user['user_id']; ?>)" 
                                                                title="View Details">
                                                            <i class="fas fa-info-circle"></i>
                                                        </button>
                                                        <div class="dropdown">
                                                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" 
                                                                    type="button" 
                                                                    id="clientActions<?php echo $user['user_id']; ?>" 
                                                                    data-bs-toggle="dropdown" 
                                                                    aria-expanded="false"
                                                                    title="More Actions">
                                                                <i class="fas fa-ellipsis-v"></i>
                                                            </button>
                                                            <ul class="dropdown-menu" aria-labelledby="clientActions<?php echo $user['user_id']; ?>">
                                                                <li>
                                                                    <a class="dropdown-item" href="client-bookings.php?user_id=<?php echo $user['user_id']; ?>">
                                                                        <i class="fas fa-calendar-alt me-2"></i>View Bookings
                                                                    </a>
                                                                </li>
                                                                <li>
                                                                    <a class="dropdown-item" href="chat.php?user_id=<?php echo $user['user_id']; ?>">
                                                                        <i class="fas fa-comments me-2"></i>Start Chat
                                                                    </a>
                                                                </li>
                                                                <li><hr class="dropdown-divider"></li>
                                                                <li>
                                                                    <a class="dropdown-item" href="mailto:<?php echo $user['client_email']; ?>">
                                                                        <i class="fas fa-envelope me-2"></i>Send Email
                                                                    </a>
                                                                </li>
                                                                <li>
                                                                    <a class="dropdown-item" href="tel:<?php echo $user['client_phone']; ?>">
                                                                        <i class="fas fa-phone me-2"></i>Call Client
                                                                    </a>
                                                                </li>
                                                                <li><hr class="dropdown-divider"></li>
                                                                <li>
                                                                    <button class="dropdown-item" onclick="exportClientData(<?php echo $user['user_id']; ?>)">
                                                                        <i class="fas fa-download me-2"></i>Export Data
                                                                    </button>
                                                                </li>
                                                            </ul>
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Bookings -->
        <div class="row">
            <div class="col-md-12">
                <div class="card dashboard-card">
                    <div class="card-header bg-transparent">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0"><i class="fas fa-calendar-check"></i> Recent Bookings</h5>
                            <a href="bookings.php" class="btn btn-outline-primary btn-sm">
                                <i class="fas fa-eye"></i> View All
                            </a>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <?php if (empty($recent_bookings)): ?>
                            <div class="text-center py-5">
                                <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                                <h5 class="text-muted">No Bookings Yet</h5>
                                <p class="text-muted">You don't have any assigned bookings yet.</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-custom mb-0">
                                    <thead>
                                        <tr>
                                            <th>Booking Ref</th>
                                            <th>Service</th>
                                            <th>Client</th>
                                            <th>Date</th>
                                            <th>Status</th>
                                            <th>Amount</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recent_bookings as $booking): ?>
                                            <tr>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($booking['booking_reference']); ?></strong>
                                                </td>
                                                <td>
                                                    <div>
                                                        <strong><?php echo htmlspecialchars($booking['service_title']); ?></strong>
                                                        <br>
                                                        <small class="text-muted"><?php echo htmlspecialchars($booking['service_category']); ?></small>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div>
                                                        <strong><?php echo htmlspecialchars($booking['client_name']); ?></strong>
                                                        <br>
                                                        <small class="text-muted"><?php echo htmlspecialchars($booking['client_email']); ?></small>
                                                    </div>
                                                </td>
                                                <td>
                                                    <small><?php echo date('M d, Y', strtotime($booking['created_at'])); ?></small>
                                                </td>
                                                <td>
                                                    <?php echo getStatusBadge($booking['status']); ?>
                                                </td>
                                                <td>
                                                    <strong>$<?php echo number_format($booking['total_amount'], 2); ?></strong>
                                                </td>
                                                <td>
                                                    <div class="booking-actions">
                                                        <a href="booking-details.php?id=<?php echo $booking['id']; ?>" 
                                                           class="btn btn-sm btn-outline-primary" title="View Details">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                        <?php if ($booking['status'] === 'waiting_approval'): ?>
                                                            <button class="btn btn-sm btn-outline-success" 
                                                                    onclick="updateBookingStatus(<?php echo $booking['id']; ?>, 'approved')" 
                                                                    title="Approve">
                                                                <i class="fas fa-check"></i>
                                                            </button>
                                                        <?php endif; ?>
                                                        <?php if ($booking['status'] === 'approved'): ?>
                                                            <button class="btn btn-sm btn-outline-warning" 
                                                                    onclick="updateBookingStatus(<?php echo $booking['id']; ?>, 'in_progress')" 
                                                                    title="Start Work">
                                                                <i class="fas fa-play"></i>
                                                            </button>
                                                        <?php endif; ?>
                                                        <?php if ($booking['status'] === 'in_progress'): ?>
                                                            <button class="btn btn-sm btn-outline-info" 
                                                                    onclick="updateBookingStatus(<?php echo $booking['id']; ?>, 'completed')" 
                                                                    title="Mark Complete">
                                                                <i class="fas fa-check-circle"></i>
                                                            </button>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Client Details Modal -->
    <div class="modal fade" id="clientDetailsModal" tabindex="-1" aria-labelledby="clientDetailsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="clientDetailsModalLabel">
                        <i class="fas fa-user"></i> Client Details
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="clientDetailsContent">
                    <div class="text-center">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" onclick="startChatWithClient()">Start Chat</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Chat Widget -->
    <div class="chat-notification-widget">
        <button class="chat-fab agent" onclick="openChat()" title="Open Chat">
            <i class="fas fa-comments"></i>
            <span class="notification-badge" id="chat-notification-badge" style="display: none;">0</span>
        </button>
    </div>

    <link href="../css/chat-widget.css" rel="stylesheet">
    <link href="../css/enhancements.css" rel="stylesheet">
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
        
        // Check for messages every 20 seconds (agents need faster updates)
        setInterval(checkUnreadMessages, 20000);
        checkUnreadMessages(); // Initial check

        function updateBookingStatus(bookingId, newStatus) {
            if (confirm(`Are you sure you want to update this booking status to "${newStatus}"?`)) {
                fetch('update-booking-status.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `booking_id=${bookingId}&status=${newStatus}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Error updating booking status: ' + data.error);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error updating booking status');
                });
            }
        }

        // Client Details Modal Functions
        let currentClientId = null;

        function showClientDetails(userId) {
            currentClientId = userId;
            
            // Reset modal content
            document.getElementById('clientDetailsContent').innerHTML = `
                <div class="text-center">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            `;
            
            // Show modal
            const modal = new bootstrap.Modal(document.getElementById('clientDetailsModal'));
            modal.show();
            
            // Load client details
            fetch(`../api/get-client-details.php?user_id=${userId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        renderClientDetails(data);
                    } else {
                        showError(data.error || 'Failed to load client details');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showError('Network error loading client details');
                });
        }

        function renderClientDetails(data) {
            const user = data.user;
            const stats = data.stats;
            const bookings = data.bookings;
            const payments = data.payments;
            const chatInfo = data.chat_info;
            
            const content = `
                <div class="row">
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-body text-center">
                                <div class="profile-avatar mb-3">
                                    ${user.profile_picture ? 
                                        `<img src="../uploads/users/${user.profile_picture}" alt="Profile" class="rounded-circle" width="80" height="80">` :
                                        `<div class="avatar-placeholder rounded-circle mx-auto" style="width: 80px; height: 80px; background: #007bff; color: white; display: flex; align-items: center; justify-content: center; font-size: 2rem;">
                                            ${user.first_name ? user.first_name.charAt(0) : 'U'}
                                        </div>`
                                    }
                                </div>
                                <h5 class="card-title">${user.first_name} ${user.last_name}</h5>
                                <p class="card-text">
                                    <i class="fas fa-envelope"></i> ${user.email}<br>
                                    <i class="fas fa-phone"></i> ${user.phone || 'Not provided'}<br>
                                    <i class="fas fa-calendar"></i> Member since ${new Date(user.created_at).toLocaleDateString()}
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-8">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="card bg-primary text-white">
                                    <div class="card-body">
                                        <h5 class="card-title">Total Bookings</h5>
                                        <h3>${stats.total_bookings}</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card bg-success text-white">
                                    <div class="card-body">
                                        <h5 class="card-title">Total Spent</h5>
                                        <h3>$${parseFloat(stats.total_spent).toFixed(2)}</h3>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-md-4">
                                <div class="card bg-warning text-white">
                                    <div class="card-body">
                                        <h6 class="card-title">Pending</h6>
                                        <h4>${stats.pending_bookings}</h4>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card bg-info text-white">
                                    <div class="card-body">
                                        <h6 class="card-title">In Progress</h6>
                                        <h4>${stats.in_progress_bookings}</h4>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card bg-success text-white">
                                    <div class="card-body">
                                        <h6 class="card-title">Completed</h6>
                                        <h4>${stats.completed_bookings}</h4>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <hr class="my-4">
                
                <div class="row">
                    <div class="col-md-6">
                        <h6><i class="fas fa-calendar-alt"></i> Recent Bookings</h6>
                        <div class="table-responsive" style="max-height: 300px; overflow-y: auto;">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Service</th>
                                        <th>Date</th>
                                        <th>Status</th>
                                        <th>Amount</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${bookings.map(booking => `
                                        <tr>
                                            <td>${booking.service_title}</td>
                                            <td>${booking.booking_date}</td>
                                            <td><span class="badge bg-${getStatusColor(booking.status)}">${booking.status}</span></td>
                                            <td>$${parseFloat(booking.total_amount).toFixed(2)}</td>
                                        </tr>
                                    `).join('')}
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <h6><i class="fas fa-credit-card"></i> Payment History</h6>
                        <div class="table-responsive" style="max-height: 300px; overflow-y: auto;">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Service</th>
                                        <th>Amount</th>
                                        <th>Method</th>
                                        <th>Status</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${payments.map(payment => `
                                        <tr>
                                            <td>${payment.service_title}</td>
                                            <td>$${parseFloat(payment.amount).toFixed(2)}</td>
                                            <td>${payment.payment_method}</td>
                                            <td><span class="badge bg-${payment.payment_status === 'completed' ? 'success' : 'warning'}">${payment.payment_status}</span></td>
                                            <td>${new Date(payment.created_at).toLocaleDateString()}</td>
                                        </tr>
                                    `).join('')}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
                ${chatInfo ? `
                    <hr class="my-4">
                    <div class="card">
                        <div class="card-header">
                            <h6 class="mb-0"><i class="fas fa-comments"></i> Chat History</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-3">
                                    <strong>Total Messages:</strong><br>
                                    ${chatInfo.total_messages}
                                </div>
                                <div class="col-md-3">
                                    <strong>Client Messages:</strong><br>
                                    ${chatInfo.user_messages}
                                </div>
                                <div class="col-md-3">
                                    <strong>Your Messages:</strong><br>
                                    ${chatInfo.agent_messages}
                                </div>
                                <div class="col-md-3">
                                    <strong>Chat Started:</strong><br>
                                    ${new Date(chatInfo.chat_started).toLocaleDateString()}
                                </div>
                            </div>
                        </div>
                    </div>
                ` : ''}
            `;
            
            document.getElementById('clientDetailsContent').innerHTML = content;
        }

        function showError(message) {
            document.getElementById('clientDetailsContent').innerHTML = `
                <div class="alert alert-danger" role="alert">
                    <i class="fas fa-exclamation-triangle"></i> ${message}
                </div>
            `;
        }

        function getStatusColor(status) {
            switch(status) {
                case 'completed': return 'success';
                case 'in_progress': return 'info';
                case 'waiting_approval': return 'warning';
                case 'cancelled': return 'danger';
                default: return 'secondary';
            }
        }

        function startChatWithClient() {
            if (currentClientId) {
                window.location.href = `chat.php?user_id=${currentClientId}`;
            }
        }

        // Client Search and Filter Functions
        function initializeClientFilters() {
            const searchInput = document.getElementById('clientSearchInput');
            const sortSelect = document.getElementById('clientSortSelect');
            
            if (searchInput) {
                searchInput.addEventListener('input', filterClients);
            }
            
            if (sortSelect) {
                sortSelect.addEventListener('change', sortClients);
            }
        }

        function filterClients() {
            const searchTerm = document.getElementById('clientSearchInput').value.toLowerCase();
            const table = document.getElementById('clientsTable');
            const rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');
            
            for (let i = 0; i < rows.length; i++) {
                const row = rows[i];
                const clientName = row.cells[0].textContent.toLowerCase();
                const clientEmail = row.cells[1].textContent.toLowerCase();
                const clientPhone = row.cells[1].textContent.toLowerCase();
                
                if (clientName.includes(searchTerm) || 
                    clientEmail.includes(searchTerm) || 
                    clientPhone.includes(searchTerm)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            }
        }

        function sortClients() {
            const sortBy = document.getElementById('clientSortSelect').value;
            const table = document.getElementById('clientsTable');
            const tbody = table.getElementsByTagName('tbody')[0];
            const rows = Array.from(tbody.getElementsByTagName('tr'));
            
            rows.sort((a, b) => {
                let aValue, bValue;
                
                switch(sortBy) {
                    case 'name':
                        aValue = a.cells[0].textContent.trim();
                        bValue = b.cells[0].textContent.trim();
                        return aValue.localeCompare(bValue);
                    
                    case 'spent':
                        aValue = parseFloat(a.cells[4].textContent.replace('$', ''));
                        bValue = parseFloat(b.cells[4].textContent.replace('$', ''));
                        return bValue - aValue; // Descending order
                    
                    case 'bookings':
                        aValue = parseInt(a.cells[3].textContent);
                        bValue = parseInt(b.cells[3].textContent);
                        return bValue - aValue; // Descending order
                    
                    case 'recent':
                        aValue = new Date(a.cells[5].textContent);
                        bValue = new Date(b.cells[5].textContent);
                        return bValue - aValue; // Most recent first
                    
                    default:
                        return 0;
                }
            });
            
            // Remove existing rows and add sorted rows
            while (tbody.firstChild) {
                tbody.removeChild(tbody.firstChild);
            }
            
            rows.forEach(row => tbody.appendChild(row));
        }

        function clearClientFilters() {
            document.getElementById('clientSearchInput').value = '';
            document.getElementById('clientSortSelect').value = 'name';
            filterClients();
            sortClients();
        }

        // Initialize filters when page loads
        document.addEventListener('DOMContentLoaded', function() {
            initializeClientFilters();
        });

        // Export client data functionality
        function exportClientData(userId) {
            if (confirm('Export client data to CSV?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'export-client-data.php';
                form.style.display = 'none';
                
                const userIdInput = document.createElement('input');
                userIdInput.name = 'user_id';
                userIdInput.value = userId;
                form.appendChild(userIdInput);
                
                document.body.appendChild(form);
                form.submit();
                document.body.removeChild(form);
            }
        }
    </script>
</body>
</html>
