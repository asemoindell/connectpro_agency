<?php
// Include database configuration
require_once 'config/database.php';

// Initialize database connection
$database = new Database();
$db = $database->getConnection();

// Get services from database
$services = [];
$categories = [];

if ($db) {
    try {
        // Get all active services
        $stmt = $db->prepare("SELECT * FROM services WHERE status = 'active' ORDER BY is_featured DESC, category, title");
        $stmt->execute();
        $services = $stmt->fetchAll();
        
        // Get unique categories
        $stmt = $db->prepare("SELECT DISTINCT category FROM services WHERE status = 'active' ORDER BY category");
        $stmt->execute();
        while ($row = $stmt->fetch()) {
            $categories[] = $row['category'];
        }
        
    } catch (Exception $e) {
        $services = [];
        $categories = [];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Our Services - ConnectPro Agency</title>
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
            <h1>Our Professional Services</h1>
            <p>Comprehensive solutions for all your professional service needs</p>
        </div>
    </section>

    <!-- Services Categories Filter -->
    <?php if (!empty($categories)): ?>
    <section class="services-filter">
        <div class="container">
            <div class="filter-buttons">
                <button class="filter-btn active" data-category="all">All Services</button>
                <?php foreach ($categories as $category): ?>
                    <button class="filter-btn" data-category="<?php echo strtolower($category); ?>"><?php echo htmlspecialchars($category); ?></button>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Services Grid -->
    <section class="services-listing">
        <div class="container">
            <?php if (!empty($services)): ?>
                <div class="services-grid">
                    <?php foreach ($services as $service): ?>
                        <div class="service-card detailed" data-category="<?php echo strtolower($service['category']); ?>" id="<?php echo $service['slug']; ?>">
                            <div class="service-header">
                                <div class="service-icon">
                                    <i class="fas fa-<?php echo $service['category'] == 'Travel' ? 'plane' : ($service['category'] == 'Legal' ? 'balance-scale' : ($service['category'] == 'Finance' ? 'calculator' : ($service['category'] == 'Technical' ? 'cogs' : ($service['category'] == 'Events' ? 'calendar-alt' : 'user-tie')))); ?>"></i>
                                </div>
                                <?php if ($service['is_featured']): ?>
                                    <span class="featured-badge">Featured</span>
                                <?php endif; ?>
                            </div>
                            
                            <div class="service-content">
                                <h3><?php echo htmlspecialchars($service['title']); ?></h3>
                                <p class="service-description"><?php echo htmlspecialchars($service['short_description']); ?></p>
                                
                                <div class="service-details">
                                    <h4>What's Included:</h4>
                                    <ul class="service-features">
                                        <?php 
                                        $features = json_decode($service['features'], true);
                                        if ($features):
                                            foreach ($features as $feature): ?>
                                                <li><i class="fas fa-check"></i> <?php echo htmlspecialchars($feature); ?></li>
                                            <?php endforeach;
                                        endif; ?>
                                    </ul>
                                </div>
                                
                                <div class="service-pricing">
                                    <div class="price-range">
                                        <span class="price-label">Starting from</span>
                                        <span class="price"><?php echo htmlspecialchars($service['price_range']); ?></span>
                                    </div>
                                </div>
                                
                                <div class="service-meta">
                                    <span class="service-category"><?php echo htmlspecialchars($service['category']); ?></span>
                                    <span class="service-rating">
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                        5.0
                                    </span>
                                </div>
                                
                                <div class="service-actions">
                                    <a href="contact.php?service=<?php echo urlencode($service['slug']); ?>" class="btn btn-primary">Get Quote</a>
                                    <button class="btn btn-secondary" onclick="showServiceDetails('<?php echo $service['id']; ?>')">Learn More</button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <!-- Fallback content if no services in database -->
                <div class="services-grid">
                    <div class="service-card detailed">
                        <div class="service-header">
                            <div class="service-icon">
                                <i class="fas fa-user-tie"></i>
                            </div>
                            <span class="featured-badge">Featured</span>
                        </div>
                        <div class="service-content">
                            <h3>Professional Agents</h3>
                            <p class="service-description">Connect with certified real estate, insurance, and business agents tailored to your specific requirements.</p>
                            <div class="service-details">
                                <h4>What's Included:</h4>
                                <ul class="service-features">
                                    <li><i class="fas fa-check"></i> Real Estate Agents</li>
                                    <li><i class="fas fa-check"></i> Insurance Agents</li>
                                    <li><i class="fas fa-check"></i> Business Brokers</li>
                                    <li><i class="fas fa-check"></i> Investment Advisors</li>
                                </ul>
                            </div>
                            <div class="service-pricing">
                                <div class="price-range">
                                    <span class="price-label">Starting from</span>
                                    <span class="price">$100 - $500</span>
                                </div>
                            </div>
                            <div class="service-actions">
                                <a href="contact.php" class="btn btn-primary">Get Quote</a>
                                <button class="btn btn-secondary">Learn More</button>
                            </div>
                        </div>
                    </div>

                    <div class="service-card detailed">
                        <div class="service-header">
                            <div class="service-icon">
                                <i class="fas fa-plane"></i>
                            </div>
                            <span class="featured-badge">Featured</span>
                        </div>
                        <div class="service-content">
                            <h3>Travel Services</h3>
                            <p class="service-description">Professional travel planning and booking services for business and leisure trips.</p>
                            <div class="service-details">
                                <h4>What's Included:</h4>
                                <ul class="service-features">
                                    <li><i class="fas fa-check"></i> Flight booking</li>
                                    <li><i class="fas fa-check"></i> Hotel reservations</li>
                                    <li><i class="fas fa-check"></i> Travel insurance</li>
                                    <li><i class="fas fa-check"></i> Itinerary planning</li>
                                </ul>
                            </div>
                            <div class="service-pricing">
                                <div class="price-range">
                                    <span class="price-label">Starting from</span>
                                    <span class="price">$50 - $200</span>
                                </div>
                            </div>
                            <div class="service-actions">
                                <a href="contact.php" class="btn btn-primary">Get Quote</a>
                                <button class="btn btn-secondary">Learn More</button>
                            </div>
                        </div>
                    </div>

                    <div class="service-card detailed">
                        <div class="service-header">
                            <div class="service-icon">
                                <i class="fas fa-balance-scale"></i>
                            </div>
                            <span class="featured-badge">Featured</span>
                        </div>
                        <div class="service-content">
                            <h3>Legal Consultation</h3>
                            <p class="service-description">Professional legal advice and consultation services from experienced attorneys.</p>
                            <div class="service-details">
                                <h4>What's Included:</h4>
                                <ul class="service-features">
                                    <li><i class="fas fa-check"></i> Contract review</li>
                                    <li><i class="fas fa-check"></i> Legal advice</li>
                                    <li><i class="fas fa-check"></i> Document preparation</li>
                                    <li><i class="fas fa-check"></i> Court representation</li>
                                </ul>
                            </div>
                            <div class="service-pricing">
                                <div class="price-range">
                                    <span class="price-label">Starting from</span>
                                    <span class="price">$100 - $500</span>
                                </div>
                            </div>
                            <div class="service-actions">
                                <a href="contact.php" class="btn btn-primary">Get Quote</a>
                                <button class="btn btn-secondary">Learn More</button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- How It Works -->
    <section class="how-it-works">
        <div class="container">
            <div class="section-header">
                <h2>How It Works</h2>
                <p>Simple steps to get connected with the right professional</p>
            </div>
            
            <div class="steps-grid">
                <div class="step-item">
                    <div class="step-number">1</div>
                    <div class="step-content">
                        <h3>Choose Service</h3>
                        <p>Browse our comprehensive list of professional services and select what you need.</p>
                    </div>
                </div>
                
                <div class="step-item">
                    <div class="step-number">2</div>
                    <div class="step-content">
                        <h3>Get Matched</h3>
                        <p>We'll connect you with qualified professionals based on your specific requirements.</p>
                    </div>
                </div>
                
                <div class="step-item">
                    <div class="step-number">3</div>
                    <div class="step-content">
                        <h3>Book & Pay</h3>
                        <p>Schedule your service and make secure payments through our platform.</p>
                    </div>
                </div>
                
                <div class="step-item">
                    <div class="step-number">4</div>
                    <div class="step-content">
                        <h3>Get Results</h3>
                        <p>Receive quality service from verified professionals with our satisfaction guarantee.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta-section">
        <div class="container">
            <div class="cta-content">
                <h2>Ready to Get Started?</h2>
                <p>Connect with qualified professionals today and get the service you need.</p>
                <div class="cta-buttons">
                    <a href="contact.php" class="btn btn-primary">Contact Us Now</a>
                    <a href="about.php" class="btn btn-secondary">Learn More</a>
                </div>
            </div>
        </div>
    </section>

    <!-- Service Details Modal -->
    <div id="serviceModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <div id="serviceModalContent">
                <!-- Service details will be loaded here -->
            </div>
        </div>
    </div>

    <!-- Footer -->
    <?php include 'includes/footer.php'; ?>

    <script src="js/script.js"></script>
    <script>
        // Service filtering functionality
        document.addEventListener('DOMContentLoaded', function() {
            const filterButtons = document.querySelectorAll('.filter-btn');
            const serviceCards = document.querySelectorAll('.service-card');
            
            filterButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const category = this.dataset.category;
                    
                    // Update active button
                    filterButtons.forEach(btn => btn.classList.remove('active'));
                    this.classList.add('active');
                    
                    // Filter services
                    serviceCards.forEach(card => {
                        if (category === 'all' || card.dataset.category === category) {
                            card.style.display = 'block';
                        } else {
                            card.style.display = 'none';
                        }
                    });
                });
            });
        });

        // Service details modal
        function showServiceDetails(serviceId) {
            // You can implement AJAX call to get service details
            const modal = document.getElementById('serviceModal');
            const modalContent = document.getElementById('serviceModalContent');
            
            modalContent.innerHTML = '<div class="loading">Loading service details...</div>';
            modal.style.display = 'block';
            
            // Close modal functionality
            const closeBtn = modal.querySelector('.close');
            closeBtn.onclick = function() {
                modal.style.display = 'none';
            };
            
            window.onclick = function(event) {
                if (event.target === modal) {
                    modal.style.display = 'none';
                }
            };
        }
    </script>
</body>
</html>
