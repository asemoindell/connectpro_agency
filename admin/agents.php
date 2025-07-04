<?php
session_start();
require_once '../config/database.php';
require_once 'includes/admin-layout.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

$database = new Database();
$db = $database->getConnection();

$success_message = '';
$error_message = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'create_agent':
                $first_name = trim($_POST['first_name']);
                $last_name = trim($_POST['last_name']);
                $email = trim($_POST['email']);
                $username = trim($_POST['username']);
                $phone = trim($_POST['phone']);
                $password = $_POST['password'];
                $role = $_POST['role'];
                $specialization = trim($_POST['specialization']);
                $bio = trim($_POST['bio']);
                $hourly_rate = floatval($_POST['hourly_rate']);
                $commission_rate = floatval($_POST['commission_rate']);
                
                // Validate required fields
                if ($first_name && $last_name && $email && $username && $password) {
                    try {
                        // Check if username or email already exists
                        $check_stmt = $db->prepare("SELECT id FROM admin_users WHERE email = ? OR username = ?");
                        $check_stmt->execute([$email, $username]);
                        
                        if ($check_stmt->fetch()) {
                            $error_message = "Email or username already exists.";
                        } else {
                            // Create new agent
                            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                            
                            $stmt = $db->prepare("INSERT INTO admin_users 
                                (first_name, last_name, email, username, phone, password, role, 
                                 specialization, bio, hourly_rate, commission_rate, status, is_available) 
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active', 1)");
                            
                            $result = $stmt->execute([
                                $first_name, $last_name, $email, $username, $phone, 
                                $hashed_password, $role, $specialization, $bio, 
                                $hourly_rate, $commission_rate
                            ]);
                            
                            if ($result) {
                                $success_message = "Agent created successfully!";
                            } else {
                                $error_message = "Failed to create agent.";
                            }
                        }
                    } catch (Exception $e) {
                        $error_message = "Error: " . $e->getMessage();
                    }
                } else {
                    $error_message = "Please fill in all required fields.";
                }
                break;
                
            case 'update_agent':
                $agent_id = intval($_POST['agent_id']);
                $first_name = trim($_POST['first_name']);
                $last_name = trim($_POST['last_name']);
                $email = trim($_POST['email']);
                $username = trim($_POST['username']);
                $phone = trim($_POST['phone']);
                $role = $_POST['role'];
                $specialization = trim($_POST['specialization']);
                $bio = trim($_POST['bio']);
                $hourly_rate = floatval($_POST['hourly_rate']);
                $commission_rate = floatval($_POST['commission_rate']);
                $status = $_POST['status'];
                $is_available = isset($_POST['is_available']) ? 1 : 0;
                
                try {
                    $stmt = $db->prepare("UPDATE admin_users SET 
                        first_name = ?, last_name = ?, email = ?, username = ?, phone = ?, 
                        role = ?, specialization = ?, bio = ?, hourly_rate = ?, 
                        commission_rate = ?, status = ?, is_available = ? 
                        WHERE id = ?");
                    
                    $result = $stmt->execute([
                        $first_name, $last_name, $email, $username, $phone, 
                        $role, $specialization, $bio, $hourly_rate, 
                        $commission_rate, $status, $is_available, $agent_id
                    ]);
                    
                    if ($result) {
                        $success_message = "Agent updated successfully!";
                    } else {
                        $error_message = "Failed to update agent.";
                    }
                } catch (Exception $e) {
                    $error_message = "Error: " . $e->getMessage();
                }
                break;
                
            case 'change_password':
                $agent_id = intval($_POST['agent_id']);
                $new_password = $_POST['new_password'];
                
                if ($new_password) {
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    
                    $stmt = $db->prepare("UPDATE admin_users SET password = ? WHERE id = ?");
                    $result = $stmt->execute([$hashed_password, $agent_id]);
                    
                    if ($result) {
                        $success_message = "Password updated successfully!";
                    } else {
                        $error_message = "Failed to update password.";
                    }
                } else {
                    $error_message = "Please enter a new password.";
                }
                break;
                
            case 'delete_agent':
                $agent_id = intval($_POST['agent_id']);
                
                try {
                    // Check if agent has any bookings
                    $check_stmt = $db->prepare("SELECT COUNT(*) FROM service_bookings WHERE assigned_admin_id = ?");
                    $check_stmt->execute([$agent_id]);
                    $booking_count = $check_stmt->fetchColumn();
                    
                    if ($booking_count > 0) {
                        $error_message = "Cannot delete agent with existing bookings. Set status to inactive instead.";
                    } else {
                        $stmt = $db->prepare("DELETE FROM admin_users WHERE id = ?");
                        $result = $stmt->execute([$agent_id]);
                        
                        if ($result) {
                            $success_message = "Agent deleted successfully!";
                        } else {
                            $error_message = "Failed to delete agent.";
                        }
                    }
                } catch (Exception $e) {
                    $error_message = "Error: " . $e->getMessage();
                }
                break;
        }
    }
}

