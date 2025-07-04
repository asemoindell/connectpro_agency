<?php
/**
 * Admin Layout Footer
 * Common footer content for admin pages
 */
?>

<!-- Admin Layout Footer Content -->
<script>
// Common admin JavaScript functions

// Modal functions
function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'block';
        document.body.classList.add('modal-open');
    }
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'none';
        document.body.classList.remove('modal-open');
    }
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modals = document.querySelectorAll('.modal');
    modals.forEach(modal => {
        if (event.target === modal) {
            modal.style.display = 'none';
            document.body.classList.remove('modal-open');
        }
    });
};

// Auto-hide alerts after 5 seconds
document.addEventListener('DOMContentLoaded', function() {
    const alerts = document.querySelectorAll('.alert:not(.alert-permanent)');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            setTimeout(() => {
                alert.remove();
            }, 300);
        }, 5000);
    });
});

// Confirm delete actions
function confirmDelete(message = 'Are you sure you want to delete this item?') {
    return confirm(message);
}

// Form validation helpers
function validateEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
}

function validateRequired(value) {
    return value !== null && value !== undefined && value.toString().trim() !== '';
}

// Data table enhancements
function initDataTable(tableId) {
    const table = document.getElementById(tableId);
    if (!table) return;
    
    // Add sorting functionality
    const headers = table.querySelectorAll('th[data-sort]');
    headers.forEach(header => {
        header.style.cursor = 'pointer';
        header.addEventListener('click', function() {
            const column = this.getAttribute('data-sort');
            sortTable(table, column);
        });
    });
}

function sortTable(table, column) {
    // Simple table sorting implementation
    const tbody = table.querySelector('tbody');
    const rows = Array.from(tbody.querySelectorAll('tr'));
    
    rows.sort((a, b) => {
        const aVal = a.querySelector(`[data-sort="${column}"]`)?.textContent || '';
        const bVal = b.querySelector(`[data-sort="${column}"]`)?.textContent || '';
        return aVal.localeCompare(bVal);
    });
    
    rows.forEach(row => tbody.appendChild(row));
}

// Loading state management
function showLoading(elementId) {
    const element = document.getElementById(elementId);
    if (element) {
        element.innerHTML = '<div class="loading-spinner"><i class="fas fa-spinner fa-spin"></i> Loading...</div>';
    }
}

function hideLoading(elementId, content = '') {
    const element = document.getElementById(elementId);
    if (element) {
        element.innerHTML = content;
    }
}

// Notification system
function showNotification(message, type = 'info', duration = 3000) {
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.innerHTML = `
        <i class="fas fa-${getNotificationIcon(type)}"></i>
        <span>${message}</span>
        <button onclick="this.parentElement.remove()" class="notification-close">&times;</button>
    `;
    
    document.body.appendChild(notification);
    
    // Auto remove after duration
    setTimeout(() => {
        if (notification.parentElement) {
            notification.remove();
        }
    }, duration);
}

function getNotificationIcon(type) {
    const icons = {
        'success': 'check-circle',
        'error': 'exclamation-circle',
        'warning': 'exclamation-triangle',
        'info': 'info-circle'
    };
    return icons[type] || 'info-circle';
}

// Real-time updates (if WebSocket is available)
function initRealTimeUpdates() {
    // Placeholder for real-time functionality
    // Can be extended with WebSocket or Server-Sent Events
}

// Initialize common functionality
document.addEventListener('DOMContentLoaded', function() {
    // Initialize data tables
    const tables = document.querySelectorAll('.admin-table');
    tables.forEach(table => {
        if (table.id) {
            initDataTable(table.id);
        }
    });
    
    // Initialize tooltips (if using a tooltip library)
    const tooltips = document.querySelectorAll('[data-tooltip]');
    tooltips.forEach(tooltip => {
        tooltip.title = tooltip.getAttribute('data-tooltip');
    });
    
    // Initialize real-time updates
    initRealTimeUpdates();
});

// Export functions for global use
window.AdminUtils = {
    openModal,
    closeModal,
    confirmDelete,
    validateEmail,
    validateRequired,
    showLoading,
    hideLoading,
    showNotification,
    initDataTable
};
</script>

<!-- Additional CSS for admin components -->
<style>
/* Modal styles */
.modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.5);
}

.modal-content {
    background-color: #fefefe;
    margin: 5% auto;
    padding: 0;
    border: none;
    border-radius: 8px;
    width: 90%;
    max-width: 600px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.15);
}

.modal-header {
    padding: 1rem 1.5rem;
    border-bottom: 1px solid #dee2e6;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-header h3 {
    margin: 0;
    color: #333;
}

.modal-body {
    padding: 1.5rem;
}

.modal-footer {
    padding: 1rem 1.5rem;
    border-top: 1px solid #dee2e6;
    display: flex;
    justify-content: flex-end;
    gap: 0.5rem;
}

.close {
    color: #aaa;
    font-size: 1.5rem;
    font-weight: bold;
    cursor: pointer;
    background: none;
    border: none;
    padding: 0;
    width: 30px;
    height: 30px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.close:hover {
    color: #333;
}

.modal-open {
    overflow: hidden;
}

/* Loading spinner */
.loading-spinner {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 2rem;
    color: #6c757d;
}

.loading-spinner i {
    margin-right: 0.5rem;
}

/* Notification system */
.notification {
    position: fixed;
    top: 20px;
    right: 20px;
    background: white;
    border: 1px solid #dee2e6;
    border-radius: 6px;
    padding: 1rem 1.5rem;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    z-index: 1050;
    min-width: 300px;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.notification-success {
    border-left: 4px solid #28a745;
    color: #155724;
}

.notification-error {
    border-left: 4px solid #dc3545;
    color: #721c24;
}

.notification-warning {
    border-left: 4px solid #ffc107;
    color: #856404;
}

.notification-info {
    border-left: 4px solid #17a2b8;
    color: #0c5460;
}

.notification-close {
    background: none;
    border: none;
    font-size: 1.2rem;
    cursor: pointer;
    color: #6c757d;
    margin-left: auto;
    padding: 0;
    width: 20px;
    height: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.notification-close:hover {
    color: #333;
}

/* Responsive improvements */
@media (max-width: 768px) {
    .modal-content {
        width: 95%;
        margin: 10% auto;
    }
    
    .notification {
        right: 10px;
        left: 10px;
        min-width: auto;
    }
}
</style>

<?php
// Include any additional footer scripts or content based on the current page
$currentScript = basename($_SERVER['PHP_SELF'], '.php');

switch ($currentScript) {
    case 'payments':
        // Payment page specific scripts
        echo '<script src="js/payments.js"></script>';
        break;
    case 'dashboard':
        // Dashboard specific scripts
        echo '<script src="js/dashboard.js"></script>';
        break;
    case 'users':
        // Users page specific scripts
        echo '<script src="js/users.js"></script>';
        break;
    case 'bookings':
        // Bookings page specific scripts
        echo '<script src="js/bookings.js"></script>';
        break;
}
?>

<!-- Footer timestamp for cache busting -->
<!-- Generated at: <?php echo date('Y-m-d H:i:s'); ?> -->
