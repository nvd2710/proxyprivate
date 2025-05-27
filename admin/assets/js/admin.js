/**
 * Admin Panel JavaScript
 * File: admin/assets/js/admin.js
 */

// Global variables
let currentModal = null;

// Initialize admin panel
document.addEventListener('DOMContentLoaded', function() {
    initializeAdmin();
});

function initializeAdmin() {
    // Setup sidebar toggle
    const sidebarToggle = document.querySelector('.sidebar-toggle');
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', toggleSidebar);
    }
    
    // Setup responsive sidebar
    if (window.innerWidth <= 1024) {
        document.querySelector('.sidebar')?.classList.remove('show');
    }
    
    // Setup window resize handler
    window.addEventListener('resize', handleResize);
    
    // Setup form auto-save
    setupAutoSave();
    
    // Setup keyboard shortcuts
    setupKeyboardShortcuts();
    
    // Setup tooltips
    setupTooltips();
}

// Sidebar functions
function toggleSidebar() {
    const sidebar = document.querySelector('.sidebar');
    if (sidebar) {
        sidebar.classList.toggle('show');
    }
}

function handleResize() {
    const sidebar = document.querySelector('.sidebar');
    if (window.innerWidth > 1024 && sidebar) {
        sidebar.classList.remove('show');
    }
}

// Toast notification system
function showToast(type, title, message, duration = 5000) {
    const container = document.getElementById('toast-container');
    if (!container) return;
    
    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    
    const icons = {
        success: 'fas fa-check-circle',
        error: 'fas fa-exclamation-circle',
        warning: 'fas fa-exclamation-triangle',
        info: 'fas fa-info-circle'
    };
    
    toast.innerHTML = `
        <div class="toast-icon">
            <i class="${icons[type] || icons.info}"></i>
        </div>
        <div class="toast-content">
            <div class="toast-title">${title}</div>
            <div class="toast-message">${message}</div>
        </div>
        <button class="toast-close" onclick="closeToast(this)">
            <i class="fas fa-times"></i>
        </button>
    `;
    
    container.appendChild(toast);
    
    // Auto remove toast
    setTimeout(() => {
        removeToast(toast);
    }, duration);
}

function closeToast(button) {
    const toast = button.closest('.toast');
    removeToast(toast);
}

function removeToast(toast) {
    if (toast && toast.parentNode) {
        toast.style.animation = 'slideOutRight 0.3s ease';
        setTimeout(() => {
            toast.remove();
        }, 300);
    }
}

