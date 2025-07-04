<?php
session_start();
require_once '../config/database.php';

// Check if agent is logged in
if (!isset($_SESSION['agent_logged_in'])) {
    header('Location: login.php');
    exit();
}

$agent_id = $_SESSION['agent_id'];
$success_message = '';
$error_message = '';

// Check for test message
if (isset($_SESSION['test_success'])) {
    $success_message = $_SESSION['test_success'];
    unset($_SESSION['test_success']);
}

if (isset($_SESSION['test_error'])) {
    $error_message = $_SESSION['test_error'];
    unset($_SESSION['test_error']);
}

try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $first_name = trim($_POST['first_name']);
        $last_name = trim($_POST['last_name']);
        $email = trim($_POST['email']);
        $phone = trim($_POST['phone']);
        $bio = trim($_POST['bio']);
        
        // Validate input
        if (empty($first_name) || empty($last_name) || empty($email)) {
            $error_message = 'First name, last name, and email are required.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error_message = 'Please enter a valid email address.';
        } else {
            // Check if email is already taken by another agent
            $stmt = $pdo->prepare("SELECT id FROM admin_users WHERE email = ? AND id != ?");
            $stmt->execute([$email, $agent_id]);
            if ($stmt->fetch()) {
                $error_message = 'This email is already in use by another agent.';
            } else {
                // Update agent information
                $stmt = $pdo->prepare("
                    UPDATE admin_users 
                    SET first_name = ?, last_name = ?, email = ?, phone = ?, bio = ?, updated_at = NOW()
                    WHERE id = ?
                ");
                $result = $stmt->execute([$first_name, $last_name, $email, $phone, $bio, $agent_id]);
                
                if ($result) {
                    $success_message = 'Profile updated successfully!';
                } else {
                    $error_message = 'Error updating profile. Please try again.';
                }
            }
        }
    }
    
    // Get current agent information
    $stmt = $pdo->prepare("SELECT * FROM admin_users WHERE id = ?");
    $stmt->execute([$agent_id]);
    $agent = $stmt->fetch();
    
    if (!$agent) {
        session_destroy();
        header('Location: login.php');
        exit();
    }
    
} catch (PDOException $e) {
    $error_message = 'Database error: ' . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - ConnectPro Agency</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../css/enhancements.css" rel="stylesheet">
    <style>
        /* Ensure alert close buttons work properly */
        .alert-dismissible .btn-close {
            position: absolute;
            top: 0;
            right: 0;
            z-index: 2;
            padding: 1.25rem 1rem;
            background: transparent;
            border: 0;
            opacity: 0.5;
            cursor: pointer;
        }
        
        .alert-dismissible .btn-close:hover {
            opacity: 1;
        }
        
        .alert-dismissible .btn-close:focus {
            opacity: 1;
            outline: none;
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
        }
        
        .alert.fade {
            transition: opacity 0.15s linear;
        }
        
        .alert.fade:not(.show) {
            opacity: 0;
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
                        <a class="nav-link" href="bookings.php"><i class="fas fa-calendar-alt"></i> Bookings</a>
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
                            <li><a class="dropdown-item active" href="profile.php"><i class="fas fa-user"></i> Profile</a></li>
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
            <div class="col-md-8 mx-auto">
                <div class="card">
                    <div class="card-header">
                        <h4 class="mb-0"><i class="fas fa-user"></i> My Profile</h4>
                    </div>
                    <div class="card-body">
                        <?php if ($success_message): ?>
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($error_message): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="fas fa-exclamation-triangle"></i> <?php echo $error_message; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>

                        <form method="POST">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="first_name" class="form-label">First Name *</label>
                                        <input type="text" class="form-control" id="first_name" name="first_name" 
                                               value="<?php echo htmlspecialchars($agent['first_name']); ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="last_name" class="form-label">Last Name *</label>
                                        <input type="text" class="form-control" id="last_name" name="last_name" 
                                               value="<?php echo htmlspecialchars($agent['last_name']); ?>" required>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="email" class="form-label">Email Address *</label>
                                <input type="email" class="form-control" id="email" name="email" 
                                       value="<?php echo htmlspecialchars($agent['email']); ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="phone" class="form-label">Phone Number</label>
                                <input type="tel" class="form-control" id="phone" name="phone" 
                                       value="<?php echo htmlspecialchars($agent['phone']); ?>">
                            </div>
                            
                            <div class="mb-3">
                                <label for="bio" class="form-label">Bio</label>
                                <textarea class="form-control" id="bio" name="bio" rows="4" 
                                          placeholder="Tell us about yourself..."><?php echo htmlspecialchars($agent['bio']); ?></textarea>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Member Since</label>
                                        <input type="text" class="form-control" readonly 
                                               value="<?php echo date('F j, Y', strtotime($agent['created_at'])); ?>">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Role</label>
                                        <input type="text" class="form-control" readonly 
                                               value="<?php echo ucfirst($agent['role']); ?>">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <a href="dashboard.php" class="btn btn-secondary me-md-2">
                                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Update Profile
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Profile Picture Section -->
                <div class="card mt-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-camera"></i> Profile Picture</h5>
                    </div>
                    <div class="card-body text-center">
                        <div class="mb-3">
                            <?php if (!empty($agent['profile_picture'])): ?>
                                <img src="../<?php echo htmlspecialchars($agent['profile_picture']); ?>" 
                                     alt="Profile Picture" class="rounded-circle" width="150" height="150" id="current-profile-picture">
                            <?php else: ?>
                                <div class="avatar-placeholder rounded-circle mx-auto" 
                                     style="width: 150px; height: 150px; background: #007bff; color: white; display: flex; align-items: center; justify-content: center; font-size: 3rem;" id="current-profile-picture">
                                    <?php echo strtoupper(substr($agent['first_name'], 0, 1)); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="mb-3">
                            <form id="profile-picture-form" enctype="multipart/form-data">
                                <div class="mb-3">
                                    <input type="file" class="form-control" id="profile-picture-input" 
                                           accept="image/*" name="profile_image">
                                </div>
                                <button type="submit" class="btn btn-primary" id="upload-btn">
                                    <i class="fas fa-upload"></i> Upload New Picture
                                </button>
                            </form>
                        </div>
                        <div id="upload-message" class="mt-3"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Ensure Bootstrap alerts work properly
        document.addEventListener('DOMContentLoaded', function() {
            // Debug information
            console.log('Bootstrap loaded:', typeof bootstrap !== 'undefined');
            console.log('jQuery loaded:', typeof $ !== 'undefined');
            
            // Get all close buttons
            const closeButtons = document.querySelectorAll('.btn-close');
            console.log('Close buttons found:', closeButtons.length);
            
            // Manually initialize alert close functionality
            closeButtons.forEach(function(button) {
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    console.log('Close button clicked manually');
                    
                    const alert = this.closest('.alert');
                    if (alert) {
                        // Add fade out animation
                        alert.classList.remove('show');
                        alert.classList.add('fade');
                        
                        // Remove element after animation
                        setTimeout(function() {
                            alert.remove();
                        }, 150);
                    }
                });
            });
            
            // Alternative: Use Bootstrap's Alert class
            if (typeof bootstrap !== 'undefined' && bootstrap.Alert) {
                closeButtons.forEach(function(button) {
                    const alert = button.closest('.alert');
                    if (alert) {
                        new bootstrap.Alert(alert);
                    }
                });
            }
            
            // Profile picture upload functionality
            const profilePictureForm = document.getElementById('profile-picture-form');
            const profilePictureInput = document.getElementById('profile-picture-input');
            const uploadBtn = document.getElementById('upload-btn');
            const uploadMessage = document.getElementById('upload-message');
            const currentProfilePicture = document.getElementById('current-profile-picture');
            
            if (profilePictureForm) {
                profilePictureForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    
                    const file = profilePictureInput.files[0];
                    if (!file) {
                        showMessage('Please select a file to upload.', 'danger');
                        return;
                    }
                    
                    // Validate file size (2MB max)
                    if (file.size > 2 * 1024 * 1024) {
                        showMessage('File size too large. Maximum 2MB allowed.', 'danger');
                        return;
                    }
                    
                    // Validate file type
                    const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
                    if (!allowedTypes.includes(file.type)) {
                        showMessage('Invalid file type. Only JPG, PNG, GIF, and WebP are allowed.', 'danger');
                        return;
                    }
                    
                    // Show loading state
                    uploadBtn.disabled = true;
                    uploadBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Uploading...';
                    
                    // Create form data
                    const formData = new FormData();
                    formData.append('profile_image', file);
                    
                    // Upload file
                    fetch('upload-profile-picture.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            showMessage(data.message, 'success');
                            
                            // Update profile picture display
                            if (currentProfilePicture.tagName === 'IMG') {
                                currentProfilePicture.src = '../' + data.image_path + '?v=' + Date.now();
                            } else {
                                // Replace placeholder with image
                                const newImg = document.createElement('img');
                                newImg.src = '../' + data.image_path + '?v=' + Date.now();
                                newImg.alt = 'Profile Picture';
                                newImg.className = 'rounded-circle';
                                newImg.width = 150;
                                newImg.height = 150;
                                newImg.id = 'current-profile-picture';
                                currentProfilePicture.parentNode.replaceChild(newImg, currentProfilePicture);
                            }
                            
                            // Clear file input
                            profilePictureInput.value = '';
                        } else {
                            showMessage(data.error, 'danger');
                        }
                    })
                    .catch(error => {
                        showMessage('Error uploading file: ' + error.message, 'danger');
                    })
                    .finally(() => {
                        // Restore button state
                        uploadBtn.disabled = false;
                        uploadBtn.innerHTML = '<i class="fas fa-upload"></i> Upload New Picture';
                    });
                });
            }
            
            function showMessage(message, type) {
                uploadMessage.innerHTML = `
                    <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                        ${message}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                `;
                
                // Auto-dismiss after 5 seconds
                setTimeout(() => {
                    const alert = uploadMessage.querySelector('.alert');
                    if (alert) {
                        alert.classList.remove('show');
                        setTimeout(() => alert.remove(), 150);
                    }
                }, 5000);
            }
        });
    </script>
</body>
</html>
