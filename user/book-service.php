<?php
session_start();
require_once '../config/database.php';
require_once 'includes/user-helpers.php';
require_once '../includes/EmailNotification.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$database = new Database();
$db = $database->getConnection();
$emailNotification = new EmailNotification($db);

$error_message = '';
$success_message = '';
$booking_reference = '';
$user_id = $_SESSION['user_id'];

// Get user info
$user_stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$user_stmt->execute([$user_id]);
$user_info = $user_stmt->fetch();

// Handle booking submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $service_id = $_POST['service_id'] ?? '';
    $selected_agent_id = $_POST['selected_agent_id'] ?? '';
    $service_details = $_POST['service_details'] ?? '';
    $urgency_level = $_POST['urgency_level'] ?? 'medium';
    
    if ($service_id && $service_details) {
        // Generate unique booking reference
        $booking_reference = 'CP' . date('Y') . strtoupper(substr(uniqid(), -6));
        
        // Get service details for pricing
        $service_stmt = $db->prepare("SELECT * FROM services_enhanced WHERE id = ? AND status = 'active'");
        $service_stmt->execute([$service_id]);
        $service = $service_stmt->fetch();
        
        if ($service) {
            // Use selected agent or default assigned agent
            $final_agent_id = $selected_agent_id ?: $service['assigned_agent_id'];
            
            // Calculate approval deadline (3-4 days)
            $approval_deadline = date('Y-m-d H:i:s', strtotime('+4 days'));
            
            // Calculate pricing based on service settings
            $quoted_price = $service['base_price'];
            $agent_fee = $service['enable_agent_fee'] ? $service['agent_fee'] : 0;
            $processing_fee = $service['enable_processing_fee'] ? $service['processing_fee'] : 0;
            $subtotal = $quoted_price + $agent_fee + $processing_fee;
            
            // Calculate VAT and Tax
            $vat_amount = $service['enable_vat'] ? ($subtotal * $service['vat_rate'] / 100) : 0;
            $tax_amount = $service['enable_tax'] ? ($subtotal * $service['tax_rate'] / 100) : 0;
            $total_amount = $subtotal + $vat_amount + $tax_amount;
            
            // Insert booking with selected agent
            $stmt = $db->prepare("INSERT INTO service_bookings 
                (booking_reference, service_id, user_id, client_name, client_email, client_phone, 
                 service_details, urgency_level, status, quoted_price, total_amount, 
                 approval_deadline, assigned_admin_id, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'waiting_approval', ?, ?, ?, ?, NOW())");
            
            $result = $stmt->execute([
                $booking_reference,
                $service_id,
                $user_id,
                $user_info['first_name'] . ' ' . $user_info['last_name'],
                $user_info['email'],
                $user_info['phone'],
                $service_details,
                $urgency_level,
                $quoted_price,
                $total_amount,
                $approval_deadline,
                $final_agent_id
            ]);
            
            if ($result) {
                $success_message = "Booking submitted successfully! Reference: " . $booking_reference;
                
                // Send notification email
                $emailNotification->sendBookingConfirmation(
                    $user_info['email'],
                    $user_info['first_name'],
                    $booking_reference,
                    $service['title'],
                    $total_amount
                );
            } else {
                $error_message = "Failed to submit booking. Please try again.";
            }
        }
    } else {
        $error_message = "Please fill in all required fields.";
    }
}

// Get all active services
$services_stmt = $db->prepare("SELECT * FROM services_enhanced WHERE status = 'active' ORDER BY category, title");
$services_stmt->execute();
$services = $services_stmt->fetchAll();

// Group services by category
$grouped_services = [];
foreach ($services as $service) {
    $grouped_services[$service['category']][] = $service;
}

