<?php
/**
 * Admin Users Management Page
 * File: admin/pages/users.php
 */
?>

<div class="content-header">
    <h2>Quản lý người dùng</h2>
    <p>Xem và quản lý tất cả người dùng trên hệ thống</p>
</div>

<!-- Search and Filter Bar -->
<div class="search-filter-bar">
    <input type="text" id="user-search" class="search-input" placeholder="Tìm kiếm theo tên, email, số điện thoại...">
    
    <select id="status-filter" class="filter-select">
        <option value="all">Tất cả trạng thái</option>
        <option value="active">Hoạt động</option>
        <option value="inactive">Chưa kích hoạt</option>
        <option value="banned">Bị khóa</option>
    </select>
    
    <select id="sort-filter" class="filter-select">
        <option value="created_at">Ngày tạo</option>
        <option value="name">Tên</option>
        <option value="email">Email</option>
        <option value="balance">Số dư</option>
        <option value="last_login_at">Đăng nhập cuối</option>
    </select>
    
    <select id="order-filter" class="filter-select">
        <option value="desc">Mới nhất</option>
        <option value="asc">Cũ nhất</option>
    </select>
    
    <button class="btn btn-primary" onclick="refreshUsers()">
        <i class="fas fa-sync-alt"></i> Làm mới
    </button>
    
    <button class="btn btn-success" onclick="exportUsers()">
        <i class="fas fa-download"></i> Xuất Excel
    </button>
</div>

<!-- Users Table -->
<div class="data-table">
    <div class="table-header">
        <div class="table-title">Danh sách người dùng</div>
        <div class="table-actions">
            <button class="btn btn-primary" onclick="addUser()">
                <i class="fas fa-plus"></i> Thêm người dùng
            </button>
        </div>
    </div>
    
    <div id="users-loading" class="loading">
        <div class="spinner"></div>
        Đang tải danh sách người dùng...
    </div>
    
    <div id="users-table-container" style="display: none;">
        <table id="users-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Thông tin</th>
                    <th>Số dư</th>
                    <th>Proxy</th>
                    <th>Đơn hàng</th>
                    <th>Trạng thái</th>
                    <th>Ngày tạo</th>
                    <th>Đăng nhập cuối</th>
                    <th>Thao tác</th>
                </tr>
            </thead>
            <tbody id="users-table-body">
                <!-- Data will be loaded here -->
            </tbody>
        </table>
    </div>
    
    <div id="users-pagination" class="pagination-container">
        <!-- Pagination will be loaded here -->
    </div>
</div>

<!-- User Edit Modal -->
<div id="user-modal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title" id="user-modal-title">Chỉnh sửa người dùng</h3>
            <button class="modal-close" onclick="closeUserModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body">
            <form id="user-form">
                <input type="hidden" id="user-id" name="user_id">
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Họ và tên</label>
                        <input type="text" id="user-name" name="name" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Email</label>
                        <input type="email" id="user-email" name="email" class="form-input" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Số điện thoại</label>
                        <input type="tel" id="user-phone" name="phone" class="form-input">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Trạng thái</label>
                        <select id="user-status" name="status" class="form-select">
                            <option value="active">Hoạt động</option>
                            <option value="inactive">Chưa kích hoạt</option>
                            <option value="banned">Bị khóa</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Số dư (VND)</label>
                        <input type="number" id="user-balance" name="balance" class="form-input" min="0" step="1000">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Mật khẩu mới (để trống nếu không đổi)</label>
                        <input type="password" id="user-password" name="password" class="form-input">
                    </div>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeUserModal()">Hủy</button>
            <button class="btn btn-primary" onclick="saveUser()">Lưu thay đổi</button>
        </div>
    </div>
</div>

<style>
.search-filter-bar {
    background: white;
    padding: 20px;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    margin-bottom: 20px;
    display: flex;
    gap: 15px;
    align-items: center;
    flex-wrap: wrap;
}

.search-input {
    flex: 1;
    min-width: 300px;
    padding: 10px 15px;
    border: 1px solid #ced4da;
    border-radius: 6px;
    font-size: 14px;
}

.filter-select {
    padding: 10px 15px;
    border: 1px solid #ced4da;
    border-radius: 6px;
    font-size: 14px;
    min-width: 150px;
}

