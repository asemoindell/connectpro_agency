// Authentication JavaScript
document.addEventListener('DOMContentLoaded', function() {
    // Check if user is already logged in
    const isLoggedIn = localStorage.getItem('adminLoggedIn');
    const currentPage = window.location.pathname;
    
    if (isLoggedIn && (currentPage.includes('admin/login.php') || currentPage.includes('admin/register.php'))) {
        window.location.href = '/Agency/admin/dashboard.php';
    } else if (!isLoggedIn && currentPage.includes('admin/dashboard.php')) {
        window.location.href = '/Agency/admin/login.php';
    }
    
    // Initialize forms
    initializeLoginForm();
    initializeRegisterForm();
    initializePasswordToggles();
    initializePasswordStrength();
});

// API Configuration
const API_BASE_URL = window.location.pathname.includes('/admin/') ? '../api/index.php' : 'api/index.php';

// API Helper functions
async function apiRequest(endpoint, method = 'GET', data = null) {
    // Clean up the URL construction to avoid double slashes
    const baseUrl = API_BASE_URL.endsWith('/') ? API_BASE_URL.slice(0, -1) : API_BASE_URL;
    const cleanEndpoint = endpoint.startsWith('/') ? endpoint : `/${endpoint}`;
    const url = `${baseUrl}${cleanEndpoint}`;
    
    const options = {
        method: method,
        headers: {
            'Content-Type': 'application/json',
        }
    };
    
    if (data) {
        options.body = JSON.stringify(data);
    }
    
    try {
        console.log('API Request:', method, url, data); // Debug logging
        const response = await fetch(url, options);
        console.log('API Response status:', response.status); // Debug logging
        
        // Check if response is actually JSON
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            const text = await response.text();
            console.error('Non-JSON response:', text);
            throw new Error('Server returned invalid response format');
        }
        
        const result = await response.json();
        
        if (!response.ok) {
            throw new Error(result.error || 'API request failed');
        }
        
        return result;
    } catch (error) {
        console.error('API Error:', error);
        throw error;
    }
}

// Initialize login form
function initializeLoginForm() {
    const loginForm = document.getElementById('loginForm');
    if (!loginForm) return;
    
    loginForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const email = formData.get('email');
        const password = formData.get('password');
        const remember = formData.get('remember');
        
        // Show loading state
        const submitButton = this.querySelector('button[type="submit"]');
        const originalText = submitButton.textContent;
        submitButton.textContent = 'Signing in...';
        submitButton.disabled = true;
        
        try {
            // Call API for authentication
            const result = await apiRequest('auth?action=login', 'POST', {
                email: email,
                password: password
            });
            
            if (result.success) {
                // Store authentication data
                localStorage.setItem('adminLoggedIn', 'true');
                localStorage.setItem('adminUser', JSON.stringify(result.user));
                localStorage.setItem('adminToken', result.token);
                
                if (remember) {
                    localStorage.setItem('rememberAdmin', 'true');
                }
                
                showNotification('Login successful! Redirecting...', 'success');
                
                setTimeout(() => {
                    window.location.href = 'dashboard.php';
                }, 1500);
            }
        } catch (error) {
            showNotification(error.message || 'Login failed. Please try again.', 'error');
        } finally {
            // Reset button state
            submitButton.textContent = originalText;
            submitButton.disabled = false;
        }
    });
}

// Initialize register form
function initializeRegisterForm() {
    const registerForm = document.getElementById('registerForm');
    if (!registerForm) return;
    
    registerForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const password = formData.get('password');
        const confirmPassword = formData.get('confirmPassword');
        
        // Validate passwords match
        if (password !== confirmPassword) {
            showNotification('Passwords do not match!', 'error');
            return;
        }
        
        // Validate password strength
        if (password.length < 6) {
            showNotification('Password must be at least 6 characters long!', 'error');
            return;
        }
        
        // Check if terms are accepted
        const terms = formData.get('terms');
        if (!terms) {
            showNotification('Please accept the Terms of Service!', 'error');
            return;
        }
        
        // Show loading state
        const submitButton = this.querySelector('button[type="submit"]');
        const originalText = submitButton.textContent;
        submitButton.textContent = 'Creating Account...';
        submitButton.disabled = true;
        
        try {
            // Call API for registration
            const result = await apiRequest('auth?action=register', 'POST', {
                firstName: formData.get('firstName'),
                lastName: formData.get('lastName'),
                email: formData.get('email'),
                password: password,
                role: formData.get('role') || 'content-admin'
            });
            
            if (result.success) {
                showNotification('Registration successful! Please login with your credentials.', 'success');
                
                setTimeout(() => {
                    window.location.href = 'login.php';
                }, 2000);
            }
        } catch (error) {
            showNotification(error.message || 'Registration failed. Please try again.', 'error');
        } finally {
            // Reset button state
            submitButton.textContent = originalText;
            submitButton.disabled = false;
        }
    });
}

// Password toggle functionality
function initializePasswordToggles() {
    document.addEventListener('click', function(e) {
        if (e.target.closest('.password-toggle')) {
            const button = e.target.closest('.password-toggle');
            const input = button.parentElement.querySelector('input');
            const icon = button.querySelector('i');
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.className = 'fas fa-eye-slash';
            } else {
                input.type = 'password';
                icon.className = 'fas fa-eye';
            }
        }
    });
}

