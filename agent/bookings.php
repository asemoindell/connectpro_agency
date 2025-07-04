<?php
session_start();
require_once '../config/database.php';

// Check if agent is logged in
if (!isset($_SESSION['agent_logged_in'])) {
    header('Location: login.php');
    exit();
}

$agent_id = $_SESSION['agent_id'];

// Get filters from query parameters
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$service_filter = isset($_GET['service']) ? $_GET['service'] : '';
$date_filter = isset($_GET['date']) ? $_GET['date'] : '';
$search_filter = isset($_GET['search']) ? $_GET['search'] : '';
$per_page = isset($_GET['per_page']) ? intval($_GET['per_page']) : 20;
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$offset = ($page - 1) * $per_page;

try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    // Get agent info
    $stmt = $pdo->prepare("SELECT * FROM admin_users WHERE id = ?");
    $stmt->execute([$agent_id]);
    $agent = $stmt->fetch();
    
    if (!$agent) {
        session_destroy();
        header('Location: login.php');
        exit();
    }
    
    // Build WHERE clause for filters
    $where_conditions = ["sb.assigned_admin_id = ?"];
    $params = [$agent_id];
    
    if (!empty($status_filter)) {
        $where_conditions[] = "sb.status = ?";
        $params[] = $status_filter;
    }
    
    if (!empty($service_filter)) {
        $where_conditions[] = "sb.service_id = ?";
        $params[] = $service_filter;
    }
    
    if (!empty($date_filter)) {
        $where_conditions[] = "DATE(sb.created_at) = ?";
        $params[] = $date_filter;
    }
    
    if (!empty($search_filter)) {
        $where_conditions[] = "(sb.client_name LIKE ? OR sb.client_email LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ?)";
        $search_param = '%' . $search_filter . '%';
        $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param]);
    }
    
    $where_clause = "WHERE " . implode(" AND ", $where_conditions);
    
    // Get total count for pagination
    $count_query = "
        SELECT COUNT(*) as total
        FROM service_bookings sb
        LEFT JOIN services se ON sb.service_id = se.id
        LEFT JOIN users u ON sb.user_id = u.id
        $where_clause
    ";
    $stmt = $pdo->prepare($count_query);
    $stmt->execute($params);
    $total_records = $stmt->fetch()['total'];
    $total_pages = ceil($total_records / $per_page);
    
    // Get bookings with pagination
    $main_query = "
        SELECT 
            sb.*,
            se.title as service_title,
            se.category as service_category,
            se.price_range as service_price,
            u.first_name as client_first_name,
            u.last_name as client_last_name,
            u.email as client_email_db,
            u.phone as client_phone_db
        FROM service_bookings sb
        LEFT JOIN services se ON sb.service_id = se.id
        LEFT JOIN users u ON sb.user_id = u.id
        $where_clause
        ORDER BY sb.created_at DESC
        LIMIT ? OFFSET ?
    ";
    
    $stmt = $pdo->prepare($main_query);
    
    // Bind the WHERE clause parameters first
    $param_index = 1;
    foreach ($params as $param) {
        $stmt->bindValue($param_index, $param);
        $param_index++;
    }
    
    // Bind LIMIT and OFFSET as integers
    $stmt->bindValue($param_index, $per_page, PDO::PARAM_INT);
    $stmt->bindValue($param_index + 1, $offset, PDO::PARAM_INT);
    
    $stmt->execute();
    $bookings = $stmt->fetchAll();
    
    // Get statistics
    $stats_query = "
        SELECT 
            COUNT(*) as total_bookings,
            COUNT(CASE WHEN sb.status = 'waiting_approval' THEN 1 END) as pending_bookings,
            COUNT(CASE WHEN sb.status = 'approved' THEN 1 END) as approved_bookings,
            COUNT(CASE WHEN sb.status = 'in_progress' THEN 1 END) as in_progress_bookings,
            COUNT(CASE WHEN sb.status = 'completed' THEN 1 END) as completed_bookings,
            COUNT(CASE WHEN sb.status = 'cancelled' THEN 1 END) as cancelled_bookings,
            SUM(CASE WHEN sb.status = 'completed' THEN sb.total_amount ELSE 0 END) as total_revenue
        FROM service_bookings sb
        WHERE sb.assigned_admin_id = ?
    ";
    $stmt = $pdo->prepare($stats_query);
    $stmt->execute([$agent_id]);
    $stats = $stmt->fetch();
    
    // Get services for filter dropdown
    $stmt = $pdo->prepare("
        SELECT DISTINCT se.id, se.title 
        FROM services se
        INNER JOIN service_bookings sb ON se.id = sb.service_id
        WHERE sb.assigned_admin_id = ?
        ORDER BY se.title
    ");
    $stmt->execute([$agent_id]);
    $services = $stmt->fetchAll();
    
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
            return '<span class="badge bg-danger"><i class="fas fa-times-circle"></i> Cancelled</span>';
        default:
            return '<span class="badge bg-secondary">' . ucfirst($status) . '</span>';
    }
}

