<?php
// Include database configuration
require_once 'config/database.php';

// Initialize database connection
$database = new Database();
$db = $database->getConnection();

// Get services for the dropdown
$services = [];
if ($db) {
    try {
        $stmt = $db->prepare("SELECT slug, title FROM services WHERE status = 'active' ORDER BY title");
        $stmt->execute();
        $services = $stmt->fetchAll();
    } catch (Exception $e) {
        $services = [];
    }
}

// Handle form submission
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_contact'])) {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $service = trim($_POST['service'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $messageText = trim($_POST['message'] ?? '');
    
    // Basic validation
    if (empty($name) || empty($email) || empty($messageText)) {
        $message = 'Please fill in all required fields.';
        $messageType = 'error';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = 'Please enter a valid email address.';
        $messageType = 'error';
    } else {
        // Save to database
        if ($db) {
            try {
                $stmt = $db->prepare("INSERT INTO contact_inquiries (name, email, phone, service, message) VALUES (?, ?, ?, ?, ?)");
                $fullMessage = $subject ? "Subject: $subject\n\n$messageText" : $messageText;
                $stmt->execute([$name, $email, $phone, $service, $fullMessage]);
                
                $message = 'Thank you for your message! We will get back to you within 24 hours.';
                $messageType = 'success';
                
                // Clear form data on success
                $_POST = [];
            } catch (Exception $e) {
                $message = 'Sorry, there was an error sending your message. Please try again.';
                $messageType = 'error';
            }
        } else {
            $message = 'Sorry, there was an error sending your message. Please try again.';
            $messageType = 'error';
        }
    }
}

