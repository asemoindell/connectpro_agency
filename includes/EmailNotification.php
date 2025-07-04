<?php
// Email configuration and notification system
class EmailNotification {
    private $db;
    private $smtp_host = 'smtp.gmail.com'; // Change to your SMTP server
    private $smtp_port = 587;
    private $smtp_username = 'your-email@gmail.com'; // Change to your email
    private $smtp_password = 'your-app-password'; // Change to your app password
    private $from_email = 'noreply@connectpro.com';
    private $from_name = 'ConnectPro Agency';
    
    public function __construct($database) {
        $this->db = $database;
    }
    
    /**
     * Send approval notification
     */
    public function sendApprovalNotification($booking_id) {
        $booking = $this->getBookingDetails($booking_id);
        if (!$booking) return false;
        
        $subject = "Service Approved - Payment Required - Ref: {$booking['booking_reference']}";
        $content = $this->generateApprovalNotificationEmail($booking);
        
        return $this->sendEmail(
            $booking['client_email'],
            $subject,
            $content,
            'approval_notice',
            $booking_id
        );
    }
    
    /**
     * Send chat invitation
     */
    public function sendChatInvitation($booking_id, $chat_token) {
        $booking = $this->getBookingDetails($booking_id);
        if (!$booking) return false;
        
        $subject = "Chat Started - Connect with Your Agent - Ref: {$booking['booking_reference']}";
        $content = $this->generateChatInvitationEmail($booking, $chat_token);
        
        return $this->sendEmail(
            $booking['client_email'],
            $subject,
            $content,
            'chat_invitation',
            $booking_id
        );
    }
    
    /**
     * Send test email
     */
    public function sendTestEmail($email) {
        $subject = "ConnectPro Agency - Test Email";
        $content = $this->generateTestEmail();
        
        return $this->sendEmail(
            $email,
            $subject,
            $content,
            'test_email',
            0
        );
    }
    
