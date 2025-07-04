// User Authentication JavaScript
document.addEventListener('DOMContentLoaded', function() {
    // Check if user is already logged in
    const isLoggedIn = localStorage.getItem('userLoggedIn');
    const currentPage = window.location.pathname;
    
    if (isLoggedIn && (currentPage.includes('user/login.php') || currentPage.includes('user/register.php'))) {
        window.location.href = '/Agency/index.php';
    }
    
    // Initialize forms
    initializeUserLoginForm();
    initializeUserRegisterForm();
    initializePasswordToggles();
    initializePasswordStrength();
});

// API Configuration for user authentication
const USER_API_BASE_URL = '../api/index.php';

// API Helper functions for user auth
async function userApiRequest(endpoint, method = 'GET', data = null) {
    const url = `${USER_API_BASE_URL}/${endpoint}`;
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
        const response = await fetch(url, options);
        const result = await response.json();
        
        if (!response.ok) {
            throw new Error(result.error || 'API request failed');
        }
        
        return result;
    } catch (error) {
        console.error('User API Error:', error);
        throw error;
    }
}

// Initialize user login form
function initializeUserLoginForm() {
    const loginForm = document.getElementById('userLoginForm');
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
            // Call API for user authentication
            const result = await userApiRequest('user-auth?action=login', 'POST', {
                email: email,
                password: password
            });
            
            if (result.success) {
                // Store authentication data
                localStorage.setItem('userLoggedIn', 'true');
                localStorage.setItem('userData', JSON.stringify(result.user));
                localStorage.setItem('userToken', result.token);
                
                if (remember) {
                    localStorage.setItem('rememberUser', 'true');
                }
                
                showNotification('Login successful! Welcome back!', 'success');
                
                setTimeout(() => {
                    window.location.href = '../index.php';
                }, 1500);
            }
        } catch (error) {
            showNotification(error.message || 'Login failed. Please try again.', 'error');
        } finally {
            submitButton.textContent = originalText;
            submitButton.disabled = false;
        }
    });
}

// Initialize user register form
function initializeUserRegisterForm() {
    const registerForm = document.getElementById('userRegisterForm');
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
            // Call API for user registration
            const result = await userApiRequest('user-auth?action=register', 'POST', {
                firstName: formData.get('firstName'),
                lastName: formData.get('lastName'),
                email: formData.get('email'),
                phone: formData.get('phone'),
                password: password,
                newsletter: formData.get('newsletter') ? true : false
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
            submitButton.textContent = originalText;
            submitButton.disabled = false;
        }
    });
}

// Password toggle functionality
function initializePasswordToggles() {
    document.querySelectorAll('.password-toggle').forEach(toggle => {
        toggle.addEventListener('click', function(e) {
            e.preventDefault();
            const input = this.parentElement.querySelector('input');
            const icon = this.querySelector('i');
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });
    });
}

function togglePassword(inputId) {
    const input = document.getElementById(inputId);
    const icon = input.parentElement.querySelector('.password-toggle i');
    
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}

// Password strength indicator
function initializePasswordStrength() {
    const passwordInput = document.getElementById('password');
    const strengthIndicator = document.getElementById('passwordStrength');
    
    if (!passwordInput || !strengthIndicator) return;
    
    passwordInput.addEventListener('input', function() {
        const password = this.value;
        const strength = calculatePasswordStrength(password);
        
        strengthIndicator.className = `password-strength strength-${strength.level}`;
        strengthIndicator.textContent = strength.text;
    });
}

function calculatePasswordStrength(password) {
    let score = 0;
    
    if (password.length >= 8) score++;
    if (password.match(/[a-z]/)) score++;
    if (password.match(/[A-Z]/)) score++;
    if (password.match(/[0-9]/)) score++;
    if (password.match(/[^a-zA-Z0-9]/)) score++;
    
    const levels = [
        { level: 'weak', text: 'Weak password' },
        { level: 'weak', text: 'Weak password' },
        { level: 'medium', text: 'Medium password' },
        { level: 'good', text: 'Good password' },
        { level: 'strong', text: 'Strong password' }
    ];
    
    return levels[score] || levels[0];
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
        animation: slideIn 0.3s ease-out;
    `;
    
    document.body.appendChild(notification);
    
    // Auto remove after 5 seconds
    setTimeout(() => {
        if (notification.parentNode) {
            notification.style.animation = 'slideOut 0.3s ease-in';
            setTimeout(() => notification.remove(), 300);
        }
    }, 5000);
}

function getNotificationIcon(type) {
    const icons = {
        'success': 'fa-check-circle',
        'error': 'fa-exclamation-circle',
        'warning': 'fa-exclamation-triangle',
        'info': 'fa-info-circle'
    };
    return icons[type] || icons.info;
}

function getNotificationColor(type) {
    const colors = {
        'success': '#28a745',
        'error': '#dc3545',
        'warning': '#ffc107',
        'info': '#17a2b8'
    };
    return colors[type] || colors.info;
}

function closeNotification(button) {
    const notification = button.closest('.notification');
    notification.style.animation = 'slideOut 0.3s ease-in';
    setTimeout(() => notification.remove(), 300);
}

// Auto-fill remembered credentials
document.addEventListener('DOMContentLoaded', function() {
    if (localStorage.getItem('rememberUser') && window.location.pathname.includes('user/login.php')) {
        const emailInput = document.getElementById('email');
        const rememberCheckbox = document.querySelector('input[name="remember"]');
        
        if (emailInput && localStorage.getItem('rememberedUserEmail')) {
            emailInput.value = localStorage.getItem('rememberedUserEmail');
            if (rememberCheckbox) {
                rememberCheckbox.checked = true;
            }
        }
    }
});

// User logout function
function userLogout() {
    localStorage.removeItem('userLoggedIn');
    localStorage.removeItem('userData');
    localStorage.removeItem('userToken');
    localStorage.removeItem('rememberUser');
    localStorage.removeItem('rememberedUserEmail');
    
    showNotification('Logged out successfully!', 'success');
    
    setTimeout(() => {
        window.location.href = '/Agency/user/login.php';
    }, 1500);
}