// Pre-populate service if provided in URL
$selectedService = $_GET['service'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us - ConnectPro Agency</title>
    <link rel="stylesheet" href="css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <!-- Navigation -->
    <?php include 'includes/header.php'; ?>

    <!-- Page Header -->
    <section class="page-header">
        <div class="container">
            <h1>Contact ConnectPro Agency</h1>
            <p>Get in touch with us for all your professional service needs</p>
        </div>
    </section>

    <!-- Contact Section -->
    <section class="contact-page">
        <div class="container">
            <div class="contact-content">
                <!-- Contact Information -->
                <div class="contact-info">
                    <h2>Get In Touch</h2>
                    <p>Ready to connect with the right professional? Contact us today and let us help you find exactly what you need.</p>
                    
                    <div class="contact-details">
                        <div class="contact-item">
                            <i class="fas fa-map-marker-alt"></i>
                            <div>
                                <h4>Address</h4>
                                <p>123 Business District<br>New York, NY 10001</p>
                            </div>
                        </div>
                        <div class="contact-item">
                            <i class="fas fa-phone"></i>
                            <div>
                                <h4>Phone</h4>
                                <p>+1 (555) 123-4567</p>
                            </div>
                        </div>
                        <div class="contact-item">
                            <i class="fas fa-envelope"></i>
                            <div>
                                <h4>Email</h4>
                                <p>hello@connectpro.com</p>
                            </div>
                        </div>
                        <div class="contact-item">
                            <i class="fas fa-clock"></i>
                            <div>
                                <h4>Business Hours</h4>
                                <p>Mon - Fri: 9:00 AM - 6:00 PM<br>Sat: 10:00 AM - 4:00 PM<br>Sun: Closed</p>
                            </div>
                        </div>
                    </div>

                    <div class="contact-social">
                        <h4>Follow Us</h4>
                        <div class="social-links">
                            <a href="#"><i class="fab fa-facebook"></i></a>
                            <a href="#"><i class="fab fa-twitter"></i></a>
                            <a href="#"><i class="fab fa-linkedin"></i></a>
                            <a href="#"><i class="fab fa-instagram"></i></a>
                        </div>
                    </div>
                </div>
                
                <!-- Contact Form -->
                <div class="contact-form">
                    <h3>Send us a Message</h3>
                    
                    <?php if ($message): ?>
                        <div class="form-message <?php echo $messageType; ?>">
                            <?php echo htmlspecialchars($message); ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="name">Full Name *</label>
                                <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="email">Email Address *</label>
                                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="phone">Phone Number</label>
                                <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
                            </div>
                            <div class="form-group">
                                <label for="service">Service Needed</label>
                                <select id="service" name="service">
                                    <option value="">Select a service</option>
                                    <?php if (!empty($services)): ?>
                                        <?php foreach ($services as $service): ?>
                                            <option value="<?php echo htmlspecialchars($service['slug']); ?>" 
                                                <?php echo ($selectedService === $service['slug'] || ($_POST['service'] ?? '') === $service['slug']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($service['title']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <option value="travel">Travel Services</option>
                                        <option value="legal">Legal Services</option>
                                        <option value="tax">Tax Preparation</option>
                                        <option value="engineering">Engineering Services</option>
                                        <option value="events">Event Planning</option>
                                        <option value="financial">Financial Advisory</option>
                                    <?php endif; ?>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="subject">Subject</label>
                            <input type="text" id="subject" name="subject" value="<?php echo htmlspecialchars($_POST['subject'] ?? ''); ?>" placeholder="Brief description of your inquiry">
                        </div>
                        
                        <div class="form-group">
                            <label for="message">Message *</label>
                            <textarea id="message" name="message" rows="6" required placeholder="Tell us more about your needs..."><?php echo htmlspecialchars($_POST['message'] ?? ''); ?></textarea>
                        </div>
                        
                        <button type="submit" name="submit_contact" class="btn btn-primary">Send Message</button>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <!-- FAQ Section -->
    <section class="faq-section">
        <div class="container">
            <div class="section-header">
                <h2>Frequently Asked Questions</h2>
                <p>Quick answers to common questions</p>
            </div>
            
            <div class="faq-grid">
                <div class="faq-item">
                    <div class="faq-question">
                        <h4>How quickly can I get connected with a professional?</h4>
                        <i class="fas fa-plus"></i>
                    </div>
                    <div class="faq-answer">
                        <p>Most connections are made within 24 hours. For urgent requests, we offer expedited matching within 2-4 hours for an additional fee.</p>
                    </div>
                </div>

                <div class="faq-item">
                    <div class="faq-question">
                        <h4>Are all professionals verified and licensed?</h4>
                        <i class="fas fa-plus"></i>
                    </div>
                    <div class="faq-answer">
                        <p>Yes, every professional in our network is thoroughly vetted, licensed, and insured. We verify credentials, references, and maintain quality standards.</p>
                    </div>
                </div>

                <div class="faq-item">
                    <div class="faq-question">
                        <h4>What if I'm not satisfied with the service?</h4>
                        <i class="fas fa-plus"></i>
                    </div>
                    <div class="faq-answer">
                        <p>We offer a satisfaction guarantee. If you're not happy with the service, we'll work to resolve the issue or connect you with another professional at no additional cost.</p>
                    </div>
                </div>

                <div class="faq-item">
                    <div class="faq-question">
                        <h4>How do I make payments?</h4>
                        <i class="fas fa-plus"></i>
                    </div>
                    <div class="faq-answer">
                        <p>We accept all major credit cards, PayPal, and bank transfers. Payments are processed securely through our encrypted platform.</p>
                    </div>
                </div>

                <div class="faq-item">
                    <div class="faq-question">
                        <h4>Can I request specific professionals?</h4>
                        <i class="fas fa-plus"></i>
                    </div>
                    <div class="faq-answer">
                        <p>Yes, if you've worked with one of our professionals before and want to request them again, just mention it in your inquiry and we'll accommodate if they're available.</p>
                    </div>
                </div>

                <div class="faq-item">
                    <div class="faq-question">
                        <h4>Do you offer emergency services?</h4>
                        <i class="fas fa-plus"></i>
                    </div>
                    <div class="faq-answer">
                        <p>For certain services like legal emergencies or urgent technical issues, we have 24/7 emergency professionals available. Additional fees may apply.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Office Locations -->
    <section class="office-locations">
        <div class="container">
            <div class="section-header">
                <h2>Our Offices</h2>
                <p>Visit us at any of our convenient locations</p>
            </div>
            
            <div class="offices-grid">
                <div class="office-item">
                    <div class="office-info">
                        <h3>New York Headquarters</h3>
                        <p>123 Business District<br>New York, NY 10001</p>
                        <p><strong>Phone:</strong> +1 (555) 123-4567</p>
                        <p><strong>Hours:</strong> Mon-Fri 9AM-6PM</p>
                    </div>
                </div>

                <div class="office-item">
                    <div class="office-info">
                        <h3>Los Angeles Office</h3>
                        <p>456 Sunset Boulevard<br>Los Angeles, CA 90028</p>
                        <p><strong>Phone:</strong> +1 (555) 234-5678</p>
                        <p><strong>Hours:</strong> Mon-Fri 8AM-5PM</p>
                    </div>
                </div>

                <div class="office-item">
                    <div class="office-info">
                        <h3>Chicago Branch</h3>
                        <p>789 Michigan Avenue<br>Chicago, IL 60611</p>
                        <p><strong>Phone:</strong> +1 (555) 345-6789</p>
                        <p><strong>Hours:</strong> Mon-Fri 9AM-6PM</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <?php include 'includes/footer.php'; ?>

    <script src="js/script.js"></script>
    <script>
        // FAQ Accordion functionality
        document.addEventListener('DOMContentLoaded', function() {
            const faqItems = document.querySelectorAll('.faq-item');
            
            faqItems.forEach(item => {
                const question = item.querySelector('.faq-question');
                const answer = item.querySelector('.faq-answer');
                const icon = question.querySelector('i');
                
                question.addEventListener('click', function() {
                    const isOpen = item.classList.contains('active');
                    
                    // Close all FAQ items
                    faqItems.forEach(faqItem => {
                        faqItem.classList.remove('active');
                        faqItem.querySelector('i').classList.remove('fa-minus');
                        faqItem.querySelector('i').classList.add('fa-plus');
                    });
                    
                    // Open clicked item if it wasn't already open
                    if (!isOpen) {
                        item.classList.add('active');
                        icon.classList.remove('fa-plus');
                        icon.classList.add('fa-minus');
                    }
                });
            });
        });
    </script>
</body>
</html>