    /**
     * Generate booking confirmation email content
     */
    private function generateBookingConfirmationEmail($booking) {
        $approval_date = date('Y-m-d', strtotime($booking['approval_deadline']));
        
        return "
        <html>
        <head>
            <style>
                .email-container { font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; }
                .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; text-align: center; }
                .content { padding: 20px; background: #f9f9f9; }
                .booking-details { background: white; padding: 15px; border-radius: 8px; margin: 15px 0; }
                .footer { background: #333; color: white; padding: 15px; text-align: center; font-size: 12px; }
                .highlight { color: #667eea; font-weight: bold; }
            </style>
        </head>
        <body>
            <div class='email-container'>
                <div class='header'>
                    <h1>🎉 Booking Confirmed!</h1>
                    <p>Thank you for choosing ConnectPro Agency</p>
                </div>
                
                <div class='content'>
                    <p>Dear {$booking['client_name']},</p>
                    
                    <p>We have successfully received your service booking request. Here are the details:</p>
                    
                    <div class='booking-details'>
                        <h3>Booking Information</h3>
                        <p><strong>Reference Number:</strong> <span class='highlight'>{$booking['booking_reference']}</span></p>
                        <p><strong>Service:</strong> {$booking['service_name']}</p>
                        <p><strong>Booking Date:</strong> " . date('F j, Y g:i A', strtotime($booking['booking_date'])) . "</p>
                        <p><strong>Status:</strong> Confirmed - Pending Review</p>
                    </div>
                    
                    <div class='booking-details'>
                        <h3>⏰ What Happens Next?</h3>
                        <ol>
                            <li><strong>Review Period:</strong> Our team will review your request within 3-4 business days</li>
                            <li><strong>Approval:</strong> You'll receive approval notification by <strong>{$approval_date}</strong></li>
                            <li><strong>Payment:</strong> After approval, you'll receive payment details</li>
                            <li><strong>Service Delivery:</strong> Once paid, we'll connect you with your dedicated agent</li>
                        </ol>
                    </div>
                    
                    <div class='booking-details'>
                        <h3>📞 Need Help?</h3>
                        <p>If you have any questions, please don't hesitate to contact us:</p>
                        <p>📧 Email: support@connectpro.com</p>
                        <p>📱 Phone: +1 (555) 123-4567</p>
                    </div>
                </div>
                
                <div class='footer'>
                    <p>&copy; 2025 ConnectPro Agency. All rights reserved.</p>
                    <p>You're receiving this email because you made a booking with us.</p>
                </div>
            </div>
        </body>
        </html>";
    }
    
    /**
     * Generate approval notification email content
     */
    private function generateApprovalNotificationEmail($booking) {
        $payment_link = "http://localhost/Agency/payment.php?ref={$booking['booking_reference']}";
        
        return "
        <html>
        <head>
            <style>
                .email-container { font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; }
                .header { background: linear-gradient(135deg, #28a745 0%, #20c997 100%); color: white; padding: 20px; text-align: center; }
                .content { padding: 20px; background: #f9f9f9; }
                .booking-details { background: white; padding: 15px; border-radius: 8px; margin: 15px 0; }
                .payment-section { background: #e7f3ff; border: 2px solid #667eea; padding: 20px; border-radius: 8px; text-align: center; }
                .pay-button { background: #667eea; color: white; padding: 15px 30px; text-decoration: none; border-radius: 8px; display: inline-block; font-weight: bold; }
                .footer { background: #333; color: white; padding: 15px; text-align: center; font-size: 12px; }
                .price { font-size: 24px; color: #28a745; font-weight: bold; }
            </style>
        </head>
        <body>
            <div class='email-container'>
                <div class='header'>
                    <h1>✅ Service Approved!</h1>
                    <p>Your request has been approved - Payment Required</p>
                </div>
                
                <div class='content'>
                    <p>Great news, {$booking['client_name']}!</p>
                    
                    <p>Your service request has been <strong>approved</strong> and is ready to proceed.</p>
                    
                    <div class='booking-details'>
                        <h3>Service Details</h3>
                        <p><strong>Reference:</strong> {$booking['booking_reference']}</p>
                        <p><strong>Service:</strong> {$booking['service_name']}</p>
                        <p><strong>Quoted Price:</strong> <span class='price'>\${$booking['quoted_price']}</span></p>
                        <p><strong>Agent Fee:</strong> \${$booking['agent_fee']}</p>
                        <p><strong>Total Amount:</strong> <span class='price'>\${$booking['total_amount']}</span></p>
                    </div>
                    
                    <div class='payment-section'>
                        <h3>💳 Payment Required</h3>
                        <p>To proceed with your service, please complete the payment:</p>
                        <a href='{$payment_link}' class='pay-button'>Pay Now - \${$booking['total_amount']}</a>
                        <p style='margin-top: 15px; font-size: 14px;'>We accept: Stripe, PayPal, USDT, Bitcoin, and Bank Transfer</p>
                    </div>
                    
                    <div class='booking-details'>
                        <h3>🚀 After Payment</h3>
                        <p>Once payment is confirmed:</p>
                        <ul>
                            <li>You'll receive a chat invitation to connect with your dedicated agent</li>
                            <li>Your agent will guide you through the entire process</li>
                            <li>Real-time communication until service completion</li>
                        </ul>
                    </div>
                </div>
                
                <div class='footer'>
                    <p>&copy; 2025 ConnectPro Agency. All rights reserved.</p>
                </div>
            </div>
        </body>
        </html>";
    }
    
    /**
     * Generate chat invitation email content
     */
    private function generateChatInvitationEmail($booking, $chat_token) {
        $chat_link = "http://localhost/Agency/chat.php?token={$chat_token}";
        
        return "
        <html>
        <head>
            <style>
                .email-container { font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; }
                .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; text-align: center; }
                .content { padding: 20px; background: #f9f9f9; }
                .chat-section { background: #e7f3ff; border: 2px solid #667eea; padding: 20px; border-radius: 8px; text-align: center; }
                .chat-button { background: #28a745; color: white; padding: 15px 30px; text-decoration: none; border-radius: 8px; display: inline-block; font-weight: bold; }
                .footer { background: #333; color: white; padding: 15px; text-align: center; font-size: 12px; }
            </style>
        </head>
        <body>
            <div class='email-container'>
                <div class='header'>
                    <h1>💬 Chat Started!</h1>
                    <p>Connect with your dedicated agent</p>
                </div>
                
                <div class='content'>
                    <p>Hello {$booking['client_name']},</p>
                    
                    <p>Payment confirmed! Your service is now active and your dedicated agent is ready to assist you.</p>
                    
                    <div class='chat-section'>
                        <h3>🎯 Start Chatting Now</h3>
                        <p>Click the button below to start communicating with your agent:</p>
                        <a href='{$chat_link}' class='chat-button'>Open Chat</a>
                        <p style='margin-top: 15px; font-size: 14px;'>Reference: {$booking['booking_reference']}</p>
                    </div>
                    
                    <div style='background: white; padding: 15px; border-radius: 8px; margin: 15px 0;'>
                        <h3>📋 What to Expect</h3>
                        <ul>
                            <li>Direct communication with your assigned agent</li>
                            <li>Real-time updates on your service progress</li>
                            <li>File sharing and document exchange</li>
                            <li>24/7 support until completion</li>
                        </ul>
                    </div>
                </div>
                
                <div class='footer'>
                    <p>&copy; 2025 ConnectPro Agency. All rights reserved.</p>
                </div>
            </div>
        </body>
        </html>";
    }
    
    /**
     * Generate test email content
     */
    private function generateTestEmail() {
        $content = "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <title>Test Email</title>
        </head>
        <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
            <div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
                <div style='background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; border-radius: 10px; text-align: center; margin-bottom: 20px;'>
                    <h1 style='margin: 0; font-size: 28px;'>🧪 Test Email</h1>
                    <p style='margin: 10px 0 0 0; font-size: 16px; opacity: 0.9;'>ConnectPro Agency Email System</p>
                </div>
                
                <div style='background: #f8f9fa; padding: 25px; border-radius: 8px; margin-bottom: 20px;'>
                    <h2 style='color: #28a745; margin-top: 0;'>✅ Email System Working!</h2>
                    <p>Congratulations! Your email configuration is working correctly.</p>
                    <p><strong>Test Details:</strong></p>
                    <ul>
                        <li>Sent: " . date('Y-m-d H:i:s') . "</li>
                        <li>System: ConnectPro Agency Booking System</li>
                        <li>Status: Email delivery successful</li>
                    </ul>
                </div>
                
                <div style='background: #e3f2fd; padding: 20px; border-radius: 8px; border-left: 4px solid #2196f3;'>
                    <h3 style='color: #1976d2; margin-top: 0;'>📧 Email Types Configured:</h3>
                    <ul style='margin: 0;'>
                        <li>Booking confirmations</li>
                        <li>Approval notifications</li>
                        <li>Payment confirmations</li>
                        <li>Chat invitations</li>
                        <li>System notifications</li>
                    </ul>
                </div>
                
                <div style='text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd;'>
                    <p style='margin: 0; color: #666; font-size: 14px;'>
                        ConnectPro Agency - Professional Service Booking System<br>
                        <a href='http://localhost/Agency' style='color: #007bff;'>Visit Website</a>
                    </p>
                </div>
            </div>
        </body>
        </html>";
        
        return $content;
    }
    
    /**
     * Send email using SMTP
     */
    private function sendEmail($to, $subject, $content, $type, $booking_id) {
        // Log email attempt
        $stmt = $this->db->prepare("INSERT INTO email_notifications (booking_id, recipient_email, email_type, subject, content) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$booking_id, $to, $type, $subject, $content]);
        $notification_id = $this->db->lastInsertId();
        
        // For demo purposes, we'll simulate email sending
        // In production, integrate with actual email service (PHPMailer, SendGrid, etc.)
        
        try {
            // Simulate email sending
            sleep(1); // Simulate network delay
            
            // Update notification status
            $update_stmt = $this->db->prepare("UPDATE email_notifications SET status = 'sent' WHERE id = ?");
            $update_stmt->execute([$notification_id]);
            
            return true;
        } catch (Exception $e) {
            // Update notification status to failed
            $update_stmt = $this->db->prepare("UPDATE email_notifications SET status = 'failed' WHERE id = ?");
            $update_stmt->execute([$notification_id]);
            
            return false;
        }
    }
    
    /**
     * Enhanced sendEmail method for template system
     */
    private function sendEmailTemplate($to, $name, $subject, $content) {
        try {
            // For demo purposes, we'll simulate email sending
            // In production, integrate with actual email service (PHPMailer, SendGrid, etc.)
            
            // Simulate email sending
            sleep(1); // Simulate network delay
            
            // In a real implementation, you would send the actual email here
            // Example with PHPMailer:
            /*
            $mail = new PHPMailer(true);
            $mail->setFrom('noreply@connectpro.com', 'ConnectPro Agency');
            $mail->addAddress($to, $name);
            $mail->Subject = $subject;
            $mail->Body = $content;
            $mail->isHTML(true);
            return $mail->send();
            */
            
            return true;
        } catch (Exception $e) {
            error_log("Email sending error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Send user registration notification
     */
    public function sendUserRegistrationEmail($user_id) {
        try {
            $user = $this->getUserDetails($user_id);
            if (!$user) return false;
            
            $template = $this->getEmailTemplate('user_registration');
            if (!$template || !$template['is_active']) return true; // Skip if template inactive
            
            $variables = [
                '{{user_name}}' => $user['name'],
                '{{user_email}}' => $user['email'],
                '{{registration_date}}' => date('F j, Y', strtotime($user['created_at'])),
                '{{login_url}}' => $this->base_url . '/user/login.php'
            ];
            
            $subject = $this->replaceVariables($template['subject'], $variables);
            $content = $this->replaceVariables($template['content'], $variables);
            
            $this->logEmail('user_registration', $user['email'], $user['name'], $subject, $content, $user_id);
            
            return $this->sendEmailTemplate($user['email'], $user['name'], $subject, $content);
            
        } catch (Exception $e) {
            error_log("User registration email error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Send user login notification
     */
    public function sendUserLoginEmail($user_id, $login_details) {
        try {
            $user = $this->getUserDetails($user_id);
            if (!$user) return false;
            
            $template = $this->getEmailTemplate('user_login');
            if (!$template || !$template['is_active']) return true; // Skip if template inactive
            
            $variables = [
                '{{user_name}}' => $user['name'],
                '{{user_email}}' => $user['email'],
                '{{login_time}}' => $login_details['login_time'] ?? date('F j, Y H:i:s'),
                '{{ip_address}}' => $login_details['ip_address'] ?? 'Unknown',
                '{{location}}' => $login_details['location'] ?? 'Unknown',
                '{{user_agent}}' => $login_details['user_agent'] ?? 'Unknown'
            ];
            
            $subject = $this->replaceVariables($template['subject'], $variables);
            $content = $this->replaceVariables($template['content'], $variables);
            
            $this->logEmail('user_login', $user['email'], $user['name'], $subject, $content, $user_id);
            
            return $this->sendEmailTemplate($user['email'], $user['name'], $subject, $content);
            
        } catch (Exception $e) {
            error_log("User login email error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Send user approval notification
     */
    public function sendUserApprovalEmail($user_id) {
        try {
            $user = $this->getUserDetails($user_id);
            if (!$user) return false;
            
            $template = $this->getEmailTemplate('user_approval');
            if (!$template || !$template['is_active']) return true; // Skip if template inactive
            
            $variables = [
                '{{user_name}}' => $user['name'],
                '{{user_email}}' => $user['email'],
                '{{admin_notes}}' => $user['admin_notes'] ?? 'Welcome to ConnectPro Agency!',
                '{{login_url}}' => $this->base_url . '/user/login.php'
            ];
            
            $subject = $this->replaceVariables($template['subject'], $variables);
            $content = $this->replaceVariables($template['content'], $variables);
            
            $this->logEmail('user_approval', $user['email'], $user['name'], $subject, $content, $user_id);
            
            return $this->sendEmailTemplate($user['email'], $user['name'], $subject, $content);
            
        } catch (Exception $e) {
            error_log("User approval email error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Send user rejection notification
     */
    public function sendUserRejectionEmail($user_id, $rejection_reason) {
        try {
            $user = $this->getUserDetails($user_id);
            if (!$user) return false;
            
            $template = $this->getEmailTemplate('user_rejection');
            if (!$template || !$template['is_active']) return true; // Skip if template inactive
            
            $variables = [
                '{{user_name}}' => $user['name'],
                '{{user_email}}' => $user['email'],
                '{{rejection_reason}}' => $rejection_reason
            ];
            
            $subject = $this->replaceVariables($template['subject'], $variables);
            $content = $this->replaceVariables($template['content'], $variables);
            
            $this->logEmail('user_rejection', $user['email'], $user['name'], $subject, $content, $user_id);
            
            return $this->sendEmailTemplate($user['email'], $user['name'], $subject, $content);
            
        } catch (Exception $e) {
            error_log("User rejection email error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get email template from database
     */
    private function getEmailTemplate($template_type) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM email_templates WHERE template_type = ? AND is_active = 1");
            $stmt->execute([$template_type]);
            return $stmt->fetch();
        } catch (Exception $e) {
            error_log("Email template error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Get user details
     */
    private function getUserDetails($user_id) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            return $stmt->fetch();
        } catch (Exception $e) {
            error_log("User details error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Replace variables in template
     */
    private function replaceVariables($text, $variables) {
        foreach ($variables as $placeholder => $value) {
            $text = str_replace($placeholder, $value, $text);
        }
        return $text;
    }
    
    /**
     * Log email for audit trail
     */
    private function logEmail($template_type, $recipient_email, $recipient_name, $subject, $content, $user_id = null, $booking_id = null) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO email_logs 
                (template_type, recipient_email, recipient_name, subject, content, user_id, booking_id, status, sent_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, 'sent', NOW())
            ");
            $stmt->execute([$template_type, $recipient_email, $recipient_name, $subject, $content, $user_id, $booking_id]);
        } catch (Exception $e) {
            error_log("Email logging error: " . $e->getMessage());
        }
    }

    /**
     * Enhanced booking confirmation with template system
     */
    public function sendBookingConfirmation($booking_id) {
        try {
            $booking = $this->getBookingDetails($booking_id);
            if (!$booking) return false;
            
            $template = $this->getEmailTemplate('booking_confirmation');
            if (!$template || !$template['is_active']) return true;
            
            $variables = [
                '{{client_name}}' => $booking['client_name'],
                '{{booking_reference}}' => $booking['booking_reference'],
                '{{service_name}}' => $booking['service_name'],
                '{{total_amount}}' => number_format($booking['total_amount'], 2),
                '{{booking_status}}' => ucfirst($booking['status']),
                '{{approval_deadline}}' => date('F j, Y', strtotime($booking['approval_deadline'])),
                '{{booking_url}}' => $this->base_url . '/track-booking.php?ref=' . $booking['booking_reference']
            ];
            
            $subject = $this->replaceVariables($template['subject'], $variables);
            $content = $this->replaceVariables($template['content'], $variables);
            
            $this->logEmail('booking_confirmation', $booking['client_email'], $booking['client_name'], $subject, $content, $booking['user_id'], $booking_id);
            
            return $this->sendEmailTemplate($booking['client_email'], $booking['client_name'], $subject, $content);
            
        } catch (Exception $e) {
            error_log("Booking confirmation email error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get booking details for email
     */
    private function getBookingDetails($booking_id) {
        $stmt = $this->db->prepare("
            SELECT b.*, s.title as service_name, s.description as service_description 
            FROM service_bookings b 
            JOIN services_enhanced s ON b.service_id = s.id 
            WHERE b.id = ?
        ");
        $stmt->execute([$booking_id]);
        return $stmt->fetch();
    }
}
?>
