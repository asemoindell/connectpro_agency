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

// Handle template updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $template_type = $_POST['template_type'];
    $subject = $_POST['subject'];
    $content = $_POST['content'];
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    // Check if template exists
    $stmt = $db->prepare("SELECT id FROM email_templates WHERE template_type = ?");
    $stmt->execute([$template_type]);
    $existing = $stmt->fetch();
    
    if ($existing) {
        // Update existing template
        $stmt = $db->prepare("UPDATE email_templates SET subject = ?, content = ?, is_active = ?, updated_at = NOW(), updated_by = ? WHERE template_type = ?");
        $stmt->execute([$subject, $content, $is_active, $_SESSION['admin_id'], $template_type]);
        $success_message = "Email template updated successfully!";
    } else {
        // Create new template
        $stmt = $db->prepare("INSERT INTO email_templates (template_type, subject, content, is_active, created_by, updated_by) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$template_type, $subject, $content, $is_active, $_SESSION['admin_id'], $_SESSION['admin_id']]);
        $success_message = "Email template created successfully!";
    }
}

// Get all email templates
$stmt = $db->query("SELECT * FROM email_templates ORDER BY template_type");
$templates = $stmt->fetchAll();

// Convert to associative array for easier access
$template_data = [];
foreach ($templates as $template) {
    $template_data[$template['template_type']] = $template;
}

