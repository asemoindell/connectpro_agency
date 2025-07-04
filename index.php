<?php
// Include database configuration
require_once 'config/database.php';

// Initialize database connection
$database = new Database();
$db = $database->getConnection();

// Get dynamic content from database
$heroContent = [];
$stats = [];

if ($db) {
    try {
        // Get hero content
        $stmt = $db->prepare("SELECT content_key, content_value FROM content_pages WHERE page_name = 'home' AND section_name = 'hero'");
        $stmt->execute();
        while ($row = $stmt->fetch()) {
            $heroContent[$row['content_key']] = $row['content_value'];
        }
        
        // Get stats
        $stmt = $db->prepare("SELECT content_key, content_value FROM content_pages WHERE page_name = 'home' AND section_name = 'stats'");
        $stmt->execute();
        while ($row = $stmt->fetch()) {
            $stats[$row['content_key']] = $row['content_value'];
        }
        
        // Get active services for display
        $stmt = $db->prepare("SELECT * FROM services WHERE status = 'active' ORDER BY is_featured DESC, created_at DESC LIMIT 6");
        $stmt->execute();
        $services = $stmt->fetchAll();
        
    } catch (Exception $e) {
        // Fallback to default content if database fails
        $heroContent = [
            'title' => 'Your Trusted Service Connection Hub',
            'subtitle' => 'Connect with professional agents, book flights, make reservations, find lawyers, get tax assistance, hire engineers, and access all professional services in one place.'
        ];
        $stats = ['clients' => '10000', 'services' => '500', 'satisfaction' => '50', 'support' => '24/7'];
        $services = [];
    }
} else {
    // Fallback content
    $heroContent = [
        'title' => 'Your Trusted Service Connection Hub',
        'subtitle' => 'Connect with professional agents, book flights, make reservations, find lawyers, get tax assistance, hire engineers, and access all professional services in one place.'
    ];
    $stats = ['clients' => '10000', 'services' => '500', 'satisfaction' => '50', 'support' => '24/7'];
    $services = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ConnectPro Agency - Your Trusted Service Connection Hub</title>
    <link rel="stylesheet" href="css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <!-- Navigation -->
    <?php include 'includes/header.php'; ?>

    <!-- Hero Section -->
    <section id="home" class="hero">
        <div class="hero-container">
            <div class="hero-content">
                <h1><?php echo htmlspecialchars($heroContent['title'] ?? 'Your Trusted Service Connection Hub'); ?></h1>
                <p><?php echo htmlspecialchars($heroContent['subtitle'] ?? 'Connect with professional agents, book flights, make reservations, find lawyers, get tax assistance, hire engineers, and access all professional services in one place.'); ?></p>
                <div class="hero-buttons">
                    <a href="#services" class="btn btn-primary">Explore Services</a>
                    <a href="#contact" class="btn btn-secondary">Get Started</a>
                </div>
            </div>
            <div class="hero-image">
                <img src="images/hero-image.svg" alt="Professional Services">
            </div>
        </div>
    </section>

    <!-- Services Section -->
    <section id="services" class="services">
        <div class="container">
            <div class="section-header">
                <h2>Our Services</h2>
                <p>Comprehensive professional services to meet all your needs</p>
            </div>
            <div class="services-grid">
                <?php if (!empty($services)): ?>
                    <?php foreach ($services as $service): ?>
                        <div class="service-card">
                            <div class="service-icon">
                                <i class="fas fa-<?php echo $service['category'] == 'Travel' ? 'plane' : ($service['category'] == 'Legal' ? 'balance-scale' : ($service['category'] == 'Finance' ? 'calculator' : ($service['category'] == 'Technical' ? 'cogs' : 'user-tie'))); ?>"></i>
                            </div>
                            <h3><?php echo htmlspecialchars($service['title']); ?></h3>
                            <p><?php echo htmlspecialchars($service['short_description']); ?></p>
                            <ul class="service-features">
                                <?php 
                                $features = json_decode($service['features'], true);
                                if ($features):
                                    foreach ($features as $feature): ?>
                                        <li><?php echo htmlspecialchars($feature); ?></li>
                                    <?php endforeach;
                                endif; ?>
                            </ul>
                            <div class="service-price"><?php echo htmlspecialchars($service['price_range']); ?></div>
                            <a href="services.php#<?php echo $service['slug']; ?>" class="service-btn">Learn More</a>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <!-- Fallback static services if database is empty -->
                    <div class="service-card">
                        <div class="service-icon">
                            <i class="fas fa-user-tie"></i>
                        </div>
                        <h3>Professional Agents</h3>
                        <p>Connect with certified real estate, insurance, and business agents tailored to your specific requirements.</p>
                        <ul class="service-features">
                            <li>Real Estate Agents</li>
                            <li>Insurance Agents</li>
                            <li>Business Brokers</li>
                            <li>Investment Advisors</li>
                        </ul>
                        <a href="services.php" class="service-btn">Find Agent</a>
                    </div>

                    <div class="service-card">
                        <div class="service-icon">
                            <i class="fas fa-plane"></i>
                        </div>
                        <h3>Flight Booking</h3>
                        <p>Seamless flight booking experience with competitive prices and comprehensive travel support.</p>
                        <ul class="service-features">
                            <li>Domestic & International</li>
                            <li>Best Price Guarantee</li>
                            <li>24/7 Support</li>
                            <li>Flexible Cancellation</li>
                        </ul>
                        <a href="services.php" class="service-btn">Book Flight</a>
                    </div>

                    <div class="service-card">
                        <div class="service-icon">
                            <i class="fas fa-balance-scale"></i>
                        </div>
                        <h3>Legal Services</h3>
                        <p>Access qualified lawyers and legal professionals for consultation and representation.</p>
                        <ul class="service-features">
                            <li>Civil Law</li>
                            <li>Corporate Law</li>
                            <li>Family Law</li>
                            <li>Immigration Law</li>
                        </ul>
                        <a href="services.php" class="service-btn">Find Lawyer</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="stats">
        <div class="container">
            <div class="stats-grid">
                <div class="stat-item">
                    <div class="stat-number"><?php echo number_format($stats['clients'] ?? 10000); ?>+</div>
                    <div class="stat-label">Happy Clients</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?php echo number_format($stats['services'] ?? 500); ?>+</div>
                    <div class="stat-label">Professional Partners</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?php echo $stats['satisfaction'] ?? 50; ?>+</div>
                    <div class="stat-label">Service Categories</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?php echo $stats['support'] ?? '24/7'; ?></div>
                    <div class="stat-label">Customer Support</div>
                </div>
            </div>
        </div>
    </section>

    <!-- About Section -->
    <section id="about" class="about">
        <div class="container">
            <div class="about-content">
                <div class="about-text">
                    <h2>About ConnectPro Agency</h2>
                    <p>ConnectPro Agency is your premier destination for professional service connections. Established in 2020, we've revolutionized how people access and connect with qualified professionals across various industries.</p>
                    
                    <div class="about-features">
                        <div class="feature">
                            <i class="fas fa-shield-alt"></i>
                            <div>
                                <h4>Verified Professionals</h4>
                                <p>All our partners are thoroughly vetted and certified in their respective fields.</p>
                            </div>
                        </div>
                        <div class="feature">
                            <i class="fas fa-clock"></i>
                            <div>
                                <h4>Quick Response</h4>
                                <p>Get connected with the right professional within 24 hours of your request.</p>
                            </div>
                        </div>
                        <div class="feature">
                            <i class="fas fa-money-bill-wave"></i>
                            <div>
                                <h4>Competitive Pricing</h4>
                                <p>Access quality services at competitive rates with transparent pricing.</p>
                            </div>
                        </div>
                    </div>

                    <div class="mission">
                        <h3>Our Mission</h3>
                        <p>To bridge the gap between clients and qualified professionals, making professional services accessible, reliable, and affordable for everyone. We believe in building lasting relationships and delivering exceptional value through our comprehensive platform.</p>
                    </div>
                </div>
                <div class="about-image">
                    <img src="images/about-us.svg" alt="About ConnectPro">
                </div>
            </div>
        </div>
    </section>

    <!-- Testimonials Section -->
    <section class="testimonials">
        <div class="container">
            <div class="section-header">
                <h2>What Our Clients Say</h2>
                <p>Real experiences from satisfied customers</p>
            </div>
            <div class="testimonials-grid">
                <div class="testimonial-card">
                    <div class="testimonial-content">
                        <p>"ConnectPro helped me find an excellent real estate agent who sold my house within two weeks. The service was professional and efficient."</p>
                    </div>
                    <div class="testimonial-author">
                        <img src="images/client1.svg" alt="Sarah Johnson">
                        <div>
                            <h4>Sarah Johnson</h4>
                            <span>Homeowner</span>
                        </div>
                    </div>
                </div>
                <div class="testimonial-card">
                    <div class="testimonial-content">
                        <p>"The tax assistance service saved me thousands of dollars. The CPA they connected me with was knowledgeable and thorough."</p>
                    </div>
                    <div class="testimonial-author">
                        <img src="images/client2.svg" alt="Michael Chen">
                        <div>
                            <h4>Michael Chen</h4>
                            <span>Business Owner</span>
                        </div>
                    </div>
                </div>
                <div class="testimonial-card">
                    <div class="testimonial-content">
                        <p>"Booked my entire vacation through their platform. From flights to hotel reservations, everything was seamless and affordable."</p>
                    </div>
                    <div class="testimonial-author">
                        <img src="images/client3.svg" alt="Emily Rodriguez">
                        <div>
                            <h4>Emily Rodriguez</h4>
                            <span>Travel Enthusiast</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Contact Section -->
    <section id="contact" class="contact">
        <div class="container">
            <div class="contact-content">
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
                                <h4>Hours</h4>
                                <p>Mon - Fri: 9:00 AM - 6:00 PM<br>Sat: 10:00 AM - 4:00 PM</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="contact-form">
                    <form id="contactForm">
                        <div class="form-group">
                            <label for="name">Full Name</label>
                            <input type="text" id="name" name="name" required>
                        </div>
                        <div class="form-group">
                            <label for="email">Email Address</label>
                            <input type="email" id="email" name="email" required>
                        </div>
                        <div class="form-group">
                            <label for="phone">Phone Number</label>
                            <input type="tel" id="phone" name="phone">
                        </div>
                        <div class="form-group">
                            <label for="service">Service Needed</label>
                            <select id="service" name="service" required>
                                <option value="">Select a service</option>
                                <?php if (!empty($services)): ?>
                                    <?php foreach ($services as $service): ?>
                                        <option value="<?php echo htmlspecialchars($service['slug']); ?>"><?php echo htmlspecialchars($service['title']); ?></option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="message">Message</label>
                            <textarea id="message" name="message" rows="5" required></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">Send Message</button>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <?php include 'includes/footer.php'; ?>

    <script src="js/script.js"></script>
</body>
</html>
