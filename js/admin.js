// Admin Dashboard JavaScript
// API Configuration
const API_BASE_URL = '../api/index.php';

// API Helper functions
async function apiRequest(endpoint, method = 'GET', data = null) {
    const url = `${API_BASE_URL}/${endpoint}`;
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
        console.error('API Error:', error);
        throw error;
    }
}

document.addEventListener('DOMContentLoaded', function() {
    // Check authentication
    checkAuthentication();
    
    // Initialize dashboard
    initializeDashboard();
    initializeSidebar();
    initializeUserInfo();
    initializeContentManagement();
    initializeModals();
    
    // Load dashboard data
    loadDashboardData();
});

// Check if user is authenticated
function checkAuthentication() {
    const isLoggedIn = localStorage.getItem('adminLoggedIn');
    const currentPage = window.location.pathname;
    
    if (!isLoggedIn && currentPage.includes('admin/dashboard.php')) {
        window.location.href = '/Agency/admin/login.php';
        return;
    }
}

// Initialize dashboard
function initializeDashboard() {
    // Set up navigation
    const navLinks = document.querySelectorAll('.nav-link[data-section]');
    navLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const sectionId = this.dataset.section;
            showSection(sectionId);
            updateActiveNav(this);
            updatePageTitle(this.textContent.trim());
        });
    });
    
    // Initialize counters animation
    animateCounters();
    
    // Initialize charts (placeholder)
    setTimeout(() => {
        initializeCharts();
    }, 1000);
}

// Initialize sidebar
function initializeSidebar() {
    const mobileMenuToggle = document.getElementById('mobileMenuToggle');
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebar = document.getElementById('sidebar');
    
    // Mobile menu toggle
    if (mobileMenuToggle) {
        mobileMenuToggle.addEventListener('click', function() {
            sidebar.classList.toggle('show');
        });
    }
    
    // Sidebar close button
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function() {
            sidebar.classList.remove('show');
        });
    }
    
    // Close sidebar when clicking outside on mobile
    document.addEventListener('click', function(e) {
        if (window.innerWidth <= 768) {
            if (!sidebar.contains(e.target) && !mobileMenuToggle.contains(e.target)) {
                sidebar.classList.remove('show');
            }
        }
    });
}

// Initialize user info
function initializeUserInfo() {
    const adminUser = JSON.parse(localStorage.getItem('adminUser') || '{}');
    
    // Update user info in sidebar
    const userName = document.getElementById('userName');
    const userRole = document.getElementById('userRole');
    
    if (userName && adminUser.firstName) {
        userName.textContent = `${adminUser.firstName} ${adminUser.lastName}`;
    }
    
    if (userRole && adminUser.role) {
        userRole.textContent = formatRole(adminUser.role);
    }
}

// Format role display
function formatRole(role) {
    const roleMap = {
        'super-admin': 'Super Admin',
        'content-admin': 'Content Admin',
        'service-admin': 'Service Admin',
        'support-admin': 'Support Admin'
    };
    return roleMap[role] || 'Admin';
}

// Show section
function showSection(sectionId) {
    // Hide all sections
    const sections = document.querySelectorAll('.content-section');
    sections.forEach(section => {
        section.classList.remove('active');
    });
    
    // Show target section
    const targetSection = document.getElementById(`${sectionId}-section`);
    if (targetSection) {
        targetSection.classList.add('active');
    }
}

// Update active navigation
function updateActiveNav(activeLink) {
    // Remove active class from all nav links
    const navLinks = document.querySelectorAll('.nav-link');
    navLinks.forEach(link => {
        link.classList.remove('active');
    });
    
    // Add active class to clicked link
    activeLink.classList.add('active');
}

// Update page title
function updatePageTitle(title) {
    const pageTitle = document.getElementById('pageTitle');
    if (pageTitle) {
        pageTitle.textContent = title;
    }
}

// Animate counters
function animateCounters() {
    const counters = document.querySelectorAll('.stat-info h3');
    const speed = 200;
    
    counters.forEach(counter => {
        const target = parseInt(counter.textContent.replace(/[^0-9]/g, ''));
        const suffix = counter.textContent.replace(/[0-9]/g, '');
        let count = 0;
        
        const updateCount = () => {
            const increment = target / speed;
            if (count < target) {
                count += increment;
                counter.textContent = Math.ceil(count).toLocaleString() + suffix;
                setTimeout(updateCount, 1);
            } else {
                counter.textContent = target.toLocaleString() + suffix;
            }
        };
        
        updateCount();
    });
}

// Initialize charts (placeholder for future enhancement)
function initializeCharts() {
    // This would integrate with a charting library like Chart.js
    console.log('Charts initialized - would integrate with Chart.js or similar');
}

