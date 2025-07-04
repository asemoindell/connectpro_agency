// Mobile Navigation Toggle
const mobileMenu = document.getElementById('mobile-menu');
const navMenu = document.querySelector('.nav-menu');

mobileMenu.addEventListener('click', () => {
    mobileMenu.classList.toggle('active');
    navMenu.classList.toggle('active');
});

// Close mobile menu when clicking on a link
document.querySelectorAll('.nav-link').forEach(link => {
    link.addEventListener('click', () => {
        mobileMenu.classList.remove('active');
        navMenu.classList.remove('active');
    });
});

// Smooth scrolling for navigation links
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
            target.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        }
    });
});

// Navbar background change on scroll
window.addEventListener('scroll', () => {
    const navbar = document.querySelector('.navbar');
    if (window.scrollY > 100) {
        navbar.style.background = 'rgba(255, 255, 255, 0.98)';
        navbar.style.boxShadow = '0 2px 20px rgba(0, 0, 0, 0.15)';
    } else {
        navbar.style.background = 'rgba(255, 255, 255, 0.95)';
        navbar.style.boxShadow = '0 2px 20px rgba(0, 0, 0, 0.1)';
    }
});

// Contact Form Handling
const contactForm = document.getElementById('contactForm');
contactForm.addEventListener('submit', async function(e) {
    e.preventDefault();
    
    // Get form data
    const formData = new FormData(this);
    const formValues = Object.fromEntries(formData);
    
    // Simple validation
    if (!formValues.name || !formValues.email || !formValues.service || !formValues.message) {
        showNotification('Please fill in all required fields.', 'error');
        return;
    }
    
    // Email validation
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(formValues.email)) {
        showNotification('Please enter a valid email address.', 'error');
        return;
    }
    
    // Show loading state
    const submitButton = this.querySelector('button[type="submit"]');
    const originalText = submitButton.textContent;
    submitButton.textContent = 'Sending...';
    submitButton.disabled = true;
    
    try {
        // Submit to database
        const response = await fetch('api/index.php/inquiries', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                name: formValues.name,
                email: formValues.email,
                phone: formValues.phone || '',
                service: formValues.service,
                message: formValues.message
            })
        });
        
        const result = await response.json();
        
        if (response.ok && result.success) {
            showNotification('Thank you for your message! We will get back to you soon.', 'success');
            this.reset();
        } else {
            throw new Error(result.error || 'Failed to send message');
        }
    } catch (error) {
        console.error('Contact form error:', error);
        showNotification('Sorry, there was an error sending your message. Please try again.', 'error');
    } finally {
        // Reset button state
        submitButton.textContent = originalText;
        submitButton.disabled = false;
    }
});

// Newsletter form handling
const newsletterForm = document.querySelector('.newsletter-form');
newsletterForm.addEventListener('submit', function(e) {
    e.preventDefault();
    
    const email = this.querySelector('input[type="email"]').value;
    
    if (!email) {
        showNotification('Please enter your email address.', 'error');
        return;
    }
    
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(email)) {
        showNotification('Please enter a valid email address.', 'error');
        return;
    }
    
    showNotification('Successfully subscribed to our newsletter!', 'success');
    this.reset();
});

// Notification system
function showNotification(message, type = 'info') {
    // Remove existing notifications
    const existingNotifications = document.querySelectorAll('.notification');
    existingNotifications.forEach(notification => notification.remove());
    
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.innerHTML = `
        <span>${message}</span>
        <button class="notification-close">&times;</button>
    `;
    
    // Add styles
    notification.style.cssText = `
        position: fixed;
        top: 100px;
        right: 20px;
        background: ${type === 'success' ? '#4CAF50' : type === 'error' ? '#f44336' : '#2196F3'};
        color: white;
        padding: 15px 20px;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        z-index: 10000;
        display: flex;
        align-items: center;
        gap: 10px;
        max-width: 350px;
        animation: slideInRight 0.3s ease;
    `;
    
    // Add close button functionality
    const closeBtn = notification.querySelector('.notification-close');
    closeBtn.style.cssText = `
        background: none;
        border: none;
        color: white;
        font-size: 20px;
        cursor: pointer;
        padding: 0;
        margin-left: 10px;
    `;
    
    closeBtn.addEventListener('click', () => {
        notification.style.animation = 'slideOutRight 0.3s ease';
        setTimeout(() => notification.remove(), 300);
    });
    
    // Add to page
    document.body.appendChild(notification);
    
    // Auto remove after 5 seconds
    setTimeout(() => {
        if (notification.parentNode) {
            notification.style.animation = 'slideOutRight 0.3s ease';
            setTimeout(() => notification.remove(), 300);
        }
    }, 5000);
}

// Add CSS animations for notifications
const style = document.createElement('style');
style.textContent = `
    @keyframes slideInRight {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    
    @keyframes slideOutRight {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(100%);
            opacity: 0;
        }
    }
`;
document.head.appendChild(style);

// Service card interactions
document.querySelectorAll('.service-btn').forEach(btn => {
    btn.addEventListener('click', function(e) {
        e.preventDefault();
        const serviceName = this.closest('.service-card').querySelector('h3').textContent;
        showNotification(`Redirecting to ${serviceName} booking...`, 'info');
        
        // Simulate navigation delay
        setTimeout(() => {
            showNotification(`${serviceName} booking system would open here in a real application.`, 'info');
        }, 1500);
    });
});