// Default templates if none exist
$default_templates = [
    'user_registration' => [
        'subject' => 'Welcome to ConnectPro Agency!',
        'content' => 'Hello {{user_name}},

Thank you for registering with ConnectPro Agency! Your account has been created successfully.

Your registration details:
- Name: {{user_name}}
- Email: {{user_email}}
- Registration Date: {{registration_date}}

Your account is currently pending approval. Once approved, you will receive an email confirmation and can start booking our services.

If you have any questions, please contact our support team.

Best regards,
ConnectPro Agency Team'
    ],
    'user_login' => [
        'subject' => 'Login Notification - ConnectPro Agency',
        'content' => 'Hello {{user_name}},

We noticed a login to your ConnectPro Agency account.

Login Details:
- Time: {{login_time}}
- IP Address: {{ip_address}}
- Location: {{location}}
- Device: {{user_agent}}

If this wasn\'t you, please contact our support team immediately.

Best regards,
ConnectPro Agency Team'
    ],
    'user_approval' => [
        'subject' => 'Account Approved - Welcome to ConnectPro Agency!',
        'content' => 'Hello {{user_name}},

Great news! Your ConnectPro Agency account has been approved!

You can now:
- Book services from our catalog
- Track your booking status
- Communicate with our agents
- Access your dashboard

Login to your account: {{login_url}}

Admin Notes: {{admin_notes}}

Welcome aboard!

Best regards,
ConnectPro Agency Team'
    ],
    'user_rejection' => [
        'subject' => 'Account Registration - ConnectPro Agency',
        'content' => 'Hello {{user_name}},

Thank you for your interest in ConnectPro Agency. After reviewing your registration, we regret to inform you that we cannot approve your account at this time.

Reason: {{rejection_reason}}

If you have any questions or would like to reapply, please contact our support team.

Best regards,
ConnectPro Agency Team'
    ],
    'booking_confirmation' => [
        'subject' => 'Booking Confirmation - {{booking_reference}}',
        'content' => 'Hello {{client_name}},

Your service booking has been confirmed!

Booking Details:
- Reference: {{booking_reference}}
- Service: {{service_name}}
- Total Amount: ${{total_amount}}
- Status: {{booking_status}}
- Approval Deadline: {{approval_deadline}}

We will review your booking and contact you within 3-4 business days.

Track your booking: {{booking_url}}

Best regards,
ConnectPro Agency Team'
    ],
    'booking_approval' => [
        'subject' => 'Booking Approved - {{booking_reference}}',
        'content' => 'Hello {{client_name}},

Excellent news! Your booking has been approved.

Booking Details:
- Reference: {{booking_reference}}
- Service: {{service_name}}
- Agent: {{agent_name}}
- Final Price: ${{final_price}}

Next Steps:
{{next_steps}}

Payment Link: {{payment_url}}

Best regards,
ConnectPro Agency Team'
    ],
    'payment_confirmation' => [
        'subject' => 'Payment Received - {{booking_reference}}',
        'content' => 'Hello {{client_name}},

Your payment has been received and processed successfully!

Payment Details:
- Amount: ${{payment_amount}}
- Method: {{payment_method}}
- Transaction ID: {{transaction_id}}
- Date: {{payment_date}}

Your service will now proceed as scheduled.

Best regards,
ConnectPro Agency Team'
    ]
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Templates - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .template-card {
            border-radius: 10px;
            margin-bottom: 2rem;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        
        .template-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1rem;
            border-radius: 10px 10px 0 0;
        }
        
        .variable-tag {
            background: #e9ecef;
            color: #495057;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-family: monospace;
            font-size: 0.8rem;
            margin: 0.1rem;
            display: inline-block;
        }
        
        .variables-section {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 5px;
            margin-bottom: 1rem;
        }
        
        .preview-section {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 5px;
            border-left: 4px solid #007bff;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-2 bg-dark text-white min-vh-100">
                <div class="p-3">
                    <h5><i class="fas fa-envelope"></i> Email Templates</h5>
                    <hr>
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link text-white" href="dashboard.php">
                                <i class="fas fa-dashboard"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-white" href="users.php">
                                <i class="fas fa-users"></i> Users
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-white" href="services.php">
                                <i class="fas fa-cog"></i> Services
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-white active" href="email-templates.php">
                                <i class="fas fa-envelope"></i> Email Templates
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-white" href="bookings.php">
                                <i class="fas fa-calendar"></i> Bookings
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
            
            <!-- Main Content -->
            <div class="col-md-10">
                <div class="p-4">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h2><i class="fas fa-envelope"></i> Email Templates Management</h2>
                        <button class="btn btn-primary" onclick="testEmailSystem()">
                            <i class="fas fa-paper-plane"></i> Test Email System
                        </button>
                    </div>
                    
                    <?php if (isset($success_message)): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        <strong>Available Variables:</strong> Use these placeholders in your templates:
                        <span class="variable-tag">{{user_name}}</span>
                        <span class="variable-tag">{{user_email}}</span>
                        <span class="variable-tag">{{booking_reference}}</span>
                        <span class="variable-tag">{{service_name}}</span>
                        <span class="variable-tag">{{agent_name}}</span>
                        <span class="variable-tag">{{total_amount}}</span>
                        <span class="variable-tag">{{login_time}}</span>
                        <span class="variable-tag">{{ip_address}}</span>
                        <span class="variable-tag">{{location}}</span>
                    </div>
                    
                    <!-- Email Templates -->
                    <?php foreach ($default_templates as $type => $default): ?>
                        <div class="template-card">
                            <div class="template-header">
                                <h5><i class="fas fa-envelope"></i> <?php echo ucwords(str_replace('_', ' ', $type)); ?></h5>
                                <small>Template for <?php echo str_replace('_', ' ', $type); ?> notifications</small>
                            </div>
                            <div class="card-body">
                                <form method="POST">
                                    <input type="hidden" name="template_type" value="<?php echo $type; ?>">
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Subject Line</label>
                                                <input type="text" name="subject" class="form-control" required
                                                       value="<?php echo htmlspecialchars($template_data[$type]['subject'] ?? $default['subject']); ?>">
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label class="form-label">Email Content</label>
                                                <textarea name="content" class="form-control" rows="15" required><?php echo htmlspecialchars($template_data[$type]['content'] ?? $default['content']); ?></textarea>
                                            </div>
                                            
                                            <div class="mb-3 form-check">
                                                <input type="checkbox" name="is_active" class="form-check-input" 
                                                       <?php echo (!isset($template_data[$type]) || $template_data[$type]['is_active']) ? 'checked' : ''; ?>>
                                                <label class="form-check-label">Active (send emails for this event)</label>
                                            </div>
                                            
                                            <button type="submit" class="btn btn-success">
                                                <i class="fas fa-save"></i> Save Template
                                            </button>
                                        </div>
                                        
                                        <div class="col-md-6">
                                            <div class="variables-section">
                                                <h6>Available Variables for this Template:</h6>
                                                <?php
                                                $variables = [];
                                                switch($type) {
                                                    case 'user_registration':
                                                    case 'user_login':
                                                    case 'user_approval':
                                                    case 'user_rejection':
                                                        $variables = ['{{user_name}}', '{{user_email}}', '{{registration_date}}', '{{login_time}}', '{{ip_address}}', '{{location}}', '{{user_agent}}', '{{admin_notes}}', '{{rejection_reason}}', '{{login_url}}'];
                                                        break;
                                                    case 'booking_confirmation':
                                                    case 'booking_approval':
                                                        $variables = ['{{client_name}}', '{{booking_reference}}', '{{service_name}}', '{{agent_name}}', '{{total_amount}}', '{{booking_status}}', '{{approval_deadline}}', '{{final_price}}', '{{next_steps}}', '{{payment_url}}', '{{booking_url}}'];
                                                        break;
                                                    case 'payment_confirmation':
                                                        $variables = ['{{client_name}}', '{{booking_reference}}', '{{payment_amount}}', '{{payment_method}}', '{{transaction_id}}', '{{payment_date}}'];
                                                        break;
                                                }
                                                
                                                foreach($variables as $var) {
                                                    echo '<span class="variable-tag">' . $var . '</span> ';
                                                }
                                                ?>
                                            </div>
                                            
                                            <div class="preview-section">
                                                <h6>Preview:</h6>
                                                <div id="preview-<?php echo $type; ?>">
                                                    <strong>Subject:</strong> <?php echo htmlspecialchars($template_data[$type]['subject'] ?? $default['subject']); ?><br><br>
                                                    <div style="white-space: pre-line;"><?php echo htmlspecialchars($template_data[$type]['content'] ?? $default['content']); ?></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    
                    <!-- Email Statistics -->
                    <div class="card mt-4">
                        <div class="card-header">
                            <h5>Email Statistics</h5>
                        </div>
                        <div class="card-body">
                            <?php
                            // Get email statistics
                            $email_stats = [];
                            try {
                                $stmt = $db->query("SELECT template_type, COUNT(*) as sent_count FROM email_logs GROUP BY template_type");
                                $email_stats = $stmt->fetchAll();
                            } catch (Exception $e) {
                                echo "<p class='text-muted'>Email statistics not available. Email logs table may not exist.</p>";
                            }
                            ?>
                            
                            <?php if ($email_stats): ?>
                                <div class="row">
                                    <?php foreach ($email_stats as $stat): ?>
                                        <div class="col-md-3 mb-3">
                                            <div class="card">
                                                <div class="card-body text-center">
                                                    <h4><?php echo $stat['sent_count']; ?></h4>
                                                    <p><?php echo ucwords(str_replace('_', ' ', $stat['template_type'])); ?></p>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <p class="text-muted">No email statistics available yet.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function testEmailSystem() {
            if (confirm('Send a test email to verify the email system is working?')) {
                fetch('test-email.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({action: 'test'})
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Test email sent successfully!');
                    } else {
                        alert('Test email failed: ' + data.message);
                    }
                })
                .catch(error => {
                    alert('Error testing email system: ' + error);
                });
            }
        }
    </script>
</body>
</html>