// User menu toggle
function toggleUserMenu() {
    const userDropdown = document.getElementById('userDropdown');
    userDropdown.classList.toggle('show');
}

// Close user menu when clicking outside
document.addEventListener('click', function(e) {
    const userMenu = document.querySelector('.user-menu');
    const userDropdown = document.getElementById('userDropdown');
    
    if (!userMenu.contains(e.target)) {
        userDropdown.classList.remove('show');
    }
});

// Show notifications
function showNotifications() {
    showNotification('You have 3 new notifications', 'info');
    // In a real app, this would show a notifications panel
}

// Show messages
function showMessages() {
    showNotification('You have 7 new messages', 'info');
    // In a real app, this would show a messages panel
}

// Content Management
function initializeContentManagement() {
    // Tab switching
    const tabButtons = document.querySelectorAll('.tab-btn');
    tabButtons.forEach(button => {
        button.addEventListener('click', function() {
            // Remove active class from all tabs
            tabButtons.forEach(btn => btn.classList.remove('active'));
            
            // Add active class to clicked tab
            this.classList.add('active');
            
            // Load content for this tab
            loadTabContent(this.dataset.tab);
        });
    });
}

// Load tab content
function loadTabContent(tabType) {
    console.log(`Loading content for tab: ${tabType}`);
    // In a real app, this would load different content based on the tab
}

// Content Management Functions
function showContentEditor() {
    const modal = document.getElementById('contentEditorModal');
    showModal(modal);
}

function editContent(contentType) {
    showNotification(`Opening editor for ${contentType} page`, 'info');
    showContentEditor();
}

function viewContent(contentType) {
    showNotification(`Opening preview for ${contentType} page`, 'info');
    // In a real app, this would open a preview modal or new tab
}

// Service Management Functions
function showServiceEditor() {
    showNotification('Service editor would open here', 'info');
    // In a real app, this would open a service editing modal
}

function editService(serviceType) {
    showNotification(`Editing ${serviceType} service`, 'info');
    // In a real app, this would open a service editing modal
}

function viewServiceStats(serviceType) {
    showNotification(`Viewing statistics for ${serviceType} service`, 'info');
    // In a real app, this would show detailed analytics
}

// Modal Functions
function initializeModals() {
    // Close modal when clicking outside
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('modal')) {
            closeModal(e.target.id);
        }
    });
    
    // Handle form submissions
    const contentEditForm = document.getElementById('contentEditForm');
    if (contentEditForm) {
        contentEditForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const title = formData.get('title');
            const description = formData.get('description');
            const content = formData.get('content');
            
            // Simulate saving
            showNotification('Content saved successfully!', 'success');
            closeModal('contentEditorModal');
            
            // Reset form
            this.reset();
        });
    }
}

function showModal(modal) {
    if (typeof modal === 'string') {
        modal = document.getElementById(modal);
    }
    if (modal) {
        modal.classList.add('show');
        document.body.style.overflow = 'hidden';
    }
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('show');
        document.body.style.overflow = '';
    }
}

// Load dashboard data from database
async function loadDashboardData() {
    try {
        // Load dashboard statistics
        const stats = await apiRequest('dashboard');
        updateDashboardStats(stats);
        
        // Load services
        await loadServices();
        
        // Load content
        await loadContent();
        
        // Load inquiries
        await loadInquiries();
        
    } catch (error) {
        console.error('Error loading dashboard data:', error);
        showNotification('Error loading dashboard data', 'error');
    }
}

// Update dashboard statistics
function updateDashboardStats(stats) {
    const statsElements = {
        services: document.querySelector('[data-stat="services"]'),
        inquiries: document.querySelector('[data-stat="inquiries"]'),
        new_inquiries: document.querySelector('[data-stat="new_inquiries"]'),
        admins: document.querySelector('[data-stat="admins"]')
    };
    
    Object.keys(stats).forEach(key => {
        if (statsElements[key]) {
            animateCounter(statsElements[key], stats[key]);
        }
    });
}

// Load services from database
async function loadServices() {
    try {
        const services = await apiRequest('services');
        updateServicesTable(services);
    } catch (error) {
        console.error('Error loading services:', error);
    }
}

