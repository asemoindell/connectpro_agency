/**
 * ConnectPro Agency - Enhanced UX JavaScript
 * Adds loading states, animations, and improved user interactions
 */

document.addEventListener('DOMContentLoaded', function() {
    initializeEnhancements();
});

function initializeEnhancements() {
    // Add loading screen
    addLoadingScreen();
    
    // Enhance navigation
    enhanceNavigation();
    
    // Add button loading states
    enhanceButtons();
    
    // Improve form interactions
    enhanceFormInteractions();
    
    // Add scroll animations
    addScrollAnimations();
    
    // Initialize stats counter
    initializeStatsCounter();
    
    // Add accessibility improvements
    addAccessibilityFeatures();
}

// Loading Screen Management
function addLoadingScreen() {
    // Create loading overlay if it doesn't exist
    if (!document.querySelector('.loading-overlay')) {
        const loadingOverlay = document.createElement('div');
        loadingOverlay.className = 'loading-overlay';
        loadingOverlay.innerHTML = `
            <div class="loading-spinner"></div>
            <div class="loading-text">Loading ConnectPro Agency...</div>
        `;
        document.body.appendChild(loadingOverlay);
        
        // Hide loading screen when page is fully loaded
        window.addEventListener('load', function() {
            setTimeout(() => {
                loadingOverlay.classList.add('hidden');
                setTimeout(() => {
                    loadingOverlay.remove();
                }, 300);
            }, 500);
        });
    }
}

// Enhanced Navigation
function enhanceNavigation() {
    // Smooth scroll for navigation links
    document.querySelectorAll('a[href^="#"]').forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const targetId = this.getAttribute('href').substring(1);
            const targetElement = document.getElementById(targetId);
            
            if (targetElement) {
                targetElement.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });
    
    // Active navigation highlight
    const sections = document.querySelectorAll('section[id]');
    const navLinks = document.querySelectorAll('.nav-link');
    
    function highlightActiveSection() {
        let current = '';
        sections.forEach(section => {
            const sectionTop = section.offsetTop;
            const sectionHeight = section.clientHeight;
            if (scrollY >= (sectionTop - 200)) {
                current = section.getAttribute('id');
            }
        });
        
        navLinks.forEach(link => {
            link.classList.remove('active');
            if (link.getAttribute('href') === '#' + current) {
                link.classList.add('active');
            }
        });
    }
    
    window.addEventListener('scroll', highlightActiveSection);
}

// Enhanced Button Interactions
function enhanceButtons() {
    // Add loading state to form submission buttons
    document.querySelectorAll('form').forEach(form => {
        form.addEventListener('submit', function(e) {
            const submitBtn = form.querySelector('button[type="submit"], input[type="submit"]');
            if (submitBtn && !submitBtn.disabled) {
                const originalText = submitBtn.innerHTML || submitBtn.value;
                
                // Add loading state
                submitBtn.disabled = true;
                submitBtn.classList.add('btn-loading');
                
                if (submitBtn.tagName === 'BUTTON') {
                    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
                } else {
                    submitBtn.value = 'Processing...';
                }
                
                // Reset if form validation fails
                setTimeout(() => {
                    if (form.checkValidity && !form.checkValidity()) {
                        submitBtn.disabled = false;
                        submitBtn.classList.remove('btn-loading');
                        if (submitBtn.tagName === 'BUTTON') {
                            submitBtn.innerHTML = originalText;
                        } else {
                            submitBtn.value = originalText;
                        }
                    }
                }, 100);
            }
        });
    });
    
    // Add ripple effect to buttons
    document.querySelectorAll('.btn-primary, .btn-book, .service-btn').forEach(button => {
        button.addEventListener('click', function(e) {
            const ripple = document.createElement('span');
            const rect = this.getBoundingClientRect();
            const size = Math.max(rect.width, rect.height);
            const x = e.clientX - rect.left - size / 2;
            const y = e.clientY - rect.top - size / 2;
            
            ripple.style.width = ripple.style.height = size + 'px';
            ripple.style.left = x + 'px';
            ripple.style.top = y + 'px';
            ripple.classList.add('ripple');
            
            this.appendChild(ripple);
            
            setTimeout(() => {
                ripple.remove();
            }, 600);
        });
    });
}

// Enhanced Form Interactions
function enhanceFormInteractions() {
    // Auto-resize textareas
    document.querySelectorAll('textarea').forEach(textarea => {
        function resizeTextarea() {
            textarea.style.height = 'auto';
            textarea.style.height = textarea.scrollHeight + 'px';
        }
        
        textarea.addEventListener('input', resizeTextarea);
        resizeTextarea(); // Initial resize
    });
    
    // Enhanced input focus effects
    document.querySelectorAll('input, select, textarea').forEach(input => {
        const parent = input.closest('.form-group');
        
        input.addEventListener('focus', function() {
            if (parent) parent.classList.add('focused');
        });
        
        input.addEventListener('blur', function() {
            if (parent) parent.classList.remove('focused');
        });
        
        // Add floating labels if empty
        input.addEventListener('input', function() {
            if (parent) {
                if (this.value) {
                    parent.classList.add('has-value');
                } else {
                    parent.classList.remove('has-value');
                }
            }
        });
    });
}