// Password toggle function (for onclick)
function togglePassword(inputId) {
    const input = document.getElementById(inputId);
    const button = input.parentElement.querySelector('.password-toggle');
    const icon = button.querySelector('i');
    
    if (input.type === 'password') {
        input.type = 'text';
        icon.className = 'fas fa-eye-slash';
    } else {
        input.type = 'password';
        icon.className = 'fas fa-eye';
    }
}

// Password strength checker
function initializePasswordStrength() {
    const passwordInput = document.getElementById('password');
    if (!passwordInput) return;
    
    passwordInput.addEventListener('input', function() {
        const password = this.value;
        const strengthFill = document.querySelector('.strength-fill');
        const strengthText = document.querySelector('.strength-text');
        
        if (!strengthFill || !strengthText) return;
        
        const strength = calculatePasswordStrength(password);
        
        strengthFill.style.width = strength.percentage + '%';
        strengthFill.style.background = strength.color;
        strengthText.textContent = strength.text;
    });
}

// Calculate password strength
function calculatePasswordStrength(password) {
    let score = 0;
    let feedback = [];
    
    if (password.length >= 8) score += 25;
    else feedback.push('at least 8 characters');
    
    if (/[a-z]/.test(password)) score += 25;
    else feedback.push('lowercase letter');
    
    if (/[A-Z]/.test(password)) score += 25;
    else feedback.push('uppercase letter');
    
    if (/[0-9]/.test(password)) score += 25;
    else feedback.push('number');
    
    if (/[^A-Za-z0-9]/.test(password)) score += 10;
    
    let strength = { percentage: score, color: '#dc3545', text: 'Very Weak' };
    
    if (score >= 90) {
        strength = { percentage: score, color: '#28a745', text: 'Very Strong' };
    } else if (score >= 70) {
        strength = { percentage: score, color: '#28a745', text: 'Strong' };
    } else if (score >= 50) {
        strength = { percentage: score, color: '#ffc107', text: 'Fair' };
    } else if (score >= 25) {
        strength = { percentage: score, color: '#fd7e14', text: 'Weak' };
    }
    
    return strength;
}

// Notification system
function showNotification(message, type = 'info') {
    // Remove existing notifications
    const existingNotifications = document.querySelectorAll('.notification');
    existingNotifications.forEach(notification => notification.remove());
    
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.innerHTML = `
        <div class="notification-content">
            <i class="fas ${getNotificationIcon(type)}"></i>
            <span>${message}</span>
        </div>
        <button class="notification-close" onclick="closeNotification(this)">
            <i class="fas fa-times"></i>
        </button>
    `;
    
    // Add styles
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: ${getNotificationColor(type)};
        color: white;
        padding: 1rem 1.5rem;
        border-radius: 10px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        z-index: 10000;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        max-width: 400px;
        animation: slideInRight 0.3s ease;
    `;
    
    // Style notification content
    const content = notification.querySelector('.notification-content');
    content.style.cssText = `
        display: flex;
        align-items: center;
        gap: 0.75rem;
    `;
    
    // Style close button
    const closeBtn = notification.querySelector('.notification-close');
    closeBtn.style.cssText = `
        background: none;
        border: none;
        color: white;
        font-size: 1rem;
        cursor: pointer;
        padding: 0.25rem;
        border-radius: 3px;
        opacity: 0.8;
        transition: opacity 0.3s ease;
    `;
    
    closeBtn.addEventListener('mouseenter', () => closeBtn.style.opacity = '1');
    closeBtn.addEventListener('mouseleave', () => closeBtn.style.opacity = '0.8');
    
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

function getNotificationIcon(type) {
    switch (type) {
        case 'success': return 'fa-check-circle';
        case 'error': return 'fa-exclamation-circle';
        case 'warning': return 'fa-exclamation-triangle';
        default: return 'fa-info-circle';
    }
}

function getNotificationColor(type) {
    switch (type) {
        case 'success': return '#28a745';
        case 'error': return '#dc3545';
        case 'warning': return '#ffc107';
        default: return '#17a2b8';
    }
}

function closeNotification(button) {
    const notification = button.closest('.notification');
    notification.style.animation = 'slideOutRight 0.3s ease';
    setTimeout(() => notification.remove(), 300);
}

// Add CSS animations
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

// Logout function
function logout() {
    localStorage.removeItem('adminLoggedIn');
    localStorage.removeItem('adminUser');
    localStorage.removeItem('rememberAdmin');
    
    showNotification('Logged out successfully!', 'success');
    
    setTimeout(() => {
        window.location.href = '/Agency/admin/login.php';
    }, 1500);
}

// Auto-fill remembered credentials
document.addEventListener('DOMContentLoaded', function() {
    if (localStorage.getItem('rememberAdmin') && window.location.pathname.includes('admin/login.php')) {
        const emailInput = document.getElementById('email');
        const rememberCheckbox = document.getElementById('remember');
        
        if (emailInput && rememberCheckbox) {
            emailInput.value = 'admin@connectpro.com';
            rememberCheckbox.checked = true;
        }
    }
});