// Update services table
function updateServicesTable(services) {
    const tbody = document.querySelector('#servicesTable tbody');
    if (!tbody) return;
    
    tbody.innerHTML = '';
    
    services.forEach(service => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${service.title}</td>
            <td>${service.category}</td>
            <td>${service.price_range}</td>
            <td>
                <span class="status-badge ${service.status}">${service.status}</span>
            </td>
            <td>
                <div class="action-buttons">
                    <button class="btn-action edit" onclick="editService(${service.id})">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn-action delete" onclick="deleteService(${service.id})">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </td>
        `;
        tbody.appendChild(row);
    });
}

// Load content from database
async function loadContent() {
    try {
        const content = await apiRequest('content');
        updateContentList(content);
    } catch (error) {
        console.error('Error loading content:', error);
    }
}

// Update content list
function updateContentList(content) {
    const contentList = document.querySelector('#contentList');
    if (!contentList) return;
    
    contentList.innerHTML = '';
    
    const groupedContent = content.reduce((groups, item) => {
        const key = `${item.page_name}-${item.section_name}`;
        if (!groups[key]) {
            groups[key] = {
                page: item.page_name,
                section: item.section_name,
                items: []
            };
        }
        groups[key].items.push(item);
        return groups;
    }, {});
    
    Object.values(groupedContent).forEach(group => {
        const groupElement = document.createElement('div');
        groupElement.className = 'content-group';
        groupElement.innerHTML = `
            <h4>${group.page} - ${group.section}</h4>
            <div class="content-items">
                ${group.items.map(item => `
                    <div class="content-item">
                        <span class="content-key">${item.content_key}:</span>
                        <span class="content-value">${item.content_value}</span>
                        <button class="btn-action edit" onclick="editContent(${item.id})">
                            <i class="fas fa-edit"></i>
                        </button>
                    </div>
                `).join('')}
            </div>
        `;
        contentList.appendChild(groupElement);
    });
}

// Load inquiries from database
async function loadInquiries() {
    try {
        const inquiries = await apiRequest('inquiries');
        updateInquiriesTable(inquiries);
    } catch (error) {
        console.error('Error loading inquiries:', error);
    }
}

// Update inquiries table
function updateInquiriesTable(inquiries) {
    const tbody = document.querySelector('#inquiriesTable tbody');
    if (!tbody) return;
    
    tbody.innerHTML = '';
    
    inquiries.forEach(inquiry => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${inquiry.name}</td>
            <td>${inquiry.email}</td>
            <td>${inquiry.service || 'General'}</td>
            <td>
                <span class="status-badge ${inquiry.status}">${inquiry.status}</span>
            </td>
            <td>${new Date(inquiry.created_at).toLocaleDateString()}</td>
            <td>
                <div class="action-buttons">
                    <button class="btn-action view" onclick="viewInquiry(${inquiry.id})">
                        <i class="fas fa-eye"></i>
                    </button>
                    <button class="btn-action edit" onclick="updateInquiryStatus(${inquiry.id})">
                        <i class="fas fa-edit"></i>
                    </button>
                </div>
            </td>
        `;
        tbody.appendChild(row);
    });
}

// Service management functions
async function editService(id) {
    try {
        const service = await apiRequest(`services/${id}`);
        openServiceModal(service);
    } catch (error) {
        showNotification('Error loading service details', 'error');
    }
}

async function deleteService(id) {
    if (confirm('Are you sure you want to delete this service?')) {
        try {
            await apiRequest(`services/${id}`, 'DELETE');
            showNotification('Service deleted successfully', 'success');
            loadServices(); // Reload services
        } catch (error) {
            showNotification('Error deleting service', 'error');
        }
    }
}

async function saveService(serviceData, isEdit = false, id = null) {
    try {
        const method = isEdit ? 'PUT' : 'POST';
        const endpoint = isEdit ? `services/${id}` : 'services';
        
        await apiRequest(endpoint, method, serviceData);
        showNotification(`Service ${isEdit ? 'updated' : 'created'} successfully`, 'success');
        loadServices(); // Reload services
        closeModal('serviceModal');
    } catch (error) {
        showNotification(`Error ${isEdit ? 'updating' : 'creating'} service`, 'error');
    }
}

// Content management functions
async function editContent(id) {
    try {
        const content = await apiRequest(`content/${id}`);
        openContentModal(content);
    } catch (error) {
        showNotification('Error loading content details', 'error');
    }
}

async function saveContent(contentData, id) {
    try {
        await apiRequest(`content/${id}`, 'PUT', contentData);
        showNotification('Content updated successfully', 'success');
        loadContent(); // Reload content
        closeModal('contentModal');
    } catch (error) {
        showNotification('Error updating content', 'error');
    }
}

// Inquiry management functions
async function updateInquiryStatus(id) {
    const status = prompt('Enter new status (new, in-progress, resolved, closed):');
    if (status && ['new', 'in-progress', 'resolved', 'closed'].includes(status)) {
        try {
            await apiRequest(`inquiries/${id}`, 'PUT', { status: status });
            showNotification('Inquiry status updated successfully', 'success');
            loadInquiries(); // Reload inquiries
        } catch (error) {
            showNotification('Error updating inquiry status', 'error');
        }
    }
}

