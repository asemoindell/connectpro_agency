<?php
// Include database configuration
require_once 'config/database.php';

// Initialize database connection
$database = new Database();
$db = $database->getConnection();

// Get about page content from database
$aboutContent = [];

if ($db) {
    try {
        $stmt = $db->prepare("SELECT content_key, content_value FROM content_pages WHERE page_name = 'about'");
        $stmt->execute();
        while ($row = $stmt->fetch()) {
            $aboutContent[$row['content_key']] = $row['content_value'];
        }
    } catch (Exception $e) {
        // Fallback content if database fails
    }
}

// Set default content if not in database
$missionTitle = $aboutContent['mission_title'] ?? 'Our Mission';
$missionContent = $aboutContent['mission_content'] ?? 'To connect clients with the most qualified professionals and make booking services simple, transparent, and reliable.';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us - ConnectPro Agency</title>
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
            <h1>About ConnectPro Agency</h1>
            <p>Your trusted partner in professional service connections</p>
        </div>
    </section>

    <!-- Company Story -->
    <section class="company-story">
        <div class="container">
            <div class="story-content">
                <div class="story-text">
                    <h2>Our Story</h2>
                    <p>Founded in 2020, ConnectPro Agency emerged from a simple yet powerful vision: to revolutionize how people access professional services. Our founders, experienced professionals from various industries, recognized the challenges individuals and businesses face when trying to find reliable, qualified service providers.</p>
                    
                    <p>What started as a small network of trusted professionals has grown into a comprehensive platform serving thousands of clients nationwide. We've carefully curated our network to include only the most qualified, verified professionals across multiple industries.</p>
                    
                    <p>Today, ConnectPro Agency stands as a bridge between those who need professional services and those who provide them, ensuring quality connections that lead to successful outcomes.</p>
                </div>
                <div class="story-image">
                    <img src="images/about-us.svg" alt="Our Story">
                </div>
            </div>
        </div>
    </section>

    <!-- Mission, Vision, Values -->
    <section class="mvv-section">
        <div class="container">
            <div class="mvv-grid">
                <div class="mvv-card">
                    <div class="mvv-icon">
                        <i class="fas fa-bullseye"></i>
                    </div>
                    <h3><?php echo htmlspecialchars($missionTitle); ?></h3>
                    <p><?php echo htmlspecialchars($missionContent); ?></p>
                </div>
                
                <div class="mvv-card">
                    <div class="mvv-icon">
                        <i class="fas fa-eye"></i>
                    </div>
                    <h3>Our Vision</h3>
                    <p>To become the global leader in professional service connections, making quality services accessible to everyone, everywhere.</p>
                </div>
                
                <div class="mvv-card">
                    <div class="mvv-icon">
                        <i class="fas fa-heart"></i>
                    </div>
                    <h3>Our Values</h3>
                    <p>Trust, excellence, innovation, and customer satisfaction drive everything we do. We believe in building lasting relationships through quality service.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Team Section -->
    <section class="team-section">
        <div class="container">
            <div class="section-header">
                <h2>Meet Our Team</h2>
                <p>The dedicated professionals behind ConnectPro Agency</p>
            </div>
            
            <div class="team-grid">
                <div class="team-member">
                    <div class="member-image">
                        <img src="images/team-member-1.svg" alt="Sarah Johnson">
                    </div>
                    <div class="member-info">
                        <h3>Sarah Johnson</h3>
                        <p class="member-role">CEO & Founder</p>
                        <p class="member-bio">With 15 years in business development, Sarah leads our vision of connecting people with quality professionals.</p>
                        <div class="member-social">
                            <a href="#"><i class="fab fa-linkedin"></i></a>
                            <a href="#"><i class="fab fa-twitter"></i></a>
                        </div>
                    </div>
                </div>

                <div class="team-member">
                    <div class="member-image">
                        <img src="images/team-member-2.svg" alt="Michael Chen">
                    </div>
                    <div class="member-info">
                        <h3>Michael Chen</h3>
                        <p class="member-role">CTO</p>
                        <p class="member-bio">Technology expert with a passion for creating seamless user experiences and robust platforms.</p>
                        <div class="member-social">
                            <a href="#"><i class="fab fa-linkedin"></i></a>
                            <a href="#"><i class="fab fa-github"></i></a>
                        </div>
                    </div>
                </div>

                <div class="team-member">
                    <div class="member-image">
                        <img src="images/team-member-3.svg" alt="Emily Rodriguez">
                    </div>
                    <div class="member-info">
                        <h3>Emily Rodriguez</h3>
                        <p class="member-role">Head of Operations</p>
                        <p class="member-bio">Operations specialist ensuring smooth service delivery and exceptional customer satisfaction.</p>
                        <div class="member-social">
                            <a href="#"><i class="fab fa-linkedin"></i></a>
                            <a href="#"><i class="fab fa-twitter"></i></a>
                        </div>
                    </div>
                </div>

                <div class="team-member">
                    <div class="member-image">
                        <img src="images/team-member-4.svg" alt="David Thompson">
                    </div>
                    <div class="member-info">
                        <h3>David Thompson</h3>
                        <p class="member-role">Head of Business Development</p>
                        <p class="member-bio">Experienced in building strategic partnerships and expanding our network of professional services.</p>
                        <div class="member-social">
                            <a href="#"><i class="fab fa-linkedin"></i></a>
                            <a href="#"><i class="fab fa-twitter"></i></a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="about-stats">
        <div class="container">
            <div class="stats-grid">
                <div class="stat-item">
                    <div class="stat-number">2500+</div>
                    <div class="stat-label">Happy Clients</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number">150+</div>
                    <div class="stat-label">Professional Partners</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number">98%</div>
                    <div class="stat-label">Satisfaction Rate</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number">24/7</div>
                    <div class="stat-label">Customer Support</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Why Choose Us -->
    <section class="why-choose-us">
        <div class="container">
            <div class="section-header">
                <h2>Why Choose ConnectPro?</h2>
                <p>We're committed to providing exceptional service and value</p>
            </div>
            
            <div class="features-grid">
                <div class="feature-item">
                    <div class="feature-icon">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <h3>Verified Professionals</h3>
                    <p>Every professional in our network is thoroughly vetted, licensed, and insured for your peace of mind.</p>
                </div>

                <div class="feature-item">
                    <div class="feature-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <h3>Quick Response Time</h3>
                    <p>Get matched with qualified professionals within 24 hours. We understand time is valuable.</p>
                </div>

                <div class="feature-item">
                    <div class="feature-icon">
                        <i class="fas fa-dollar-sign"></i>
                    </div>
                    <h3>Transparent Pricing</h3>
                    <p>No hidden fees, no surprises. Clear, upfront pricing for all services with competitive rates.</p>
                </div>

                <div class="feature-item">
                    <div class="feature-icon">
                        <i class="fas fa-headset"></i>
                    </div>
                    <h3>24/7 Support</h3>
                    <p>Our customer support team is available around the clock to assist you with any questions or concerns.</p>
                </div>

                <div class="feature-item">
                    <div class="feature-icon">
                        <i class="fas fa-star"></i>
                    </div>
                    <h3>Quality Guarantee</h3>
                    <p>We stand behind our services with a satisfaction guarantee. Your success is our priority.</p>
                </div>

                <div class="feature-item">
                    <div class="feature-icon">
                        <i class="fas fa-mobile-alt"></i>
                    </div>
                    <h3>Easy to Use Platform</h3>
                    <p>Our user-friendly platform makes it simple to find, book, and manage professional services.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta-section">
        <div class="container">
            <div class="cta-content">
                <h2>Ready to Connect with Professionals?</h2>
                <p>Join thousands of satisfied clients who trust ConnectPro for their professional service needs.</p>
                <div class="cta-buttons">
                    <a href="services.php" class="btn btn-primary">Explore Services</a>
                    <a href="contact.php" class="btn btn-secondary">Contact Us</a>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <?php include 'includes/footer.php'; ?>

    <script src="js/script.js"></script>
</body>
</html>
