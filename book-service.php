<?php
session_start();
require_once 'config/database.php';
require_once 'includes/EmailNotification.php';

$database = new Database();
$db = $database->getConnection();
$emailNotification = new EmailNotification($db);

$error_message = '';
$success_message = '';
$booking_reference = '';

// Handle booking submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $service_id = $_POST['service_id'] ?? '';
    $client_name = $_POST['client_name'] ?? '';
    $client_email = $_POST['client_email'] ?? '';
    $client_phone = $_POST['client_phone'] ?? '';
    $service_details = $_POST['service_details'] ?? '';
    $urgency_level = $_POST['urgency_level'] ?? 'medium';
    
    if ($service_id && $client_name && $client_email && $service_details) {
        // Generate unique booking reference
        $booking_reference = 'CP' . date('Y') . strtoupper(substr(uniqid(), -6));
        
        // Get service details for pricing
        $service_stmt = $db->prepare("SELECT * FROM services WHERE id = ? AND status = 'active'");
        $service_stmt->execute([$service_id]);
        $service = $service_stmt->fetch();
        
        if ($service) {
            // Calculate approval deadline (3-4 days)
            $approval_deadline = date('Y-m-d H:i:s', strtotime('+4 days'));
            
            // Calculate pricing based on service settings
            $user_id = $_SESSION['user_id'] ?? null;
            
            // Use a simple pricing model since detailed pricing columns don't exist
            $quoted_price = 100; // Default base price
            $agent_fee = 0;
            $processing_fee = 0;
            $subtotal = $quoted_price + $agent_fee + $processing_fee;
            
            // No VAT/Tax for now since those columns don't exist
            $vat_amount = 0;
            $tax_amount = 0;
            $total_amount = $subtotal;
            
            // Prepare booking insertion statement
            $stmt = $db->prepare("INSERT INTO service_bookings 
                (booking_reference, service_id, client_name, client_email, client_phone, 
                 service_details, urgency_level, approval_deadline, quoted_price, 
                 agent_fee, total_amount, user_id) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            
            if ($stmt->execute([
                $booking_reference, $service_id, $client_name, $client_email, 
                $client_phone, $service_details, $urgency_level, $approval_deadline,
                $quoted_price, $agent_fee, $total_amount, $user_id
            ])) {
                $booking_id = $db->lastInsertId();
                
                // Update confirmation sent timestamp
                $update_stmt = $db->prepare("UPDATE service_bookings SET confirmation_sent_at = NOW(), status = 'waiting_approval' WHERE id = ?");
                $update_stmt->execute([$booking_id]);
                
                // Send confirmation email
                if ($emailNotification->sendBookingConfirmation($booking_id)) {
                    $success_message = "Booking confirmed! Reference: {$booking_reference}. You'll receive an email confirmation shortly.";
                } else {
                    $success_message = "Booking confirmed! Reference: {$booking_reference}. Email confirmation will be sent shortly.";
                }
                
                // Redirect to crypto payment page
                header("Location: payment/crypto-payment.php?booking_id=$booking_id&method=btc");
                exit();
            } else {
                $error_message = 'Failed to create booking. Please try again.';
            }
        } else {
            $error_message = 'Selected service is not available.';
        }
    } else {
        $error_message = 'Please fill in all required fields.';
    }
}