// Sample data for demonstration
const sampleBookings = [
    {
        id: 1,
        service: 'Legal Consultation',
        client: 'Sarah Johnson',
        details: 'Family Law',
        time: '2 hours ago',
        status: 'pending'
    },
    {
        id: 2,
        service: 'Flight Booking',
        client: 'Michael Chen',
        details: 'NY to LA',
        time: '4 hours ago',
        status: 'confirmed'
    },
    {
        id: 3,
        service: 'Tax Assistance',
        client: 'Emily Rodriguez',
        details: 'Business Tax',
        time: '6 hours ago',
        status: 'completed'
    }
];

const sampleServices = [
    {
        id: 1,
        name: 'Professional Agents',
        category: 'Consultation',
        priceRange: '$50 - $200',
        status: 'active',
        bookings: 1234,
        icon: 'fa-user-tie'
    },
    {
        id: 2,
        name: 'Flight Booking',
        category: 'Travel',
        priceRange: '$25 - $50',
        status: 'active',
        bookings: 892,
        icon: 'fa-plane'
    },
    {
        id: 3,
        name: 'Legal Services',
        category: 'Consultation',
        priceRange: '$150 - $500',
        status: 'active',
        bookings: 567,
        icon: 'fa-balance-scale'
    }
];

// Load sample data
function loadSampleData() {
    loadBookingsData();
    loadServicesData();
}

function loadBookingsData() {
    // This would typically fetch from an API
    console.log('Sample bookings loaded:', sampleBookings);
}

function loadServicesData() {
    const servicesTableBody = document.getElementById('servicesTableBody');
    if (!servicesTableBody) return;
    
    // Clear existing rows
    servicesTableBody.innerHTML = '';
    
    // Add sample services
    sampleServices.forEach(service => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>
                <div class="service-cell">
                    <i class="fas ${service.icon}"></i>
                    <span>${service.name}</span>
                </div>
            </td>
            <td>${service.category}</td>
            <td>${service.priceRange}</td>
            <td><span class="status ${service.status}">${service.status.charAt(0).toUpperCase() + service.status.slice(1)}</span></td>
            <td>${service.bookings.toLocaleString()}</td>
            <td>
                <button class="btn-icon" onclick="editService('${service.name.toLowerCase().replace(' ', '-')}')">
                    <i class="fas fa-edit"></i>
                </button>
                <button class="btn-icon" onclick="viewServiceStats('${service.name.toLowerCase().replace(' ', '-')}')">
                    <i class="fas fa-chart-bar"></i>
                </button>
            </td>
        `;
        servicesTableBody.appendChild(row);
    });
}

// Real-time updates simulation
function startRealTimeUpdates() {
    // Simulate real-time updates every 30 seconds
    setInterval(() => {
        updateDashboardStats();
    }, 30000);
}

function updateDashboardStats() {
    // Simulate random updates to stats
    const statNumbers = document.querySelectorAll('.stat-info h3');
    statNumbers.forEach(stat => {
        const currentValue = parseInt(stat.textContent.replace(/[^0-9]/g, ''));
        const change = Math.floor(Math.random() * 10) - 5; // Random change between -5 and +5
        const newValue = Math.max(0, currentValue + change);
        const suffix = stat.textContent.replace(/[0-9,]/g, '');
        stat.textContent = newValue.toLocaleString() + suffix;
    });
}

// Notification system (reused from auth.js)
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

// Logout function
function logout() {
    if (confirm('Are you sure you want to logout?')) {
        localStorage.removeItem('adminLoggedIn');
        localStorage.removeItem('adminUser');
        localStorage.removeItem('rememberAdmin');
        
        showNotification('Logged out successfully!', 'success');
        
        setTimeout(() => {
            window.location.href = '/Agency/admin/login.php';
        }, 1500);
    }
}

// Initialize everything when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    loadSampleData();
    // startRealTimeUpdates(); // Disabled auto-refresh
    
    // Welcome message
    setTimeout(() => {
        const adminUser = JSON.parse(localStorage.getItem('adminUser') || '{}');
        const welcomeMessage = adminUser.firstName 
            ? `Welcome back, ${adminUser.firstName}!` 
            : 'Welcome to the Admin Dashboard!';
        showNotification(welcomeMessage, 'success');
    }, 1000);
});

// Export functions for global access
window.showModal = showModal;
window.closeModal = closeModal;
window.editContent = editContent;
window.viewContent = viewContent;
window.editService = editService;
window.viewServiceStats = viewServiceStats;
window.showContentEditor = showContentEditor;
window.showServiceEditor = showServiceEditor;
window.toggleUserMenu = toggleUserMenu;
window.showNotifications = showNotifications;
window.showMessages = showMessages;
window.logout = logout;