function getPriorityBadge($priority) {
    switch ($priority) {
        case 'high':
            return '<span class="badge bg-danger"><i class="fas fa-exclamation-triangle"></i> High</span>';
        case 'medium':
            return '<span class="badge bg-warning"><i class="fas fa-exclamation"></i> Medium</span>';
        case 'low':
            return '<span class="badge bg-info"><i class="fas fa-info-circle"></i> Low</span>';
        default:
            return '<span class="badge bg-secondary">Normal</span>';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Bookings - ConnectPro Agency</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../css/enhancements.css" rel="stylesheet">
    <style>
        .booking-card {
            transition: transform 0.2s ease-in-out;
            border: none;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .booking-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 16px rgba(0,0,0,0.15);
        }
        .filters-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            border-radius: 10px;
            margin-bottom: 2rem;
        }
        .stat-card {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            text-align: center;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 1rem;
        }
        .stat-card h3 {
            margin: 0;
            font-size: 2rem;
            font-weight: bold;
        }
        .table-responsive {
            border-radius: 10px;
            overflow: hidden;
        }
        .table th {
            background: #f8f9fa;
            font-weight: 600;
            border-bottom: 2px solid #dee2e6;
        }
        .pagination {
            margin-top: 2rem;
        }
        .booking-actions {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }
        .booking-actions .btn {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="#"><i class="fas fa-briefcase"></i> ConnectPro Agency</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="bookings.php"><i class="fas fa-calendar-alt"></i> Bookings</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="chat.php"><i class="fas fa-comments"></i> Chat</a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user-circle"></i> <?php echo htmlspecialchars($agent['first_name'] . ' ' . $agent['last_name']); ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user"></i> Profile</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row">
            <div class="col-md-12">
                <h2 class="mb-4"><i class="fas fa-calendar-alt"></i> My Bookings</h2>
                
                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-md-2">
                        <div class="stat-card">
                            <h3 class="text-primary"><?php echo $stats['total_bookings']; ?></h3>
                            <p class="mb-0">Total Bookings</p>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="stat-card">
                            <h3 class="text-warning"><?php echo $stats['pending_bookings']; ?></h3>
                            <p class="mb-0">Pending</p>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="stat-card">
                            <h3 class="text-info"><?php echo $stats['in_progress_bookings']; ?></h3>
                            <p class="mb-0">In Progress</p>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="stat-card">
                            <h3 class="text-success"><?php echo $stats['completed_bookings']; ?></h3>
                            <p class="mb-0">Completed</p>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="stat-card">
                            <h3 class="text-danger"><?php echo $stats['cancelled_bookings']; ?></h3>
                            <p class="mb-0">Cancelled</p>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="stat-card">
                            <h3 class="text-success">$<?php echo number_format($stats['total_revenue'], 2); ?></h3>
                            <p class="mb-0">Revenue</p>
                        </div>
                    </div>
                </div>

                <!-- Filters Section -->
                <div class="filters-section">
                    <h5 class="mb-3"><i class="fas fa-filter"></i> Filters & Search</h5>
                    <form method="GET" action="bookings.php">
                        <div class="row">
                            <div class="col-md-3">
                                <label class="form-label">Status</label>
                                <select name="status" class="form-select">
                                    <option value="">All Statuses</option>
                                    <option value="waiting_approval" <?php echo $status_filter === 'waiting_approval' ? 'selected' : ''; ?>>Waiting Approval</option>
                                    <option value="approved" <?php echo $status_filter === 'approved' ? 'selected' : ''; ?>>Approved</option>
                                    <option value="in_progress" <?php echo $status_filter === 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                                    <option value="completed" <?php echo $status_filter === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                    <option value="cancelled" <?php echo $status_filter === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Service</label>
                                <select name="service" class="form-select">
                                    <option value="">All Services</option>
                                    <?php foreach ($services as $service): ?>
                                        <option value="<?php echo $service['id']; ?>" <?php echo $service_filter == $service['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($service['title']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Date</label>
                                <input type="date" name="date" class="form-control" value="<?php echo htmlspecialchars($date_filter); ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Search</label>
                                <input type="text" name="search" class="form-control" placeholder="Search client name or email..." value="<?php echo htmlspecialchars($search_filter); ?>">
                            </div>
                            <div class="col-md-1">
                                <label class="form-label">&nbsp;</label>
                                <button type="submit" class="btn btn-light w-100">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-md-2">
                                <label class="form-label">Per Page</label>
                                <select name="per_page" class="form-select">
                                    <option value="10" <?php echo $per_page == 10 ? 'selected' : ''; ?>>10</option>
                                    <option value="20" <?php echo $per_page == 20 ? 'selected' : ''; ?>>20</option>
                                    <option value="50" <?php echo $per_page == 50 ? 'selected' : ''; ?>>50</option>
                                    <option value="100" <?php echo $per_page == 100 ? 'selected' : ''; ?>>100</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">&nbsp;</label>
                                <a href="bookings.php" class="btn btn-outline-light w-100">
                                    <i class="fas fa-times"></i> Clear
                                </a>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Bookings Table -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-list"></i> Bookings 
                            <span class="badge bg-primary ms-2"><?php echo $total_records; ?> total</span>
                        </h5>
                    </div>
                    <div class="card-body p-0">
                        <?php if (empty($bookings)): ?>
                            <div class="text-center py-5">
                                <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                                <h5 class="text-muted">No Bookings Found</h5>
                                <p class="text-muted">No bookings match your current filters.</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Client</th>
                                            <th>Service</th>
                                            <th>Date & Time</th>
                                            <th>Status</th>
                                            <th>Amount</th>
                                            <th>Created</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($bookings as $booking): ?>
                                            <tr>
                                                <td>
                                                    <strong>#<?php echo $booking['id']; ?></strong>
                                                </td>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="avatar-placeholder rounded-circle me-2" style="width: 40px; height: 40px; background: #007bff; color: white; display: flex; align-items: center; justify-content: center; font-size: 1rem;">
                                                            <?php echo strtoupper(substr($booking['client_name'] ?: ($booking['client_first_name'] . ' ' . $booking['client_last_name']), 0, 1)); ?>
                                                        </div>
                                                        <div>
                                                            <div class="fw-bold">
                                                                <?php echo htmlspecialchars($booking['client_name'] ?: ($booking['client_first_name'] . ' ' . $booking['client_last_name'])); ?>
                                                            </div>
                                                            <small class="text-muted">
                                                                <?php echo htmlspecialchars($booking['client_email'] ?: $booking['client_email_db']); ?>
                                                            </small>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="fw-bold"><?php echo htmlspecialchars($booking['service_title']); ?></div>
                                                    <small class="text-muted"><?php echo htmlspecialchars($booking['service_category']); ?></small>
                                                </td>
                                                <td>
                                                    <div class="fw-bold"><?php echo date('M j, Y', strtotime($booking['booking_date'])); ?></div>
                                                    <small class="text-muted"><?php echo date('g:i A', strtotime($booking['booking_time'])); ?></small>
                                                </td>
                                                <td>
                                                    <?php echo getStatusBadge($booking['status']); ?>
                                                </td>
                                                <td>
                                                    <strong class="text-success">$<?php echo number_format($booking['total_amount'], 2); ?></strong>
                                                </td>
                                                <td>
                                                    <small class="text-muted"><?php echo date('M j, Y g:i A', strtotime($booking['created_at'])); ?></small>
                                                </td>
                                                <td>
                                                    <div class="booking-actions">
                                                        <button class="btn btn-sm btn-outline-primary" onclick="viewBookingDetails(<?php echo $booking['id']; ?>)" title="View Details">
                                                            <i class="fas fa-eye"></i>
                                                        </button>
                                                        <?php if ($booking['status'] === 'waiting_approval'): ?>
                                                            <button class="btn btn-sm btn-success" onclick="updateBookingStatus(<?php echo $booking['id']; ?>, 'approved')" title="Approve">
                                                                <i class="fas fa-check"></i>
                                                            </button>
                                                            <button class="btn btn-sm btn-danger" onclick="updateBookingStatus(<?php echo $booking['id']; ?>, 'cancelled')" title="Cancel">
                                                                <i class="fas fa-times"></i>
                                                            </button>
                                                        <?php elseif ($booking['status'] === 'approved'): ?>
                                                            <button class="btn btn-sm btn-primary" onclick="updateBookingStatus(<?php echo $booking['id']; ?>, 'in_progress')" title="Start Work">
                                                                <i class="fas fa-play"></i>
                                                            </button>
                                                        <?php elseif ($booking['status'] === 'in_progress'): ?>
                                                            <button class="btn btn-sm btn-success" onclick="updateBookingStatus(<?php echo $booking['id']; ?>, 'completed')" title="Complete">
                                                                <i class="fas fa-check-circle"></i>
                                                            </button>
                                                        <?php endif; ?>
                                                        <?php if ($booking['user_id']): ?>
                                                            <a href="chat.php?user_id=<?php echo $booking['user_id']; ?>" class="btn btn-sm btn-outline-info" title="Chat">
                                                                <i class="fas fa-comments"></i>
                                                            </a>
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

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <nav aria-label="Bookings pagination">
                        <ul class="pagination justify-content-center">
                            <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>">Previous</a>
                                </li>
                            <?php endif; ?>
                            
                            <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>"><?php echo $i; ?></a>
                                </li>
                            <?php endfor; ?>
                            
                            <?php if ($page < $total_pages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>">Next</a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Booking Details Modal -->
    <div class="modal fade" id="bookingDetailsModal" tabindex="-1" aria-labelledby="bookingDetailsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="bookingDetailsModalLabel">
                        <i class="fas fa-calendar-alt"></i> Booking Details
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="bookingDetailsContent">
                    <div class="text-center">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
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

        function viewBookingDetails(bookingId) {
            // Reset modal content
            document.getElementById('bookingDetailsContent').innerHTML = `
                <div class="text-center">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            `;
            
            // Show modal
            const modal = new bootstrap.Modal(document.getElementById('bookingDetailsModal'));
            modal.show();
            
            // Load booking details
            fetch(`get-booking-details.php?booking_id=${bookingId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        renderBookingDetails(data.booking);
                    } else {
                        showError(data.error || 'Failed to load booking details');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showError('Network error loading booking details');
                });
        }

        function renderBookingDetails(booking) {
            const content = `
                <div class="row">
                    <div class="col-md-6">
                        <h6><i class="fas fa-user"></i> Client Information</h6>
                        <table class="table table-sm">
                            <tr><td><strong>Name:</strong></td><td>${booking.client_name}</td></tr>
                            <tr><td><strong>Email:</strong></td><td>${booking.client_email}</td></tr>
                            <tr><td><strong>Phone:</strong></td><td>${booking.client_phone || 'Not provided'}</td></tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <h6><i class="fas fa-cogs"></i> Service Information</h6>
                        <table class="table table-sm">
                            <tr><td><strong>Service:</strong></td><td>${booking.service_title}</td></tr>
                            <tr><td><strong>Category:</strong></td><td>${booking.service_category}</td></tr>
                            <tr><td><strong>Amount:</strong></td><td>$${parseFloat(booking.total_amount).toFixed(2)}</td></tr>
                        </table>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <h6><i class="fas fa-calendar"></i> Booking Details</h6>
                        <table class="table table-sm">
                            <tr><td><strong>Date:</strong></td><td>${booking.booking_date}</td></tr>
                            <tr><td><strong>Time:</strong></td><td>${booking.booking_time}</td></tr>
                            <tr><td><strong>Status:</strong></td><td>${getStatusBadgeHTML(booking.status)}</td></tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <h6><i class="fas fa-info-circle"></i> Additional Information</h6>
                        <table class="table table-sm">
                            <tr><td><strong>Created:</strong></td><td>${new Date(booking.created_at).toLocaleString()}</td></tr>
                            <tr><td><strong>Updated:</strong></td><td>${new Date(booking.updated_at).toLocaleString()}</td></tr>
                        </table>
                    </div>
                </div>
                ${booking.client_message ? `
                    <div class="row">
                        <div class="col-md-12">
                            <h6><i class="fas fa-comment"></i> Client Message</h6>
                            <div class="alert alert-info">
                                ${booking.client_message}
                            </div>
                        </div>
                    </div>
                ` : ''}
            `;
            
            document.getElementById('bookingDetailsContent').innerHTML = content;
        }

        function getStatusBadgeHTML(status) {
            switch(status) {
                case 'waiting_approval':
                    return '<span class="badge bg-warning">Waiting Approval</span>';
                case 'approved':
                    return '<span class="badge bg-info">Approved</span>';
                case 'in_progress':
                    return '<span class="badge bg-primary">In Progress</span>';
                case 'completed':
                    return '<span class="badge bg-success">Completed</span>';
                case 'cancelled':
                    return '<span class="badge bg-danger">Cancelled</span>';
                default:
                    return '<span class="badge bg-secondary">' + status + '</span>';
            }
        }

        function showError(message) {
            document.getElementById('bookingDetailsContent').innerHTML = `
                <div class="alert alert-danger" role="alert">
                    <i class="fas fa-exclamation-triangle"></i> ${message}
                </div>
            `;
        }
    </script>
</body>
</html>