// Get all active services grouped by category
$services_stmt = $db->prepare("
    SELECT s.*, s.category as category_name
    FROM services s 
    WHERE s.status = 'active'
    ORDER BY s.category, s.title
");
$services_stmt->execute();
$services = $services_stmt->fetchAll();

// Group services by category
$grouped_services = [];
foreach ($services as $service) {
    $grouped_services[$service['category_name']][] = $service;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book a Service - ConnectPro Agency</title>
    <link rel="stylesheet" href="css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .booking-container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 2rem;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        
        .booking-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .booking-header h1 {
            color: #667eea;
            margin-bottom: 0.5rem;
        }
        
        .service-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .service-card {
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
        }
        
        .service-card:hover {
            border-color: #667eea;
            background: #f8f9ff;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.1);
        }
        
        .service-card.selected {
            border-color: #667eea;
            background: #f8f9ff;
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.2);
        }
        
        .service-card.selected::after {
            content: '✓';
            position: absolute;
            top: 10px;
            right: 10px;
            background: #28a745;
            color: white;
            border-radius: 50%;
            width: 25px;
            height: 25px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: bold;
        }
            background: #f8f9ff;
        }
        
        .service-card h4 {
            color: #667eea;
            margin-bottom: 0.5rem;
        }
        
        .service-price {
            font-weight: bold;
            color: #28a745;
            margin-top: 0.5rem;
        }
        
        .booking-form {
            background: #f8f9fa;
            padding: 2rem;
            border-radius: 10px;
            margin-top: 2rem;
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
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }
        
        .urgency-options {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 0.5rem;
        }
        
        .urgency-option {
            text-align: center;
            padding: 0.5rem;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .urgency-option.selected {
            border-color: #667eea;
            background: #f8f9ff;
        }
        
        .urgency-option input {
            display: none;
        }
        
        .btn-book {
            width: 100%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1rem 2rem;
            border: none;
            border-radius: 10px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.3s ease;
        }
        
        .btn-book:hover {
            transform: translateY(-2px);
        }
        
        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
        }
        
        .alert-success {
            background: #d1f2eb;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .category-section {
            margin-bottom: 2rem;
        }
        
        .category-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: #333;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #667eea;
        }
        
        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .urgency-options {
                grid-template-columns: 1fr 1fr;
            }
        }
        
        /* Enhanced Form Validation Styles */
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .form-group input.valid {
            border-color: #28a745;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' width='8' height='8' viewBox='0 0 8 8'%3e%3cpath fill='%2328a745' d='m2.3 6.73.4.4c.2.2.5.2.7 0L6.97 3.6a.5.5 0 0 0-.7-.7L3.7 5.46 2.03 3.8a.5.5 0 0 0-.7.7l.97.93z'/%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right calc(0.375em + 0.1875rem) center;
            background-size: calc(0.75em + 0.375rem) calc(0.75em + 0.375rem);
        }
        
        .form-group input.invalid {
            border-color: #dc3545;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' fill='none' stroke='%23dc3545' viewBox='0 0 12 12'%3e%3ccircle cx='6' cy='6' r='4.5'/%3e%3cpath d='M5.8 5.8l.4.4.4-.4M5.8 6.2l.4-.4.4.4'/%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right calc(0.375em + 0.1875rem) center;
            background-size: calc(0.75em + 0.375rem) calc(0.75em + 0.375rem);
        }
        
        .field-error {
            animation: slideInError 0.3s ease-out;
        }
        
        .field-success {
            animation: slideInSuccess 0.3s ease-out;
        }
        
        @keyframes slideInError {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        @keyframes slideInSuccess {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        /* Enhanced button states */
        .btn-book:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }
        
        .btn-book:disabled:hover {
            transform: none;
        }
        
        /* Loading animation */
        .fa-spinner {
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        
        /* Improved urgency selector */
        .urgency-option:hover {
            border-color: #667eea;
            background: #f8f9ff;
            transform: translateY(-1px);
        }
        
        .urgency-option.selected {
            border-color: #667eea;
            background: #667eea;
            color: white;
        }
        
        /* Agent Section Styles */
        .agent-section {
            margin: 2rem 0;
            animation: slideInUp 0.3s ease-out;
        }
        
        .agent-card {
            display: flex;
            gap: 1rem;
            padding: 1.5rem;
            background: white;
            border: 1px solid #e9ecef;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 1.5rem;
        }
        
        .agent-avatar {
            flex-shrink: 0;
        }
        
        .agent-info {
            flex: 1;
        }
        
        .price-breakdown {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 12px;
            padding: 1.5rem;
        }
        
        .price-breakdown h4 {
            margin: 0 0 1rem 0;
            color: #333;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .price-item {
            display: flex;
            justify-content: space-between;
            padding: 0.5rem 0;
            border-bottom: 1px solid #e9ecef;
        }
        
        .price-item:last-child {
            border-bottom: none;
        }
        
        .price-item.total {
            margin-top: 0.5rem;
            padding-top: 1rem;
            border-top: 2px solid #667eea;
            font-size: 1.1rem;
            font-weight: bold;
        }
        
        @keyframes slideInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
            background: #f8f9ff;
            box-shadow: 0 2px 8px rgba(102, 126, 234, 0.2);
        }
        
        /* Responsive improvements */
        @media (max-width: 768px) {
            .booking-container {
                margin: 1rem;
                padding: 1rem;
            }
            
            .urgency-options {
                grid-template-columns: repeat(2, 1fr);
                gap: 0.75rem;
            }
            
            .service-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="booking-container">
        <div class="booking-header">
            <h1><i class="fas fa-calendar-check"></i> Book a Service</h1>
            <p>Select a service and provide your details. We'll contact you within 3-4 business days.</p>
        </div>
        
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
        
        <form method="POST" class="booking-form" id="bookingForm">
            <h3><i class="fas fa-list"></i> Select a Service</h3>
            
            <?php foreach ($grouped_services as $category => $category_services): ?>
                <div class="category-section">
                    <div class="category-title"><?php echo htmlspecialchars($category); ?></div>
                    <div class="service-grid">
                        <?php foreach ($category_services as $service): ?>
                            <div class="service-card" onclick="selectService(<?php echo $service['id']; ?>, this)">
                                <h4><?php echo htmlspecialchars($service['title']); ?></h4>
                                <p><?php echo htmlspecialchars($service['short_description'] ?: substr($service['description'], 0, 100) . '...'); ?></p>
                                <div class="service-price">
                                    <?php echo htmlspecialchars($service['price_range'] ?: 'Contact for pricing'); ?>
                                </div>
                                <small><i class="fas fa-tag"></i> <?php echo htmlspecialchars($service['category']); ?></small>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
            
            <input type="hidden" name="service_id" id="selectedServiceId" required>
            
            <!-- Agent Information Section (Hidden by default) -->
            <div id="agentSection" class="agent-section" style="display: none;">
                <!-- Content will be populated dynamically by JavaScript -->
            </div>
            
            <h3><i class="fas fa-user"></i> Your Information</h3>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="client_name">Full Name *</label>
                    <input type="text" name="client_name" id="client_name" required 
                           value="<?php echo htmlspecialchars($_POST['client_name'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label for="client_email">Email Address *</label>
                    <input type="email" name="client_email" id="client_email" required 
                           value="<?php echo htmlspecialchars($_POST['client_email'] ?? ''); ?>">
                </div>
            </div>
            
            <div class="form-group">
                <label for="client_phone">Phone Number</label>
                <input type="tel" name="client_phone" id="client_phone" 
                       value="<?php echo htmlspecialchars($_POST['client_phone'] ?? ''); ?>">
            </div>
            
            <div class="form-group">
                <label for="service_details">Service Details & Requirements *</label>
                <textarea name="service_details" id="service_details" required 
                          placeholder="Please provide detailed information about what you need..."><?php echo htmlspecialchars($_POST['service_details'] ?? ''); ?></textarea>
            </div>
            
            <div class="form-group">
                <label>Urgency Level</label>
                <div class="urgency-options">
                    <div class="urgency-option" onclick="selectUrgency('low', this)">
                        <input type="radio" name="urgency_level" value="low">
                        <div><i class="fas fa-clock" style="color: #28a745;"></i></div>
                        <div>Low</div>
                    </div>
                    <div class="urgency-option selected" onclick="selectUrgency('medium', this)">
                        <input type="radio" name="urgency_level" value="medium" checked>
                        <div><i class="fas fa-clock" style="color: #ffc107;"></i></div>
                        <div>Medium</div>
                    </div>
                    <div class="urgency-option" onclick="selectUrgency('high', this)">
                        <input type="radio" name="urgency_level" value="high">
                        <div><i class="fas fa-clock" style="color: #fd7e14;"></i></div>
                        <div>High</div>
                    </div>
                    <div class="urgency-option" onclick="selectUrgency('urgent', this)">
                        <input type="radio" name="urgency_level" value="urgent">
                        <div><i class="fas fa-exclamation-triangle" style="color: #dc3545;"></i></div>
                        <div>Urgent</div>
                    </div>
                </div>
            </div>
            
            <button type="submit" class="btn-book">
                <i class="fas fa-paper-plane"></i> Submit Booking Request
            </button>
        </form>
    </div>
    
    <?php include 'includes/footer.php'; ?>
    
    <script>
        // Enhanced form validation and UX
        document.addEventListener('DOMContentLoaded', function() {
            initializeFormValidation();
            initializeRealTimeValidation();
        });
        
        function selectService(serviceId, element) {
            // Remove selected class from all service cards
            document.querySelectorAll('.service-card').forEach(card => {
                card.classList.remove('selected');
            });
            
            // Add selected class to clicked card
            element.classList.add('selected');
            
            // Set hidden input value
            document.getElementById('selectedServiceId').value = serviceId;
            
            // Clear any existing service selection error
            clearFieldError('selectedServiceId');
            
            // Show success feedback
            showFieldSuccess('selectedServiceId', 'Service selected successfully!');
            
            // Fetch and display service details with agent information
            fetchServiceDetails(serviceId);
        }
        
        function fetchServiceDetails(serviceId) {
            // Show loading state
            const agentSection = document.getElementById('agentSection');
            agentSection.style.display = 'block';
            agentSection.innerHTML = '<div style="text-align: center; padding: 2rem;"><i class="fas fa-spinner fa-spin"></i> Loading service details...</div>';
            
            // Fetch service details via AJAX
            fetch(`api/get-service-details.php?service_id=${serviceId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        displayServiceDetails(data);
                    } else {
                        throw new Error(data.error || 'Failed to load service details');
                    }
                })
                .catch(error => {
                    console.error('Error fetching service details:', error);
                    agentSection.innerHTML = `
                        <div style="text-align: center; padding: 2rem; color: #dc3545;">
                            <i class="fas fa-exclamation-triangle"></i> 
                            Error loading service details. Please try again.
                        </div>
                    `;
                });
        }
        
        function displayServiceDetails(data) {
            const agentSection = document.getElementById('agentSection');
            
            let agentHtml = '';
            if (data.agent) {
                const agent = data.agent;
                agentHtml = `
                    <h3><i class="fas fa-user-tie"></i> Your Assigned Agent</h3>
                    <div class="agent-card">
                        <div class="agent-avatar">
                            ${agent.avatar ? 
                                `<img src="${agent.avatar}" alt="${agent.name}" style="width: 60px; height: 60px; border-radius: 50%; object-fit: cover;">` :
                                `<div class="agent-initials" style="width: 60px; height: 60px; background: #667eea; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 1.2rem;">${agent.initials}</div>`
                            }
                        </div>
                        <div class="agent-info">
                            <h4 style="margin: 0 0 0.5rem 0; color: #333;">${agent.name}</h4>
                            ${agent.specialization ? `<p class="agent-specialization" style="margin: 0 0 0.5rem 0; color: #667eea; font-weight: 500;"><i class="fas fa-star"></i> ${agent.specialization}</p>` : ''}
                            ${agent.bio ? `<p class="agent-bio" style="margin: 0 0 1rem 0; color: #666; font-size: 0.9rem;">${agent.bio}</p>` : ''}
                            <div class="agent-contact" style="display: flex; gap: 1rem; font-size: 0.85rem; color: #888;">
                                ${agent.email ? `<span><i class="fas fa-envelope"></i> ${agent.email}</span>` : ''}
                                ${agent.phone ? `<span><i class="fas fa-phone"></i> ${agent.phone}</span>` : ''}
                            </div>
                            ${!agent.specialization && !agent.bio && !agent.phone ? `<p style="margin: 0.5rem 0; color: #888; font-size: 0.9rem; font-style: italic;">Experienced professional ready to assist you.</p>` : ''}
                        </div>
                    </div>
                `;
            } else {
                agentHtml = `
                    <div style="text-align: center; padding: 1.5rem; background: #f8f9fa; border-radius: 8px; color: #666;">
                        <i class="fas fa-info-circle"></i> 
                        No specific agent assigned. Our team will handle your request.
                    </div>
                `;
            }
            
            const pricing = data.pricing;
            agentSection.innerHTML = `
                ${agentHtml}
                
                <!-- Price Information -->
                <div class="price-breakdown" style="margin-top: 1.5rem; background: #f8f9fa; padding: 1.5rem; border-radius: 8px;">
                    <h4 style="margin: 0 0 1rem 0; color: #333;"><i class="fas fa-calculator"></i> Service Pricing</h4>
                    <div class="price-item" style="display: flex; justify-content: space-between; padding: 0.5rem 0;">
                        <span>Price Range:</span>
                        <span style="font-weight: 500;">${pricing.price_range}</span>
                    </div>
                </div>
            `;
            
            // Add smooth scroll to agent section
            agentSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
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
            
            // Clear any urgency error
            clearFieldError('urgency_level');
        }
        
        // Enhanced form validation
        function initializeFormValidation() {
            const form = document.getElementById('bookingForm');
            const submitBtn = form.querySelector('button[type="submit"]');
            const originalBtnText = submitBtn.innerHTML;
            
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                
                // Clear all previous errors
                clearAllErrors();
                
                // Validate all fields
                let isValid = true;
                
                // Service selection validation
                const serviceId = document.getElementById('selectedServiceId').value;
                if (!serviceId) {
                    showFieldError('selectedServiceId', 'Please select a service before submitting.');
                    isValid = false;
                    // Scroll to service section
                    document.querySelector('.service-grid').scrollIntoView({ behavior: 'smooth' });
                }
                
                // Name validation
                const name = document.getElementById('client_name').value.trim();
                if (!name) {
                    showFieldError('client_name', 'Full name is required.');
                    isValid = false;
                } else if (name.length < 2) {
                    showFieldError('client_name', 'Name must be at least 2 characters long.');
                    isValid = false;
                }
                
                // Email validation
                const email = document.getElementById('client_email').value.trim();
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!email) {
                    showFieldError('client_email', 'Email address is required.');
                    isValid = false;
                } else if (!emailRegex.test(email)) {
                    showFieldError('client_email', 'Please enter a valid email address.');
                    isValid = false;
                }
                
                // Phone validation
                const phone = document.getElementById('client_phone').value.trim();
                const phoneRegex = /^[\+]?[1-9][\d]{0,15}$/;
                if (!phone) {
                    showFieldError('client_phone', 'Phone number is required.');
                    isValid = false;
                } else if (!phoneRegex.test(phone.replace(/[\s\-\(\)]/g, ''))) {
                    showFieldError('client_phone', 'Please enter a valid phone number.');
                    isValid = false;
                }
                
                // Service details validation
                const serviceDetails = document.getElementById('service_details').value.trim();
                if (!serviceDetails) {
                    showFieldError('service_details', 'Please describe your requirements.');
                    isValid = false;
                } else if (serviceDetails.length < 10) {
                    showFieldError('service_details', 'Please provide more detailed requirements (at least 10 characters).');
                    isValid = false;
                }
                
                // Urgency validation
                const urgency = document.querySelector('input[name="urgency_level"]:checked');
                if (!urgency) {
                    showFieldError('urgency_level', 'Please select an urgency level.');
                    isValid = false;
                }
                
                // If validation passes, show loading state and submit
                if (isValid) {
                    // Show loading state
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Submitting...';
                    submitBtn.style.opacity = '0.7';
                    
                    // Add a slight delay for better UX
                    setTimeout(() => {
                        form.submit();
                    }, 500);
                } else {
                    // Focus on first error field
                    const firstError = document.querySelector('.field-error');
                    if (firstError) {
                        const fieldId = firstError.getAttribute('data-field');
                        const field = document.getElementById(fieldId);
                        if (field) {
                            field.focus();
                            field.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        }
                    }
                }
            });
        }
        
        // Real-time validation
        function initializeRealTimeValidation() {
            // Name validation
            document.getElementById('client_name').addEventListener('blur', function() {
                const value = this.value.trim();
                if (value && value.length >= 2) {
                    showFieldSuccess(this.id, 'Valid name');
                } else if (value) {
                    showFieldError(this.id, 'Name must be at least 2 characters long.');
                }
            });
            
            // Email validation
            document.getElementById('client_email').addEventListener('blur', function() {
                const value = this.value.trim();
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (value && emailRegex.test(value)) {
                    showFieldSuccess(this.id, 'Valid email address');
                } else if (value) {
                    showFieldError(this.id, 'Please enter a valid email address.');
                }
            });
            
            // Phone validation
            document.getElementById('client_phone').addEventListener('blur', function() {
                const value = this.value.trim();
                const phoneRegex = /^[\+]?[1-9][\d]{0,15}$/;
                if (value && phoneRegex.test(value.replace(/[\s\-\(\)]/g, ''))) {
                    showFieldSuccess(this.id, 'Valid phone number');
                } else if (value) {
                    showFieldError(this.id, 'Please enter a valid phone number.');
                }
            });
            
            // Service details validation
            document.getElementById('service_details').addEventListener('input', function() {
                const value = this.value.trim();
                if (value.length >= 10) {
                    showFieldSuccess(this.id, 'Good description');
                }
            });
            
            // Clear errors on input
            document.querySelectorAll('input, textarea, select').forEach(field => {
                field.addEventListener('input', function() {
                    clearFieldError(this.id);
                });
            });
        }
        
        // Utility functions for field feedback
        function showFieldError(fieldId, message) {
            const field = document.getElementById(fieldId);
            const formGroup = field.closest('.form-group') || field.closest('.urgency-selector') || field.closest('.booking-form');
            
            // Remove existing feedback
            removeFieldFeedback(fieldId);
            
            // Add error styling
            field.style.borderColor = '#dc3545';
            
            // Create error element
            const errorElement = document.createElement('div');
            errorElement.className = 'field-error';
            errorElement.setAttribute('data-field', fieldId);
            errorElement.style.cssText = 'color: #dc3545; font-size: 0.875rem; margin-top: 0.25rem; display: flex; align-items: center;';
            errorElement.innerHTML = `<i class="fas fa-exclamation-circle" style="margin-right: 0.5rem;"></i>${message}`;
            
            formGroup.appendChild(errorElement);
        }
        
        function showFieldSuccess(fieldId, message) {
            const field = document.getElementById(fieldId);
            const formGroup = field.closest('.form-group') || field.closest('.urgency-selector') || field.closest('.booking-form');
            
            // Remove existing feedback
            removeFieldFeedback(fieldId);
            
            // Add success styling
            field.style.borderColor = '#28a745';
            
            // Create success element
            const successElement = document.createElement('div');
            successElement.className = 'field-success';
            successElement.setAttribute('data-field', fieldId);
            successElement.style.cssText = 'color: #28a745; font-size: 0.875rem; margin-top: 0.25rem; display: flex; align-items: center;';
            successElement.innerHTML = `<i class="fas fa-check-circle" style="margin-right: 0.5rem;"></i>${message}`;
            
            formGroup.appendChild(successElement);
            
            // Auto-remove success message after 3 seconds
            setTimeout(() => {
                if (successElement.parentNode) {
                    successElement.remove();
                    field.style.borderColor = '';
                }
            }, 3000);
        }
        
        function clearFieldError(fieldId) {
            removeFieldFeedback(fieldId);
            const field = document.getElementById(fieldId);
            field.style.borderColor = '';
        }
        
        function removeFieldFeedback(fieldId) {
            const existingFeedback = document.querySelectorAll(`[data-field="${fieldId}"]`);
            existingFeedback.forEach(element => element.remove());
        }
        
        function clearAllErrors() {
            document.querySelectorAll('.field-error, .field-success').forEach(element => element.remove());
            document.querySelectorAll('input, textarea, select').forEach(field => {
                field.style.borderColor = '';
            });
        }
    </script>
</body>
</html>
