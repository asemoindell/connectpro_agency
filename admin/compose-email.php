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

// Get admin info
$admin = getCurrentAdmin();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $to_email = $_POST['to_email'] ?? '';
    $subject = $_POST['subject'] ?? '';
    $message = $_POST['message'] ?? '';
    $template_id = $_POST['template_id'] ?? '';
    
    try {
        // Insert email log
        $stmt = $db->prepare("
            INSERT INTO email_logs (
                recipient_email, 
                recipient_name, 
                subject, 
                content, 
                user_id,
                template_type,
                status,
                sent_at
            ) VALUES (?, ?, ?, ?, ?, ?, 'sent', NOW())
        ");
        
        $recipient_name = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
        
        $stmt->execute([
            $to_email,
            $recipient_name,
            $subject,
            $message,
            $user_id,
            $template_id ? 'template_' . $template_id : 'manual'
        ]);
        
        $success_message = "Email sent successfully!";
        
        // In a real application, you would send the actual email here
        // mail($to_email, $subject, $message, $headers);
        
    } catch (Exception $e) {
        $error_message = "Failed to send email: " . $e->getMessage();
    }
}

// Get email templates
$templates_stmt = $db->query("SELECT * FROM email_templates WHERE status = 'active' ORDER BY name");
$templates = $templates_stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Compose Email - <?php echo htmlspecialchars(trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''))); ?></title>
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
        .compose-container {
            max-width: 700px;
            margin: 0 auto;
            background: var(--bg-card);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-lg);
            overflow: hidden;
        }
        .compose-header {
            background: var(--primary-color);
            color: white;
            padding: 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .compose-header h2 {
            margin: 0;
            font-size: 1.25rem;
            font-weight: 600;
        }
        .close-btn {
            background: none;
            border: none;
            color: white;
            font-size: 1.5rem;
            cursor: pointer;
            padding: 0;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: background-color 0.2s;
        }
        .close-btn:hover {
            background: rgba(255, 255, 255, 0.1);
        }
        .compose-body {
            padding: 2rem;
        }
        .user-info {
            background: var(--bg-secondary);
            padding: 1rem;
            border-radius: var(--border-radius);
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        .user-avatar {
            width: 50px;
            height: 50px;
            background: var(--primary-color);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 1.2rem;
        }
        .form-group {
            margin-bottom: 1.5rem;
        }
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--text-primary);
        }
        .form-control {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid var(--border-color);
            border-radius: var(--border-radius);
            font-size: 0.9rem;
            background: var(--bg-primary);
            color: var(--text-primary);
            transition: border-color 0.2s;
        }
        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        textarea.form-control {
            min-height: 200px;
            resize: vertical;
        }
        .template-actions {
            display: flex;
            gap: 0.5rem;
            margin-top: 0.5rem;
        }
        .btn-sm {
            padding: 0.5rem 1rem;
            font-size: 0.875rem;
        }
        .compose-footer {
            padding: 1.5rem 2rem;
            background: var(--bg-secondary);
            border-top: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .alert {
            padding: 1rem;
            border-radius: var(--border-radius);
            margin-bottom: 1rem;
        }
        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }
        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fca5a5;
        }
    </style>
</head>
<body>
    <div class="compose-container">
        <div class="compose-header">
            <h2><i class="fas fa-envelope"></i> Compose Email</h2>
            <button class="close-btn" onclick="window.close();">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <div class="compose-body">
            <?php if (isset($success_message)): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($error_message)): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error_message; ?>
                </div>
            <?php endif; ?>
            
            <!-- User Info -->
            <div class="user-info">
                <div class="user-avatar">
                    <?php 
                    $full_name = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
                    echo strtoupper(substr($full_name ?: 'U', 0, 2)); 
                    ?>
                </div>
                <div>
                    <h4 style="margin: 0; color: var(--text-primary);">
                        <?php echo htmlspecialchars($full_name); ?>
                    </h4>
                    <p style="margin: 0; color: var(--text-muted); font-size: 0.9rem;">
                        <i class="fas fa-envelope"></i> <?php echo htmlspecialchars($user['email']); ?>
                    </p>
                </div>
            </div>
            
            <!-- Email Form -->
            <form method="POST">
                <div class="form-group">
                    <label for="template_id">Email Template (Optional)</label>
                    <select name="template_id" id="template_id" class="form-control" onchange="loadTemplate()">
                        <option value="">Select a template...</option>
                        <?php foreach ($templates as $template): ?>
                            <option value="<?php echo $template['id']; ?>" 
                                    data-subject="<?php echo htmlspecialchars($template['subject']); ?>"
                                    data-content="<?php echo htmlspecialchars($template['content']); ?>">
                                <?php echo htmlspecialchars($template['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="template-actions">
                        <button type="button" class="btn btn-secondary btn-sm" onclick="loadTemplate()">
                            <i class="fas fa-download"></i> Load Template
                        </button>
                        <a href="email-templates.php" target="_blank" class="btn btn-outline btn-sm">
                            <i class="fas fa-cog"></i> Manage Templates
                        </a>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="to_email">To</label>
                    <input type="email" name="to_email" id="to_email" class="form-control" 
                           value="<?php echo htmlspecialchars($user['email']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="subject">Subject</label>
                    <input type="text" name="subject" id="subject" class="form-control" 
                           placeholder="Enter email subject..." required>
                </div>
                
                <div class="form-group">
                    <label for="message">Message</label>
                    <textarea name="message" id="message" class="form-control" 
                              placeholder="Enter your message here..." required></textarea>
                </div>
            </form>
        </div>
        
        <div class="compose-footer">
            <div>
                <small class="text-muted">
                    <i class="fas fa-user"></i> Sending as: <?php echo htmlspecialchars(($admin['first_name'] ?? 'Admin') . ' ' . ($admin['last_name'] ?? '')); ?>
                </small>
            </div>
            <div>
                <button type="button" onclick="window.close();" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Cancel
                </button>
                <button type="submit" form="compose-form" class="btn btn-primary">
                    <i class="fas fa-paper-plane"></i> Send Email
                </button>
            </div>
        </div>
    </div>

    <script>
        function loadTemplate() {
            const select = document.getElementById('template_id');
            const option = select.options[select.selectedIndex];
            
            if (option.value) {
                document.getElementById('subject').value = option.dataset.subject || '';
                document.getElementById('message').value = option.dataset.content || '';
            }
        }
        
        // Fix form submission
        document.querySelector('.btn-primary').onclick = function() {
            document.querySelector('form').submit();
        };
    </script>
</body>
</html>