// Get all active agents
$agents_stmt = $db->prepare("SELECT id, first_name, last_name, email, role FROM admin_users WHERE status = 'active' ORDER BY first_name, last_name");
$agents_stmt->execute();
$available_agents = $agents_stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book a Service - ConnectPro Agency</title>
    <link rel="stylesheet" href="../css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .booking-container {
            max-width: 900px;
            margin: 2rem auto;
            padding: 2rem;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        
        .user-welcome {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1.5rem;
            border-radius: 12px;
            margin-bottom: 2rem;
            text-align: center;
        }
        
        .booking-steps {
            display: flex;
            justify-content: center;
            margin-bottom: 2rem;
            gap: 1rem;
        }
        
        .step {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            background: #f8f9fa;
            border-radius: 25px;
            font-weight: 500;
            color: #6c757d;
            transition: all 0.3s ease;
        }
        
        .step.active {
            background: #667eea;
            color: white;
        }
        
        .step.completed {
            background: #28a745;
            color: white;
        }
        
        .service-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .service-card {
            border: 2px solid #e9ecef;
            border-radius: 12px;
            padding: 1.5rem;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            background: white;
        }
        
        .service-card:hover {
            border-color: #667eea;
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.15);
        }
        
        .service-card.selected {
            border-color: #667eea;
            background: #f8f9ff;
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.2);
        }
        
        .agent-selection {
            display: none;
            margin: 2rem 0;
            padding: 2rem;
            background: #f8f9fa;
            border-radius: 12px;
            border: 1px solid #e9ecef;
        }
        
        .agent-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
            margin-top: 1rem;
        }
        
        .agent-card {
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            background: white;
            text-align: center;
        }
        
        .agent-card:hover {
            border-color: #667eea;
            transform: translateY(-1px);
        }
        
        .agent-card.selected {
            border-color: #667eea;
            background: #f8f9ff;
        }
        
        .agent-avatar {
            width: 60px;
            height: 60px;
            background: #667eea;
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 1.2rem;
            margin: 0 auto 1rem;
        }
        
        .default-agent-note {
            background: #e3f2fd;
            border: 1px solid #bbdefb;
            border-radius: 8px;
            padding: 1rem;
            margin-top: 1rem;
            color: #1976d2;
        }
        
        .form-section {
            display: none;
            margin-top: 2rem;
        }
        
        .form-section.active {
            display: block;
            animation: fadeInUp 0.3s ease-out;
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: #333;
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 1rem;
        }
        
        .form-group textarea {
            height: 120px;
            resize: vertical;
        }
        
        .urgency-options {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 0.5rem;
        }
        
        .urgency-option {
            text-align: center;
            padding: 0.75rem;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .urgency-option:hover {
            border-color: #667eea;
            background: #f8f9ff;
        }
        
        .urgency-option.selected {
            border-color: #667eea;
            background: #667eea;
            color: white;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 1rem 2rem;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            width: 100%;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
        }
        
        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 2rem;
        }
        
        .alert-success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }
        
        .alert-error {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
        
        .navigation-buttons {
            display: flex;
            justify-content: space-between;
            margin-top: 2rem;
            gap: 1rem;
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn-secondary:hover {
            background: #5a6268;
        }
    </style>
</head>
<body>
    <div class="booking-container">
        <div class="user-welcome">
            <h1><i class="fas fa-user-circle"></i> Welcome, <?php echo htmlspecialchars($user_info['first_name']); ?>!</h1>
            <p>Book your preferred service and choose your agent</p>
        </div>
        
        <!-- Booking Steps -->
        <div class="booking-steps">
            <div class="step active" id="step1">
                <i class="fas fa-list"></i>
                <span>Select Service</span>
            </div>
            <div class="step" id="step2">
                <i class="fas fa-user-tie"></i>
                <span>Choose Agent</span>
            </div>
            <div class="step" id="step3">
                <i class="fas fa-edit"></i>
                <span>Service Details</span>
            </div>
        </div>
        
        <?php if ($error_message): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($success_message): ?>
            <div class="alert alert-success">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success_message); ?>
                        <br><a href="dashboard.php" style="color: #155724; text-decoration: underline;">Return to Dashboard</a>
                    </div>
                    <div>
                        <a href="chat.php" class="btn btn-primary btn-sm">
                            <i class="fas fa-comments"></i> Start Chat with Agent
                        </a>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        
        <form method="POST" id="bookingForm">
            <!-- Step 1: Service Selection -->
            <div id="serviceSection" class="form-section active">
                <h3><i class="fas fa-list"></i> Select Your Service</h3>
                
                <?php foreach ($grouped_services as $category => $category_services): ?>
                    <div class="category-section">
                        <h4 style="color: #667eea; margin: 2rem 0 1rem 0;"><?php echo htmlspecialchars($category); ?></h4>
                        <div class="service-grid">
                            <?php foreach ($category_services as $service): ?>
                                <div class="service-card" onclick="selectService(<?php echo $service['id']; ?>, this)">
                                    <h5><?php echo htmlspecialchars($service['title']); ?></h5>
                                    <p><?php echo htmlspecialchars($service['short_description'] ?: substr($service['description'], 0, 100) . '...'); ?></p>
                                    <div class="service-price" style="font-weight: bold; color: #667eea; margin-top: 1rem;">
                                        From $<?php echo formatCurrency($service['base_price']); ?>
                                    </div>
                                    <div class="service-agent" style="font-size: 0.9rem; color: #666; margin-top: 0.5rem;">
                                        <?php if ($service['assigned_agent_id']): ?>
                                            <i class="fas fa-user"></i> Default Agent Available
                                        <?php else: ?>
                                            <i class="fas fa-users"></i> Team Service
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
                
                <input type="hidden" name="service_id" id="selectedServiceId" required>
                
                <div class="navigation-buttons">
                    <div></div>
                    <button type="button" class="btn-primary" onclick="proceedToAgentSelection()">
                        Next: Choose Agent <i class="fas fa-arrow-right"></i>
                    </button>
                </div>
            </div>
            
            <!-- Step 2: Agent Selection -->
            <div id="agentSection" class="form-section">
                <h3><i class="fas fa-user-tie"></i> Choose Your Preferred Agent</h3>
                
                <div class="default-agent-note">
                    <i class="fas fa-info-circle"></i>
                    <strong>Note:</strong> You can choose any available agent or leave the default selection for automatic assignment.
                </div>
                
                <div class="agent-grid">
                    <?php foreach ($available_agents as $agent): ?>
                        <div class="agent-card" onclick="selectAgent(<?php echo $agent['id']; ?>, this)">
                            <div class="agent-avatar">
                                <?php echo strtoupper(substr($agent['first_name'], 0, 1) . substr($agent['last_name'], 0, 1)); ?>
                            </div>
                            <h5><?php echo htmlspecialchars($agent['first_name'] . ' ' . $agent['last_name']); ?></h5>
                            <p style="color: #666; font-size: 0.9rem;"><?php echo htmlspecialchars(ucfirst(str_replace('-', ' ', $agent['role']))); ?></p>
                            <p style="color: #888; font-size: 0.8rem;"><?php echo htmlspecialchars($agent['email']); ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <input type="hidden" name="selected_agent_id" id="selectedAgentId">
                
                <div class="navigation-buttons">
                    <button type="button" class="btn-secondary" onclick="backToServiceSelection()">
                        <i class="fas fa-arrow-left"></i> Back
                    </button>
                    <button type="button" class="btn-primary" onclick="proceedToDetails()">
                        Next: Proceed to Payment <i class="fas fa-credit-card"></i>
                    </button>
                </div>
            </div>
            
            <!-- Step 3: Service Details -->
            <div id="detailsSection" class="form-section">
                <h3><i class="fas fa-edit"></i> Service Details</h3>
                
                <div class="form-group">
                    <label for="service_details">Service Details & Requirements *</label>
                    <textarea name="service_details" id="service_details" required 
                              placeholder="Please provide detailed information about what you need..."></textarea>
                </div>
                
                <div class="form-group">
                    <label>Urgency Level</label>
                    <div class="urgency-options">
                        <div class="urgency-option" onclick="selectUrgency('low', this)">
                            <input type="radio" name="urgency_level" value="low" style="display: none;">
                            <div><strong>Low</strong></div>
                            <small>5-7 days</small>
                        </div>
                        <div class="urgency-option selected" onclick="selectUrgency('medium', this)">
                            <input type="radio" name="urgency_level" value="medium" checked style="display: none;">
                            <div><strong>Medium</strong></div>
                            <small>3-4 days</small>
                        </div>
                        <div class="urgency-option" onclick="selectUrgency('high', this)">
                            <input type="radio" name="urgency_level" value="high" style="display: none;">
                            <div><strong>High</strong></div>
                            <small>1-2 days</small>
                        </div>
                        <div class="urgency-option" onclick="selectUrgency('urgent', this)">
                            <input type="radio" name="urgency_level" value="urgent" style="display: none;">
                            <div><strong>Urgent</strong></div>
                            <small>Same day</small>
                        </div>
                    </div>
                </div>
                
                <div class="navigation-buttons">
                    <button type="button" class="btn-secondary" onclick="backToAgentSelection()">
                        <i class="fas fa-arrow-left"></i> Back
                    </button>
                    <button type="submit" class="btn-primary">
                        <i class="fas fa-check"></i> Submit Booking
                    </button>
                </div>
            </div>
        </form>
    </div>
    
    <script>
        let currentStep = 1;
        let selectedServiceId = null;
        let selectedAgentId = null;
        
        function selectService(serviceId, element) {
            // Remove selected class from all service cards
            document.querySelectorAll('.service-card').forEach(card => {
                card.classList.remove('selected');
            });
            
            // Add selected class to clicked card
            element.classList.add('selected');
            
            // Store selection
            selectedServiceId = serviceId;
            document.getElementById('selectedServiceId').value = serviceId;
        }
        
        function selectAgent(agentId, element) {
            // Remove selected class from all agent cards
            document.querySelectorAll('.agent-card').forEach(card => {
                card.classList.remove('selected');
            });
            
            // Add selected class to clicked card
            element.classList.add('selected');
            
            // Store selection
            selectedAgentId = agentId;
            document.getElementById('selectedAgentId').value = agentId;
        }
        
        function selectUrgency(urgency, element) {
            // Remove selected class from all urgency options
            document.querySelectorAll('.urgency-option').forEach(option => {
                option.classList.remove('selected');
            });
            
            // Add selected class to clicked option
            element.classList.add('selected');
            
            // Set radio button
            element.querySelector('input[type="radio"]').checked = true;
        }
        
        function proceedToAgentSelection() {
            if (!selectedServiceId) {
                alert('Please select a service first.');
                return;
            }
            
            currentStep = 2;
            updateSteps();
            showSection('agentSection');
        }
        
        function proceedToDetails() {
            // Check if service is selected
            const serviceId = document.getElementById('selectedServiceId').value;
            if (!serviceId) {
                alert('Please select a service first.');
                return;
            }
            
            // Get selected agent
            const selectedAgentId = document.getElementById('selectedAgentId').value;
            
            // Show loading state
            const nextButton = document.querySelector('button[onclick="proceedToDetails()"]');
            const originalText = nextButton.innerHTML;
            nextButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Creating booking...';
            nextButton.disabled = true;
            
            // Create booking with selected agent and redirect to payment
            fetch('create-booking-with-agent.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    service_id: serviceId,
                    selected_agent_id: selectedAgentId,
                    service_details: 'Service booked via agent selection',
                    urgency_level: 'medium'
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Redirect to payment page
                    window.location.href = data.redirect_url;
                } else {
                    alert('Error creating booking: ' + data.error);
                    nextButton.innerHTML = originalText;
                    nextButton.disabled = false;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while creating the booking.');
                nextButton.innerHTML = originalText;
                nextButton.disabled = false;
            });
        }
        
        function backToServiceSelection() {
            currentStep = 1;
            updateSteps();
            showSection('serviceSection');
        }
        
        function backToAgentSelection() {
            currentStep = 2;
            updateSteps();
            showSection('agentSection');
        }
        
        function updateSteps() {
            const steps = document.querySelectorAll('.step');
            steps.forEach((step, index) => {
                step.classList.remove('active', 'completed');
                if (index + 1 < currentStep) {
                    step.classList.add('completed');
                } else if (index + 1 === currentStep) {
                    step.classList.add('active');
                }
            });
        }
        
        function showSection(sectionId) {
            // Hide all sections
            document.querySelectorAll('.form-section').forEach(section => {
                section.classList.remove('active');
            });
            
            // Show target section
            document.getElementById(sectionId).classList.add('active');
        }
        
        // Form validation
        document.getElementById('bookingForm').addEventListener('submit', function(e) {
            const serviceDetails = document.getElementById('service_details').value.trim();
            
            if (!serviceDetails) {
                e.preventDefault();
                alert('Please provide service details.');
                document.getElementById('service_details').focus();
                return;
            }
            
            if (serviceDetails.length < 10) {
                e.preventDefault();
                alert('Please provide more detailed service requirements (at least 10 characters).');
                document.getElementById('service_details').focus();
                return;
            }
        });
    </script>
</body>
</html>