.user-info {
    display: flex;
    flex-direction: column;
}

.user-name {
    font-weight: 600;
    margin-bottom: 2px;
}

.user-email {
    font-size: 12px;
    color: #666;
}

.user-phone {
    font-size: 12px;
    color: #888;
}

.proxy-stats {
    display: flex;
    flex-direction: column;
    font-size: 14px;
}

.balance-amount {
    font-weight: 600;
    color: #28a745;
}

.pagination-container {
    padding: 20px;
    text-align: center;
}

.pagination {
    display: inline-flex;
    gap: 5px;
}

.pagination a,
.pagination span {
    padding: 8px 12px;
    border: 1px solid #dee2e6;
    color: #495057;
    text-decoration: none;
    border-radius: 4px;
    transition: all 0.3s ease;
}

.pagination a:hover {
    background-color: #e9ecef;
}

.pagination .current {
    background: linear-gradient(45deg, #667eea, #764ba2);
    color: white;
    border-color: #667eea;
}

@media (max-width: 768px) {
    .search-filter-bar {
        flex-direction: column;
        align-items: stretch;
    }
    
    .search-input {
        min-width: auto;
    }
    
    #users-table {
        font-size: 12px;
    }
    
    .form-row {
        flex-direction: column;
    }
}
</style>

<script>
let currentPage = 1;
let currentFilters = {
    search: '',
    status: 'all',
    sort: 'created_at',
    order: 'desc'
};

// Initialize page
document.addEventListener('DOMContentLoaded', function() {
    setupEventListeners();
    loadUsers();
});

function setupEventListeners() {
    // Search input with debounce
    let searchTimeout;
    document.getElementById('user-search').addEventListener('input', function(e) {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            currentFilters.search = e.target.value;
            currentPage = 1;
            loadUsers();
        }, 500);
    });
    
    // Filter changes
    document.getElementById('status-filter').addEventListener('change', function(e) {
        currentFilters.status = e.target.value;
        currentPage = 1;
        loadUsers();
    });
    
    document.getElementById('sort-filter').addEventListener('change', function(e) {
        currentFilters.sort = e.target.value;
        currentPage = 1;
        loadUsers();
    });
    
    document.getElementById('order-filter').addEventListener('change', function(e) {
        currentFilters.order = e.target.value;
        currentPage = 1;
        loadUsers();
    });
}

async function loadUsers(page = 1) {
    currentPage = page;
    
    // Show loading
    document.getElementById('users-loading').style.display = 'block';
    document.getElementById('users-table-container').style.display = 'none';
    
    try {
        const params = new URLSearchParams({
            page: currentPage,
            limit: 20,
            ...currentFilters
        });
        
        const response = await fetch(`../api/admin/get-users.php?${params}`);
        const data = await response.json();
        
        if (data.success) {
            displayUsers(data.data);
            displayPagination(data.pagination);
            
            // Hide loading, show table
            document.getElementById('users-loading').style.display = 'none';
            document.getElementById('users-table-container').style.display = 'block';
        } else {
            throw new Error(data.message);
        }
        
    } catch (error) {
        console.error('Error loading users:', error);
        document.getElementById('users-loading').innerHTML = 
            '<div class="error">❌ Lỗi tải dữ liệu: ' + error.message + '</div>';
    }
}

function displayUsers(users) {
    const tbody = document.getElementById('users-table-body');
    
    if (users.length === 0) {
        tbody.innerHTML = '<tr><td colspan="9" style="text-align: center; padding: 40px; color: #666;">Không tìm thấy người dùng nào</td></tr>';
        return;
    }
    
    tbody.innerHTML = users.map(user => `
        <tr>
            <td>${user.id}</td>
            <td>
                <div class="user-info">
                    <div class="user-name">${user.name}</div>
                    <div class="user-email">${user.email}</div>
                    ${user.phone ? `<div class="user-phone">${user.phone}</div>` : ''}
                </div>
            </td>
            <td>
                <div class="balance-amount">${user.formatted_balance}</div>
            </td>
            <td>
                <div class="proxy-stats">
                    <span>${user.active_proxies}/${user.total_proxies}</span>
                    <small>Hoạt động/Tổng</small>
                </div>
            </td>
            <td>${user.total_orders}</td>
            <td>
                <span class="status-badge status-${user.status}">
                    ${getStatusText(user.status)}
                </span>
            </td>
            <td>${user.formatted_created_at}</td>
            <td>${user.formatted_last_login}</td>
            <td>
                <div style="display: flex; gap: 5px;">
                    <button class="control-btn btn-check" onclick="editUser(${user.id})" title="Chỉnh sửa">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="control-btn btn-extend" onclick="viewUserDetails(${user.id})" title="Xem chi tiết">
                        <i class="fas fa-eye"></i>
                    </button>
                    ${user.status !== 'banned' ? 
                        `<button class="control-btn btn-delete" onclick="banUser(${user.id})" title="Khóa tài khoản">
                            <i class="fas fa-ban"></i>
                        </button>` :
                        `<button class="control-btn btn-extend" onclick="unbanUser(${user.id})" title="Mở khóa">
                            <i class="fas fa-unlock"></i>
                        </button>`
                    }
                </div>
            </td>
        </tr>
    `).join('');
}