// Counter animation for stats
function animateCounters() {
    const counters = document.querySelectorAll('.stat-number');
    const speed = 200; // The lower the slower
    
    counters.forEach(counter => {
        const target = parseInt(counter.textContent.replace(/[^0-9]/g, ''));
        const suffix = counter.textContent.replace(/[0-9]/g, '');
        let count = 0;
        
        const updateCount = () => {
            const increment = target / speed;
            if (count < target) {
                count += increment;
                counter.textContent = Math.ceil(count) + suffix;
                setTimeout(updateCount, 1);
            } else {
                counter.textContent = target + suffix;
            }
        };
        
        updateCount();
    });
}

// Intersection Observer for animations
const observerOptions = {
    threshold: 0.1,
    rootMargin: '0px 0px -50px 0px'
};

const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            if (entry.target.classList.contains('stats')) {
                animateCounters();
                observer.unobserve(entry.target);
            }
            
            // Add fade-in animation to elements
            entry.target.style.opacity = '1';
            entry.target.style.transform = 'translateY(0)';
        }
    });
}, observerOptions);

// Observe elements for animation
document.addEventListener('DOMContentLoaded', () => {
    const animatedElements = document.querySelectorAll('.service-card, .testimonial-card, .stats');
    
    animatedElements.forEach(el => {
        el.style.opacity = '0';
        el.style.transform = 'translateY(30px)';
        el.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
        observer.observe(el);
    });
});

// Search functionality (for future implementation)
function initializeSearch() {
    const searchButton = document.createElement('button');
    searchButton.innerHTML = '<i class="fas fa-search"></i>';
    searchButton.className = 'search-toggle';
    searchButton.style.cssText = `
        position: fixed;
        bottom: 30px;
        right: 30px;
        width: 60px;
        height: 60px;
        border-radius: 50%;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border: none;
        color: white;
        font-size: 20px;
        cursor: pointer;
        box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        transition: transform 0.3s ease;
        z-index: 1000;
    `;
    
    searchButton.addEventListener('mouseenter', () => {
        searchButton.style.transform = 'scale(1.1)';
    });
    
    searchButton.addEventListener('mouseleave', () => {
        searchButton.style.transform = 'scale(1)';
    });
    
    searchButton.addEventListener('click', () => {
        showNotification('Search functionality coming soon!', 'info');
    });
    
    document.body.appendChild(searchButton);
}

// Initialize search button
document.addEventListener('DOMContentLoaded', initializeSearch);

// Lazy loading for images (when added)
function lazyLoadImages() {
    const images = document.querySelectorAll('img[data-src]');
    
    const imageObserver = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const img = entry.target;
                img.src = img.dataset.src;
                img.removeAttribute('data-src');
                imageObserver.unobserve(img);
            }
        });
    });
    
    images.forEach(img => imageObserver.observe(img));
}

// Initialize lazy loading
document.addEventListener('DOMContentLoaded', lazyLoadImages);

// Testimonial carousel (simple version)
function initTestimonialCarousel() {
    const testimonials = document.querySelectorAll('.testimonial-card');
    let currentIndex = 0;
    
    if (testimonials.length <= 1) return;
    
    // Auto-rotate testimonials every 5 seconds
    setInterval(() => {
        testimonials[currentIndex].style.opacity = '0.7';
        currentIndex = (currentIndex + 1) % testimonials.length;
        testimonials[currentIndex].style.opacity = '1';
        testimonials[currentIndex].style.transform = 'scale(1.02)';
        
        setTimeout(() => {
            testimonials.forEach((testimonial, index) => {
                if (index !== currentIndex) {
                    testimonial.style.transform = 'scale(1)';
                }
            });
        }, 300);
    }, 5000);
}

// Initialize testimonial carousel
document.addEventListener('DOMContentLoaded', initTestimonialCarousel);

// Service filtering (for future enhancement)
function initServiceFilter() {
    const filterButtons = document.createElement('div');
    filterButtons.className = 'service-filters';
    filterButtons.innerHTML = `
        <button class="filter-btn active" data-filter="all">All Services</button>
        <button class="filter-btn" data-filter="booking">Booking</button>
        <button class="filter-btn" data-filter="professional">Professional</button>
        <button class="filter-btn" data-filter="consultation">Consultation</button>
    `;
    
    const servicesSection = document.querySelector('.services');
    const sectionHeader = servicesSection.querySelector('.section-header');
    
    // Add filter buttons after section header
    sectionHeader.after(filterButtons);
    
    // Add styles for filter buttons
    const filterStyle = document.createElement('style');
    filterStyle.textContent = `
        .service-filters {
            display: flex;
            justify-content: center;
            gap: 1rem;
            margin-bottom: 3rem;
            flex-wrap: wrap;
        }
        
        .filter-btn {
            padding: 10px 20px;
            border: 2px solid #667eea;
            background: transparent;
            color: #667eea;
            border-radius: 25px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 500;
        }
        
        .filter-btn:hover,
        .filter-btn.active {
            background: #667eea;
            color: white;
        }
        
        @media (max-width: 768px) {
            .service-filters {
                gap: 0.5rem;
            }
            
            .filter-btn {
                padding: 8px 16px;
                font-size: 14px;
            }
        }
    `;
    document.head.appendChild(filterStyle);
    
    // Filter functionality
    filterButtons.addEventListener('click', (e) => {
        if (e.target.classList.contains('filter-btn')) {
            // Update active button
            filterButtons.querySelectorAll('.filter-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            e.target.classList.add('active');
            
            // Show notification (since we don't have actual filtering logic yet)
            const filter = e.target.dataset.filter;
            showNotification(`Filtering services by: ${filter}`, 'info');
        }
    });
}

// Initialize service filter
document.addEventListener('DOMContentLoaded', initServiceFilter);
