<?php
session_start();
require_once '../config/database.php';

$error_message = '';
$success_message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstName = $_POST['firstName'] ?? '';
    $lastName = $_POST['lastName'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirmPassword'] ?? '';
    $role = $_POST['role'] ?? 'content-admin';
    
    if ($firstName && $lastName && $email && $password && $confirmPassword) {
        if ($password !== $confirmPassword) {
            $error_message = 'Passwords do not match';
        } elseif (strlen($password) < 6) {
            $error_message = 'Password must be at least 6 characters long';
        } else {
            $database = new Database();
            $db = $database->getConnection();
            
            if ($db) {
                // Check if email already exists
                $check_stmt = $db->prepare("SELECT id FROM admin_users WHERE email = ?");
                $check_stmt->execute([$email]);
                
                if ($check_stmt->fetch()) {
                    $error_message = 'Email already exists';
                } else {
                    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                    
                    $stmt = $db->prepare("INSERT INTO admin_users (first_name, last_name, email, password, role) VALUES (?, ?, ?, ?, ?)");
                    if ($stmt->execute([$firstName, $lastName, $email, $hashedPassword, $role])) {
                        $success_message = 'Admin account created successfully! You can now login.';
                    } else {
                        $error_message = 'Failed to create account. Please try again.';
                    }
                }
            } else {
                $error_message = 'Database connection failed';
            }
        }
    } else {
        $error_message = 'Please fill in all fields';
    }
}

// Redirect if already logged in
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header('Location: dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register Admin - ConnectPro Agency</title>
    <link rel="stylesheet" href="../css/admin.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="auth-body">
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <div class="auth-logo">
                    <i class="fas fa-network-wired"></i>
                    <span>ConnectPro Admin</span>
                </div>
                <h2>Create Admin Account</h2>
                <p>Register as a new administrator</p>
                
                <?php if ($error_message): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-circle"></i>
                        <?php echo htmlspecialchars($error_message); ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($success_message): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i>
                        <?php echo htmlspecialchars($success_message); ?>
                    </div>
                <?php endif; ?>
            </div>

            <form method="POST" class="auth-form">
                <div class="form-row">
                    <div class="form-group">
                        <label for="firstName">First Name</label>
                        <div class="input-wrapper">
                            <i class="fas fa-user"></i>
                            <input type="text" id="firstName" name="firstName" placeholder="First name" 
                                   value="<?php echo htmlspecialchars($_POST['firstName'] ?? ''); ?>" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="lastName">Last Name</label>
                        <div class="input-wrapper">
                            <i class="fas fa-user"></i>
                            <input type="text" id="lastName" name="lastName" placeholder="Last name" 
                                   value="<?php echo htmlspecialchars($_POST['lastName'] ?? ''); ?>" required>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="email">Email Address</label>
                    <div class="input-wrapper">
                        <i class="fas fa-envelope"></i>
                        <input type="email" id="email" name="email" placeholder="Enter your email" 
                               value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="role">Admin Role</label>
                    <div class="input-wrapper">
                        <i class="fas fa-user-shield"></i>
                        <select id="role" name="role" required>
                            <option value="">Select role</option>
                            <option value="content-admin" <?php echo ($_POST['role'] ?? '') === 'content-admin' ? 'selected' : ''; ?>>Content Admin</option>
                            <option value="service-admin" <?php echo ($_POST['role'] ?? '') === 'service-admin' ? 'selected' : ''; ?>>Service Admin</option>
                            <option value="super-admin" <?php echo ($_POST['role'] ?? '') === 'super-admin' ? 'selected' : ''; ?>>Super Admin</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="input-wrapper">
                        <i class="fas fa-lock"></i>
                        <input type="password" id="password" name="password" placeholder="Create password" required>
                        <button type="button" class="password-toggle" onclick="togglePassword('password')">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>

                <div class="form-group">
                    <label for="confirmPassword">Confirm Password</label>
                    <div class="input-wrapper">
                        <i class="fas fa-lock"></i>
                        <input type="password" id="confirmPassword" name="confirmPassword" placeholder="Confirm password" required>
                        <button type="button" class="password-toggle" onclick="togglePassword('confirmPassword')">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>

                <div class="form-options">
                    <label class="checkbox-label">
                        <input type="checkbox" id="terms" name="terms" required>
                        <span class="checkmark"></span>
                        I agree to the <a href="#">Terms of Service</a>
                    </label>
                </div>

                <button type="submit" class="btn btn-primary">Create Account</button>
            </form>

            <div class="auth-footer">
                <p>Already have an account? <a href="login.php">Sign in</a></p>
                <p><a href="../index.php">← Back to Website</a></p>
            </div>
        </div>
    </div>

    <script>
        // Simple password toggle function
        function togglePassword(inputId) {
            const input = document.getElementById(inputId);
            const button = input.parentElement.querySelector('.password-toggle');
            const icon = button.querySelector('i');
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.className = 'fas fa-eye-slash';
            } else {
                input.type = 'password';
                icon.className = 'fas fa-eye';
            }
        }
    </script>
</body>
</html>