function displayPagination(pagination) {
    const container = document.getElementById('users-pagination');
    
    if (pagination.total_pages <= 1) {
        container.innerHTML = '';
        return;
    }
    
    let html = '<div class="pagination">';
    
    // Previous page
    if (pagination.has_prev) {
        html += `<a href="#" onclick="loadUsers(${pagination.current_page - 1})">‹ Trước</a>`;
    }
    
    // Page numbers
    const startPage = Math.max(1, pagination.current_page - 2);
    const endPage = Math.min(pagination.total_pages, pagination.current_page + 2);
    
    if (startPage > 1) {
        html += `<a href="#" onclick="loadUsers(1)">1</a>`;
        if (startPage > 2) {
            html += '<span>...</span>';
        }
    }
    
    for (let i = startPage; i <= endPage; i++) {
        if (i === pagination.current_page) {
            html += `<span class="current">${i}</span>`;
        } else {
            html += `<a href="#" onclick="loadUsers(${i})">${i}</a>`;
        }
    }
    
    if (endPage < pagination.total_pages) {
        if (endPage < pagination.total_pages - 1) {
            html += '<span>...</span>';
        }
        html += `<a href="#" onclick="loadUsers(${pagination.total_pages})">${pagination.total_pages}</a>`;
    }
    
    // Next page
    if (pagination.has_next) {
        html += `<a href="#" onclick="loadUsers(${pagination.current_page + 1})">Sau ›</a>`;
    }
    
    html += '</div>';
    container.innerHTML = html;
}

function getStatusText(status) {
    const statusTexts = {
        'active': 'Hoạt động',
        'inactive': 'Chưa kích hoạt',
        'banned': 'Bị khóa'
    };
    return statusTexts[status] || status;
}

function refreshUsers() {
    loadUsers(currentPage);
}

function editUser(userId) {
    // TODO: Implement edit user
    showToast('info', 'Thông báo', 'Chức năng chỉnh sửa đang phát triển');
}

function viewUserDetails(userId) {
    // TODO: Implement view details
    showToast('info', 'Thông báo', 'Chức năng xem chi tiết đang phát triển');
}

function banUser(userId) {
    if (confirm('Bạn có chắc chắn muốn khóa tài khoản này?')) {
        // TODO: Implement ban user
        showToast('info', 'Thông báo', 'Chức năng khóa tài khoản đang phát triển');
    }
}

function unbanUser(userId) {
    if (confirm('Bạn có chắc chắn muốn mở khóa tài khoản này?')) {
        // TODO: Implement unban user
        showToast('info', 'Thông báo', 'Chức năng mở khóa đang phát triển');
    }
}

function addUser() {
    // TODO: Implement add user
    showToast('info', 'Thông báo', 'Chức năng thêm người dùng đang phát triển');
}

function exportUsers() {
    // TODO: Implement export
    showToast('info', 'Thông báo', 'Chức năng xuất Excel đang phát triển');
}

function closeUserModal() {
    document.getElementById('user-modal').style.display = 'none';
}

function saveUser() {
    // TODO: Implement save user
    showToast('info', 'Thông báo', 'Chức năng lưu đang phát triển');
}

// Helper function for toast notifications
function showToast(type, title, message) {
    // This should match the showToast function in admin.js
    console.log(type + ': ' + title + ' - ' + message);
}
</script>