// Scroll Animations
function addScrollAnimations() {
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };
    
    const observer = new IntersectionObserver(function(entries) {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('animate-in');
            }
        });
    }, observerOptions);
    
    // Observe elements for animation
    document.querySelectorAll('.service-card, .testimonial-card, .stat-item, .about-feature').forEach(el => {
        observer.observe(el);
    });
}

// Stats Counter Animation
function initializeStatsCounter() {
    const stats = document.querySelectorAll('.stat-number');
    let statsAnimated = false;
    
    function animateStats() {
        if (statsAnimated) return;
        
        stats.forEach(stat => {
            const target = parseInt(stat.textContent.replace(/[^\d]/g, ''));
            if (isNaN(target)) return;
            
            let current = 0;
            const increment = target / 50;
            const timer = setInterval(() => {
                current += increment;
                if (current >= target) {
                    current = target;
                    clearInterval(timer);
                }
                
                if (stat.textContent.includes('+')) {
                    stat.textContent = Math.floor(current).toLocaleString() + '+';
                } else {
                    stat.textContent = Math.floor(current).toLocaleString();
                }
            }, 40);
        });
        
        statsAnimated = true;
    }
    
    // Start animation when stats section is visible
    const statsSection = document.querySelector('.stats');
    if (statsSection) {
        const observer = new IntersectionObserver(function(entries) {
            if (entries[0].isIntersecting) {
                animateStats();
                observer.disconnect();
            }
        });
        observer.observe(statsSection);
    }
}

// Accessibility Features
function addAccessibilityFeatures() {
    // Add skip link
    if (!document.querySelector('.skip-link')) {
        const skipLink = document.createElement('a');
        skipLink.href = '#main-content';
        skipLink.className = 'skip-link';
        skipLink.textContent = 'Skip to main content';
        document.body.insertBefore(skipLink, document.body.firstChild);
    }
    
    // Add main content ID if not exists
    const mainContent = document.querySelector('main, .main-content, section');
    if (mainContent && !mainContent.id) {
        mainContent.id = 'main-content';
    }
    
    // Keyboard navigation for custom elements
    document.querySelectorAll('.service-card, .urgency-option').forEach(element => {
        element.setAttribute('tabindex', '0');
        element.setAttribute('role', 'button');
        
        element.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                this.click();
            }
        });
    });
    
    // Announce page changes to screen readers
    function announcePageChange(message) {
        const announcement = document.createElement('div');
        announcement.setAttribute('aria-live', 'polite');
        announcement.setAttribute('aria-atomic', 'true');
        announcement.className = 'sr-only';
        announcement.textContent = message;
        document.body.appendChild(announcement);
        
        setTimeout(() => {
            announcement.remove();
        }, 1000);
    }
    
    // Announce form validation results
    document.addEventListener('formValidationResult', function(e) {
        if (e.detail.success) {
            announcePageChange('Form submitted successfully');
        } else {
            announcePageChange('Please correct the errors in the form');
        }
    });
}

// Utility Functions
function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.innerHTML = `
        <div class="notification-content">
            <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'}"></i>
            <span>${message}</span>
        </div>
        <button class="notification-close" onclick="this.parentElement.remove()">
            <i class="fas fa-times"></i>
        </button>
    `;
    
    document.body.appendChild(notification);
    
    // Auto-remove after 5 seconds
    setTimeout(() => {
        if (notification.parentElement) {
            notification.remove();
        }
    }, 5000);
}

// Add CSS for ripple effect
const rippleCSS = `
    .ripple {
        position: absolute;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.3);
        transform: scale(0);
        animation: ripple 0.6s linear;
        pointer-events: none;
    }
    
    @keyframes ripple {
        to {
            transform: scale(4);
            opacity: 0;
        }
    }
    
    .sr-only {
        position: absolute;
        width: 1px;
        height: 1px;
        padding: 0;
        margin: -1px;
        overflow: hidden;
        clip: rect(0, 0, 0, 0);
        white-space: nowrap;
        border: 0;
    }
    
    .notification {
        position: fixed;
        top: 20px;
        right: 20px;
        background: white;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        padding: 1rem;
        z-index: 10000;
        display: flex;
        align-items: center;
        justify-content: space-between;
        min-width: 300px;
        animation: slideInFromRight 0.3s ease-out;
    }
    
    @keyframes slideInFromRight {
        from { transform: translateX(100%); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }
    
    .notification-success { border-left: 4px solid #28a745; }
    .notification-error { border-left: 4px solid #dc3545; }
    .notification-info { border-left: 4px solid #17a2b8; }
`;

// Inject CSS
const style = document.createElement('style');
style.textContent = rippleCSS;
document.head.appendChild(style);