// Get agents with statistics
$agents_stmt = $db->prepare("
    SELECT 
        a.*,
        COUNT(sb.id) as total_bookings,
        COUNT(CASE WHEN sb.status = 'completed' THEN 1 END) as completed_bookings,
        SUM(CASE WHEN sb.status = 'completed' THEN sb.total_amount ELSE 0 END) as total_revenue
    FROM admin_users a
    LEFT JOIN service_bookings sb ON a.id = sb.assigned_admin_id
    GROUP BY a.id
    ORDER BY a.created_at DESC
");
$agents_stmt->execute();
$agents = $agents_stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agent Management - ConnectPro Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Inter', sans-serif;
        }
        .navbar-brand {
            font-weight: 600;
        }
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .table th {
            border-top: none;
            background-color: #f8f9fa;
            font-weight: 600;
        }
        .agent-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
        }
        .agent-initials {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
        }
        .btn-group .btn {
            margin-right: 5px;
        }
        .modal-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .form-label {
            font-weight: 500;
        }
        .status-badge {
            padding: 4px 8px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 500;
        }
        .status-active {
            background-color: #d4edda;
            color: #155724;
        }
        .status-inactive {
            background-color: #f8d7da;
            color: #721c24;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
        <div class="container-fluid">
            <a class="navbar-brand" href="dashboard.php">
                <i class="fas fa-shield-alt me-2"></i>ConnectPro Admin
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                <a class="nav-link" href="users.php"><i class="fas fa-users"></i> Users</a>
                <a class="nav-link active" href="agents.php"><i class="fas fa-user-tie"></i> Agents</a>
                <a class="nav-link" href="services.php"><i class="fas fa-cogs"></i> Services</a>
                <a class="nav-link" href="bookings.php"><i class="fas fa-calendar-check"></i> Bookings</a>
                <a class="nav-link" href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>
    </nav>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">Agent Management</h1>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createAgentModal">
                    <i class="fas fa-plus"></i> Create New Agent
                </button>
            </div>
            
            <?php if ($success_message): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success_message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <?php if ($error_message): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($error_message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <!-- Agents Grid -->
            <div class="row">
                <?php foreach ($agents as $agent): ?>
                    <div class="col-lg-4 col-md-6 mb-4">
                        <div class="card h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-center mb-3">
                                    <div class="agent-avatar me-3">
                                        <?php if ($agent['profile_image']): ?>
                                            <img src="<?php echo htmlspecialchars($agent['profile_image']); ?>" 
                                                 alt="<?php echo htmlspecialchars($agent['first_name']); ?>" 
                                                 class="rounded-circle" width="60" height="60" style="object-fit: cover;">
                                        <?php else: ?>
                                            <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" 
                                                 style="width: 60px; height: 60px; font-size: 1.5rem; font-weight: bold;">
                                                <?php echo strtoupper(substr($agent['first_name'], 0, 1) . substr($agent['last_name'], 0, 1)); ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="flex-grow-1">
                                        <h5 class="card-title mb-1">
                                            <?php echo htmlspecialchars($agent['first_name'] . ' ' . $agent['last_name']); ?>
                                        </h5>
                                        <p class="text-muted mb-0">@<?php echo htmlspecialchars($agent['username'] ?: 'N/A'); ?></p>
                                    </div>
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" 
                                                type="button" data-bs-toggle="dropdown">
                                            <i class="fas fa-ellipsis-v"></i>
                                        </button>
                                        <ul class="dropdown-menu">
                                            <li><a class="dropdown-item" href="#" onclick="editAgent(<?php echo $agent['id']; ?>)">
                                                <i class="fas fa-edit"></i> Edit Profile
                                            </a></li>
                                            <li><a class="dropdown-item" href="#" onclick="changePassword(<?php echo $agent['id']; ?>)">
                                                <i class="fas fa-key"></i> Change Password
                                            </a></li>
                                            <li><a class="dropdown-item" href="#" onclick="uploadPhoto(<?php echo $agent['id']; ?>)">
                                                <i class="fas fa-camera"></i> Upload Photo
                                            </a></li>
                                            <li><hr class="dropdown-divider"></li>
                                            <li><a class="dropdown-item text-danger" href="#" onclick="deleteAgent(<?php echo $agent['id']; ?>)">
                                                <i class="fas fa-trash"></i> Delete Agent
                                            </a></li>
                                        </ul>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <span class="badge bg-<?php echo $agent['status'] === 'active' ? 'success' : 'secondary'; ?> me-1">
                                        <?php echo ucfirst($agent['status']); ?>
                                    </span>
                                    <span class="badge bg-<?php echo $agent['is_available'] ? 'info' : 'warning'; ?> me-1">
                                        <?php echo $agent['is_available'] ? 'Available' : 'Busy'; ?>
                                    </span>
                                    <span class="badge bg-primary">
                                        <?php echo ucfirst(str_replace('-', ' ', $agent['role'])); ?>
                                    </span>
                                </div>
                                
                                <?php if ($agent['specialization']): ?>
                                    <p class="text-muted mb-2">
                                        <i class="fas fa-star text-warning"></i> 
                                        <?php echo htmlspecialchars($agent['specialization']); ?>
                                    </p>
                                <?php endif; ?>
                                
                                <div class="row text-center">
                                    <div class="col-4">
                                        <div class="fw-bold text-primary"><?php echo $agent['total_bookings']; ?></div>
                                        <small class="text-muted">Bookings</small>
                                    </div>
                                    <div class="col-4">
                                        <div class="fw-bold text-success"><?php echo $agent['completed_bookings']; ?></div>
                                        <small class="text-muted">Completed</small>
                                    </div>
                                    <div class="col-4">
                                        <div class="fw-bold text-info">$<?php echo number_format($agent['total_revenue'], 0); ?></div>
                                        <small class="text-muted">Revenue</small>
                                    </div>
                                </div>
                                
                                <?php if ($agent['hourly_rate'] > 0): ?>
                                    <div class="mt-3 pt-3 border-top">
                                        <div class="d-flex justify-content-between">
                                            <span class="text-muted">Hourly Rate:</span>
                                            <span class="fw-bold">$<?php echo number_format($agent['hourly_rate'], 2); ?></span>
                                        </div>
                                        <div class="d-flex justify-content-between">
                                            <span class="text-muted">Commission:</span>
                                            <span class="fw-bold"><?php echo number_format($agent['commission_rate'], 1); ?>%</span>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="mt-3">
                                    <div class="d-flex gap-2">
                                        <a href="mailto:<?php echo htmlspecialchars($agent['email']); ?>" 
                                           class="btn btn-sm btn-outline-primary flex-fill">
                                            <i class="fas fa-envelope"></i>
                                        </a>
                                        <?php if ($agent['phone']): ?>
                                            <a href="tel:<?php echo htmlspecialchars($agent['phone']); ?>" 
                                               class="btn btn-sm btn-outline-success flex-fill">
                                                <i class="fas fa-phone"></i>
                                            </a>
                                        <?php endif; ?>
                                        <button class="btn btn-sm btn-outline-secondary flex-fill" 
                                                onclick="viewAgentDetails(<?php echo $agent['id']; ?>)">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<!-- Create Agent Modal -->
<div class="modal fade" id="createAgentModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Create New Agent</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="create_agent">
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">First Name *</label>
                                <input type="text" class="form-control" name="first_name" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Last Name *</label>
                                <input type="text" class="form-control" name="last_name" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Email *</label>
                                <input type="email" class="form-control" name="email" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Username *</label>
                                <input type="text" class="form-control" name="username" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Phone</label>
                                <input type="tel" class="form-control" name="phone">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Role *</label>
                                <select class="form-select" name="role" required>
                                    <option value="service-admin">Service Admin</option>
                                    <option value="content-admin">Content Admin</option>
                                    <option value="super-admin">Super Admin</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Password *</label>
                        <input type="password" class="form-control" name="password" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Specialization</label>
                        <input type="text" class="form-control" name="specialization" 
                               placeholder="e.g., Legal Services, Travel Planning, Financial Consulting">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Bio</label>
                        <textarea class="form-control" name="bio" rows="3" 
                                  placeholder="Brief description of the agent's background and expertise"></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Hourly Rate ($)</label>
                                <input type="number" class="form-control" name="hourly_rate" min="0" step="0.01" value="0">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Commission Rate (%)</label>
                                <input type="number" class="form-control" name="commission_rate" min="0" max="100" step="0.1" value="0">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Agent</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Agent Modal -->
<div class="modal fade" id="editAgentModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Agent</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="editAgentForm">
                <div class="modal-body">
                    <input type="hidden" name="action" value="update_agent">
                    <input type="hidden" name="agent_id" id="edit_agent_id">
                    
                    <!-- Form fields will be populated by JavaScript -->
                    <div id="editAgentFormContent"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Agent</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Change Password Modal -->
<div class="modal fade" id="changePasswordModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Change Password</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="change_password">
                    <input type="hidden" name="agent_id" id="password_agent_id">
                    
                    <div class="mb-3">
                        <label class="form-label">New Password</label>
                        <input type="password" class="form-control" name="new_password" required>
                    </div>
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> 
                        The agent will need to use this new password to log in.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning">Change Password</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Upload Photo Modal -->
<div class="modal fade" id="uploadPhotoModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Upload Profile Photo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="uploadPhotoForm" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" id="photo_agent_id">
                    
                    <div class="mb-3">
                        <label class="form-label">Select Photo</label>
                        <input type="file" class="form-control" id="profile_image" accept="image/*" required>
                    </div>
                    
                    <div class="mb-3">
                        <div id="photo_preview" class="text-center" style="display: none;">
                            <img id="preview_image" src="" alt="Preview" class="img-thumbnail" style="max-width: 200px;">
                        </div>
                    </div>
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> 
                        Recommended size: 300x300 pixels. Max file size: 2MB.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Upload Photo</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Agent data for JavaScript access
const agentsData = <?php echo json_encode($agents); ?>;

function editAgent(agentId) {
    const agent = agentsData.find(a => a.id == agentId);
    if (!agent) return;
    
    document.getElementById('edit_agent_id').value = agentId;
    
    const formContent = `
        <div class="row">
            <div class="col-md-6">
                <div class="mb-3">
                    <label class="form-label">First Name *</label>
                    <input type="text" class="form-control" name="first_name" value="${agent.first_name}" required>
                </div>
            </div>
            <div class="col-md-6">
                <div class="mb-3">
                    <label class="form-label">Last Name *</label>
                    <input type="text" class="form-control" name="last_name" value="${agent.last_name}" required>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-6">
                <div class="mb-3">
                    <label class="form-label">Email *</label>
                    <input type="email" class="form-control" name="email" value="${agent.email}" required>
                </div>
            </div>
            <div class="col-md-6">
                <div class="mb-3">
                    <label class="form-label">Username *</label>
                    <input type="text" class="form-control" name="username" value="${agent.username || ''}" required>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-6">
                <div class="mb-3">
                    <label class="form-label">Phone</label>
                    <input type="tel" class="form-control" name="phone" value="${agent.phone || ''}">
                </div>
            </div>
            <div class="col-md-6">
                <div class="mb-3">
                    <label class="form-label">Role *</label>
                    <select class="form-select" name="role" required>
                        <option value="service-admin" ${agent.role === 'service-admin' ? 'selected' : ''}>Service Admin</option>
                        <option value="content-admin" ${agent.role === 'content-admin' ? 'selected' : ''}>Content Admin</option>
                        <option value="super-admin" ${agent.role === 'super-admin' ? 'selected' : ''}>Super Admin</option>
                    </select>
                </div>
            </div>
        </div>
        
        <div class="mb-3">
            <label class="form-label">Specialization</label>
            <input type="text" class="form-control" name="specialization" value="${agent.specialization || ''}" 
                   placeholder="e.g., Legal Services, Travel Planning, Financial Consulting">
        </div>
        
        <div class="mb-3">
            <label class="form-label">Bio</label>
            <textarea class="form-control" name="bio" rows="3">${agent.bio || ''}</textarea>
        </div>
        
        <div class="row">
            <div class="col-md-6">
                <div class="mb-3">
                    <label class="form-label">Hourly Rate ($)</label>
                    <input type="number" class="form-control" name="hourly_rate" min="0" step="0.01" value="${agent.hourly_rate}">
                </div>
            </div>
            <div class="col-md-6">
                <div class="mb-3">
                    <label class="form-label">Commission Rate (%)</label>
                    <input type="number" class="form-control" name="commission_rate" min="0" max="100" step="0.1" value="${agent.commission_rate}">
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-6">
                <div class="mb-3">
                    <label class="form-label">Status</label>
                    <select class="form-select" name="status">
                        <option value="active" ${agent.status === 'active' ? 'selected' : ''}>Active</option>
                        <option value="inactive" ${agent.status === 'inactive' ? 'selected' : ''}>Inactive</option>
                    </select>
                </div>
            </div>
            <div class="col-md-6">
                <div class="mb-3">
                    <label class="form-label">Availability</label>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="is_available" ${agent.is_available ? 'checked' : ''}>
                        <label class="form-check-label">Available for new bookings</label>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    document.getElementById('editAgentFormContent').innerHTML = formContent;
    
    new bootstrap.Modal(document.getElementById('editAgentModal')).show();
}

function changePassword(agentId) {
    document.getElementById('password_agent_id').value = agentId;
    new bootstrap.Modal(document.getElementById('changePasswordModal')).show();
}

function uploadPhoto(agentId) {
    document.getElementById('photo_agent_id').value = agentId;
    new bootstrap.Modal(document.getElementById('uploadPhotoModal')).show();
}

function deleteAgent(agentId) {
    const agent = agentsData.find(a => a.id == agentId);
    if (!agent) return;
    
    if (confirm(`Are you sure you want to delete ${agent.first_name} ${agent.last_name}? This action cannot be undone.`)) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="delete_agent">
            <input type="hidden" name="agent_id" value="${agentId}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

function viewAgentDetails(agentId) {
    // Redirect to detailed agent view (could be implemented later)
    console.log('View details for agent:', agentId);
}

// Handle photo preview
document.getElementById('profile_image').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('preview_image').src = e.target.result;
            document.getElementById('photo_preview').style.display = 'block';
        };
        reader.readAsDataURL(file);
    }
});

// Handle photo upload
document.getElementById('uploadPhotoForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const agentId = document.getElementById('photo_agent_id').value;
    const fileInput = document.getElementById('profile_image');
    const file = fileInput.files[0];
    
    if (!file) {
        alert('Please select a file');
        return;
    }
    
    const formData = new FormData();
    formData.append('agent_id', agentId);
    formData.append('profile_image', file);
    
    fetch('upload-agent-photo.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Photo uploaded successfully!');
            location.reload();
        } else {
            alert('Error uploading photo: ' + data.error);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error uploading photo');
    });
});
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