// Modal system
function createModal(title, content, buttons = null) {
    const modal = document.createElement('div');
    modal.className = 'modal';
    modal.style.display = 'block';
    
    let buttonsHtml = '';
    if (buttons && buttons.length > 0) {
        buttonsHtml = buttons.map(btn => 
            `<button class="btn ${btn.class}" onclick="${btn.onclick}">${btn.text}</button>`
        ).join('');
    } else {
        buttonsHtml = '<button class="btn btn-secondary" onclick="closeModal()">Đóng</button>';
    }
    
    modal.innerHTML = `
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">${title}</h3>
                <button class="modal-close" onclick="closeModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                ${content}
            </div>
            <div class="modal-footer">
                ${buttonsHtml}
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
    currentModal = modal;
    
    // Close on background click
    modal.addEventListener('click', function(e) {
        if (e.target === modal) {
            closeModal();
        }
    });
    
    return modal;
}

function showModal(modal) {
    if (modal) {
        modal.style.display = 'block';
        currentModal = modal;
    }
}

function closeModal() {
    if (currentModal) {
        currentModal.style.display = 'none';
        currentModal.remove();
        currentModal = null;
    }
}

// Data table functions
function initializeDataTable(tableId, options = {}) {
    const table = document.getElementById(tableId);
    if (!table) return;
    
    // Add sorting functionality
    const headers = table.querySelectorAll('th[data-sortable]');
    headers.forEach(header => {
        header.style.cursor = 'pointer';
        header.addEventListener('click', () => sortTable(table, header));
    });
    
    // Add search functionality if search input exists
    const searchInput = document.querySelector(`[data-table-search="${tableId}"]`);
    if (searchInput) {
        searchInput.addEventListener('input', (e) => filterTable(table, e.target.value));
    }
}

function sortTable(table, header) {
    const columnIndex = Array.from(header.parentNode.children).indexOf(header);
    const tbody = table.querySelector('tbody');
    const rows = Array.from(tbody.querySelectorAll('tr'));
    
    const isAscending = header.classList.contains('sort-asc');
    
    // Remove existing sort classes
    table.querySelectorAll('th').forEach(th => {
        th.classList.remove('sort-asc', 'sort-desc');
    });
    
    // Add new sort class
    header.classList.add(isAscending ? 'sort-desc' : 'sort-asc');
    
    rows.sort((a, b) => {
        const aVal = a.children[columnIndex].textContent.trim();
        const bVal = b.children[columnIndex].textContent.trim();
        
        // Try to parse as numbers
        const aNum = parseFloat(aVal.replace(/[^\d.-]/g, ''));
        const bNum = parseFloat(bVal.replace(/[^\d.-]/g, ''));
        
        if (!isNaN(aNum) && !isNaN(bNum)) {
            return isAscending ? bNum - aNum : aNum - bNum;
        }
        
        // Compare as strings
        return isAscending ? bVal.localeCompare(aVal) : aVal.localeCompare(bVal);
    });
    
    // Re-append sorted rows
    rows.forEach(row => tbody.appendChild(row));
}

function filterTable(table, searchTerm) {
    const tbody = table.querySelector('tbody');
    const rows = tbody.querySelectorAll('tr');
    
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        const matches = text.includes(searchTerm.toLowerCase());
        row.style.display = matches ? '' : 'none';
    });
}

// Form functions
function setupAutoSave() {
    const forms = document.querySelectorAll('form[data-autosave]');
    forms.forEach(form => {
        const inputs = form.querySelectorAll('input, select, textarea');
        inputs.forEach(input => {
            input.addEventListener('change', () => autoSaveForm(form));
        });
    });
}

function autoSaveForm(form) {
    const formData = new FormData(form);
    const data = Object.fromEntries(formData);
    
    localStorage.setItem(`autosave_${form.id}`, JSON.stringify(data));
    
    // Show auto-save indicator
    showAutoSaveIndicator();
}

function loadAutoSavedData(formId) {
    const saved = localStorage.getItem(`autosave_${formId}`);
    if (!saved) return;
    
    try {
        const data = JSON.parse(saved);
        const form = document.getElementById(formId);
        
        Object.keys(data).forEach(key => {
            const input = form.querySelector(`[name="${key}"]`);
            if (input) {
                input.value = data[key];
            }
        });
    } catch (e) {
        console.error('Error loading auto-saved data:', e);
    }
}

function showAutoSaveIndicator() {
    let indicator = document.getElementById('autosave-indicator');
    if (!indicator) {
        indicator = document.createElement('div');
        indicator.id = 'autosave-indicator';
        indicator.style.cssText = `
            position: fixed;
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            background: #28a745;
            color: white;
            padding: 8px 16px;
            border-radius: 4px;
            font-size: 14px;
            z-index: 9999;
            opacity: 0;
            transition: opacity 0.3s ease;
        `;
        indicator.textContent = 'Đã lưu tự động';
        document.body.appendChild(indicator);
    }
    
    indicator.style.opacity = '1';
    setTimeout(() => {
        indicator.style.opacity = '0';
    }, 2000);
}

// Keyboard shortcuts
function setupKeyboardShortcuts() {
    document.addEventListener('keydown', function(e) {
        // Ctrl/Cmd + S to save
        if ((e.ctrlKey || e.metaKey) && e.key === 's') {
            e.preventDefault();
            const form = document.querySelector('form');
            if (form) {
                form.submit();
            }
        }
        
        // Ctrl/Cmd + K to search
        if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
            e.preventDefault();
            const searchInput = document.querySelector('input[type="search"], .search-input');
            if (searchInput) {
                searchInput.focus();
            }
        }
        
        // Escape to close modal
        if (e.key === 'Escape') {
            closeModal();
        }
        
        // Alt + D for dashboard
        if (e.altKey && e.key === 'd') {
            e.preventDefault();
            window.location.href = '?page=dashboard';
        }
        
        // Alt + U for users
        if (e.altKey && e.key === 'u') {
            e.preventDefault();
            window.location.href = '?page=users';
        }
        
        // Alt + P for proxies
        if (e.altKey && e.key === 'p') {
            e.preventDefault();
            window.location.href = '?page=proxies';
        }
        
        // Alt + O for orders
        if (e.altKey && e.key === 'o') {
            e.preventDefault();
            window.location.href = '?page=orders';
        }
        
        // Alt + S for settings
        if (e.altKey && e.key === 's') {
            e.preventDefault();
            window.location.href = '?page=settings';
        }
    });
}

// Tooltip system
function setupTooltips() {
    const tooltipElements = document.querySelectorAll('[data-tooltip]');
    tooltipElements.forEach(element => {
        element.addEventListener('mouseenter', showTooltip);
        element.addEventListener('mouseleave', hideTooltip);
    });
}

function showTooltip(e) {
    const text = e.target.getAttribute('data-tooltip');
    if (!text) return;
    
    const tooltip = document.createElement('div');
    tooltip.className = 'tooltip';
    tooltip.textContent = text;
    tooltip.style.cssText = `
        position: absolute;
        background: #333;
        color: white;
        padding: 6px 10px;
        border-radius: 4px;
        font-size: 12px;
        white-space: nowrap;
        z-index: 10000;
        pointer-events: none;
        opacity: 0;
        transition: opacity 0.3s ease;
    `;
    
    document.body.appendChild(tooltip);
    
    const rect = e.target.getBoundingClientRect();
    tooltip.style.left = rect.left + (rect.width / 2) - (tooltip.offsetWidth / 2) + 'px';
    tooltip.style.top = rect.top - tooltip.offsetHeight - 8 + 'px';
    
    setTimeout(() => {
        tooltip.style.opacity = '1';
    }, 10);
    
    e.target.tooltipElement = tooltip;
}

function hideTooltip(e) {
    if (e.target.tooltipElement) {
        e.target.tooltipElement.remove();
        e.target.tooltipElement = null;
    }
}

// Utility functions
function formatCurrency(amount, currency = 'VND') {
    if (currency === 'VND') {
        return new Intl.NumberFormat('vi-VN').format(amount) + ' ₫';
    } else {
        return new Intl.NumberFormat('en-US', {
            style: 'currency',
            currency: currency
        }).format(amount);
    }
}

function formatDate(dateString, format = 'dd/mm/yyyy HH:MM') {
    const date = new Date(dateString);
    
    const day = String(date.getDate()).padStart(2, '0');
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const year = date.getFullYear();
    const hours = String(date.getHours()).padStart(2, '0');
    const minutes = String(date.getMinutes()).padStart(2, '0');
    
    return format
        .replace('dd', day)
        .replace('mm', month)
        .replace('yyyy', year)
        .replace('HH', hours)
        .replace('MM', minutes);
}

function timeAgo(dateString) {
    const now = new Date();
    const date = new Date(dateString);
    const diffInSeconds = Math.floor((now - date) / 1000);
    
    if (diffInSeconds < 60) {
        return 'vừa xong';
    } else if (diffInSeconds < 3600) {
        const minutes = Math.floor(diffInSeconds / 60);
        return `${minutes} phút trước`;
    } else if (diffInSeconds < 86400) {
        const hours = Math.floor(diffInSeconds / 3600);
        return `${hours} giờ trước`;
    } else if (diffInSeconds < 2592000) {
        const days = Math.floor(diffInSeconds / 86400);
        return `${days} ngày trước`;
    } else if (diffInSeconds < 31536000) {
        const months = Math.floor(diffInSeconds / 2592000);
        return `${months} tháng trước`;
    } else {
        const years = Math.floor(diffInSeconds / 31536000);
        return `${years} năm trước`;
    }
}

function copyToClipboard(text) {
    if (navigator.clipboard) {
        navigator.clipboard.writeText(text).then(() => {
            showToast('success', 'Thành công', 'Đã sao chép vào clipboard');
        }).catch(() => {
            fallbackCopyToClipboard(text);
        });
    } else {
        fallbackCopyToClipboard(text);
    }
}

function fallbackCopyToClipboard(text) {
    const textArea = document.createElement('textarea');
    textArea.value = text;
    textArea.style.position = 'fixed';
    textArea.style.left = '-999999px';
    textArea.style.top = '-999999px';
    document.body.appendChild(textArea);
    textArea.focus();
    textArea.select();
    
    try {
        document.execCommand('copy');
        showToast('success', 'Thành công', 'Đã sao chép vào clipboard');
    } catch (err) {
        showToast('error', 'Lỗi', 'Không thể sao chép vào clipboard');
    }
    
    document.body.removeChild(textArea);
}

// API functions
function adminApiCall(endpoint, method = 'GET', data = null) {
    const options = {
        method: method,
        headers: {
            'Content-Type': 'application/json',
        }
    };
    
    if (data && method !== 'GET') {
        options.body = JSON.stringify(data);
    }
    
    return fetch(`api/admin/${endpoint}`, options)
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .catch(error => {
            console.error('API Error:', error);
            showToast('error', 'Lỗi API', error.message);
            throw error;
        });
}

// Pagination functions
function setupPagination(containerId, currentPage, totalPages, baseUrl) {
    const container = document.getElementById(containerId);
    if (!container) return;
    
    let html = '<div class="pagination">';
    
    // Previous button
    if (currentPage > 1) {
        html += `<a href="${baseUrl}&page=${currentPage - 1}">« Trước</a>`;
    }
    
    // Page numbers
    let startPage = Math.max(1, currentPage - 2);
    let endPage = Math.min(totalPages, currentPage + 2);
    
    if (startPage > 1) {
        html += `<a href="${baseUrl}&page=1">1</a>`;
        if (startPage > 2) {
            html += '<span>...</span>';
        }
    }
    
    for (let i = startPage; i <= endPage; i++) {
        if (i === currentPage) {
            html += `<span class="current">${i}</span>`;
        } else {
            html += `<a href="${baseUrl}&page=${i}">${i}</a>`;
        }
    }
    
    if (endPage < totalPages) {
        if (endPage < totalPages - 1) {
            html += '<span>...</span>';
        }
        html += `<a href="${baseUrl}&page=${totalPages}">${totalPages}</a>`;
    }
    
    // Next button
    if (currentPage < totalPages) {
        html += `<a href="${baseUrl}&page=${currentPage + 1}">Sau »</a>`;
    }
    
    html += '</div>';
    container.innerHTML = html;
}

// File upload functions
function setupFileUpload(inputId, options = {}) {
    const input = document.getElementById(inputId);
    if (!input) return;
    
    const allowedTypes = options.allowedTypes || ['image/jpeg', 'image/png', 'image/gif'];
    const maxSize = options.maxSize || 5 * 1024 * 1024; // 5MB
    
    input.addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (!file) return;
        
        // Validate file type
        if (!allowedTypes.includes(file.type)) {
            showToast('error', 'Lỗi', 'Loại file không được hỗ trợ');
            input.value = '';
            return;
        }
        
        // Validate file size
        if (file.size > maxSize) {
            showToast('error', 'Lỗi', `File quá lớn. Tối đa ${maxSize / 1024 / 1024}MB`);
            input.value = '';
            return;
        }
        
        // Show preview if it's an image
        if (file.type.startsWith('image/') && options.previewId) {
            const reader = new FileReader();
            reader.onload = function(e) {
                const preview = document.getElementById(options.previewId);
                if (preview) {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                }
            };
            reader.readAsDataURL(file);
        }
        
        // Call callback if provided
        if (options.callback) {
            options.callback(file);
        }
    });
}

// Chart helpers
function createChart(canvasId, type, data, options = {}) {
    const ctx = document.getElementById(canvasId);
    if (!ctx) return null;
    
    const defaultOptions = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: true,
                position: 'top'
            }
        }
    };
    
    const chartOptions = { ...defaultOptions, ...options };
    
    return new Chart(ctx, {
        type: type,
        data: data,
        options: chartOptions
    });
}

// Data export functions
function exportTableToCSV(tableId, filename) {
    const table = document.getElementById(tableId);
    if (!table) return;
    
    let csv = [];
    const rows = table.querySelectorAll('tr');
    
    rows.forEach(row => {
        const cols = row.querySelectorAll('td, th');
        const rowData = Array.from(cols).map(col => {
            let text = col.textContent.trim();
            // Escape quotes and wrap in quotes if contains comma
            if (text.includes(',') || text.includes('"') || text.includes('\n')) {
                text = '"' + text.replace(/"/g, '""') + '"';
            }
            return text;
        });
        csv.push(rowData.join(','));
    });
    
    const csvContent = csv.join('\n');
    downloadFile(csvContent, filename, 'text/csv');
}

function exportToExcel(data, filename) {
    // This would require a library like xlsx.js
    // For now, export as CSV
    exportTableToCSV(data, filename.replace('.xlsx', '.csv'));
}

function downloadFile(content, filename, contentType) {
    const blob = new Blob([content], { type: contentType });
    const url = URL.createObjectURL(blob);
    
    const link = document.createElement('a');
    link.href = url;
    link.download = filename;
    link.style.display = 'none';
    
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    
    URL.revokeObjectURL(url);
}

// Real-time updates
function setupRealTimeUpdates() {
    // Check for updates every 30 seconds
    setInterval(() => {
        updateDashboardStats();
        updateNotifications();
    }, 30000);
}

function updateDashboardStats() {
    if (window.location.search.includes('page=dashboard') || !window.location.search.includes('page=')) {
        adminApiCall('get-stats.php')
            .then(data => {
                if (data.success) {
                    updateStatsDisplay(data.stats);
                }
            })
            .catch(error => {
                console.error('Error updating stats:', error);
            });
    }
}

function updateStatsDisplay(stats) {
    // Update dashboard cards with new stats
    Object.keys(stats).forEach(key => {
        const element = document.querySelector(`[data-stat="${key}"]`);
        if (element) {
            element.textContent = stats[key];
        }
    });
}

function updateNotifications() {
    adminApiCall('get-notifications.php')
        .then(data => {
            if (data.success && data.notifications.length > 0) {
                // Show new notifications
                data.notifications.forEach(notification => {
                    showToast(notification.type, notification.title, notification.message);
                });
            }
        })
        .catch(error => {
            console.error('Error updating notifications:', error);
        });
}

// Form validation
function validateForm(formId, rules) {
    const form = document.getElementById(formId);
    if (!form) return false;
    
    let isValid = true;
    const errors = {};
    
    Object.keys(rules).forEach(fieldName => {
        const field = form.querySelector(`[name="${fieldName}"]`);
        if (!field) return;
        
        const fieldRules = rules[fieldName];
        const value = field.value.trim();
        
        // Required validation
        if (fieldRules.required && !value) {
            errors[fieldName] = 'Trường này là bắt buộc';
            isValid = false;
            return;
        }
        
        // Min length validation
        if (fieldRules.minLength && value.length < fieldRules.minLength) {
            errors[fieldName] = `Tối thiểu ${fieldRules.minLength} ký tự`;
            isValid = false;
            return;
        }
        
        // Max length validation
        if (fieldRules.maxLength && value.length > fieldRules.maxLength) {
            errors[fieldName] = `Tối đa ${fieldRules.maxLength} ký tự`;
            isValid = false;
            return;
        }
        
        // Email validation
        if (fieldRules.email && value && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)) {
            errors[fieldName] = 'Email không đúng định dạng';
            isValid = false;
            return;
        }
        
        // Number validation
        if (fieldRules.numeric && value && isNaN(value)) {
            errors[fieldName] = 'Phải là số';
            isValid = false;
            return;
        }
        
        // Min value validation
        if (fieldRules.min && parseFloat(value) < fieldRules.min) {
            errors[fieldName] = `Giá trị tối thiểu là ${fieldRules.min}`;
            isValid = false;
            return;
        }
        
        // Max value validation
        if (fieldRules.max && parseFloat(value) > fieldRules.max) {
            errors[fieldName] = `Giá trị tối đa là ${fieldRules.max}`;
            isValid = false;
            return;
        }
    });
    
    // Display errors
    displayFormErrors(form, errors);
    
    return isValid;
}

function displayFormErrors(form, errors) {
    // Clear existing errors
    form.querySelectorAll('.field-error').forEach(error => error.remove());
    form.querySelectorAll('.form-input, .form-select, .form-textarea').forEach(field => {
        field.classList.remove('error');
    });
    
    // Display new errors
    Object.keys(errors).forEach(fieldName => {
        const field = form.querySelector(`[name="${fieldName}"]`);
        if (field) {
            field.classList.add('error');
            
            const errorDiv = document.createElement('div');
            errorDiv.className = 'field-error';
            errorDiv.textContent = errors[fieldName];
            errorDiv.style.cssText = 'color: #dc3545; font-size: 12px; margin-top: 5px;';
            
            field.parentNode.appendChild(errorDiv);
        }
    });
}

// Initialize real-time updates when page loads
document.addEventListener('DOMContentLoaded', function() {
    setupRealTimeUpdates();
});

// Add CSS animations keyframes
const style = document.createElement('style');
style.textContent = `
    @keyframes slideOutRight {
        from {
            opacity: 1;
            transform: translateX(0);
        }
        to {
            opacity: 0;
            transform: translateX(100%);
        }
    }
    
    .form-input.error,
    .form-select.error,
    .form-textarea.error {
        border-color: #dc3545;
        box-shadow: 0 0 0 3px rgba(220, 53, 69, 0.1);
    }
    
    .tooltip {
        box-shadow: 0 2px 8px rgba(0,0,0,0.2);
    }
    
    .tooltip::after {
        content: '';
        position: absolute;
        top: 100%;
        left: 50%;
        margin-left: -5px;
        border-width: 5px;
        border-style: solid;
        border-color: #333 transparent transparent transparent;
    }
`;
document.head.appendChild(style);