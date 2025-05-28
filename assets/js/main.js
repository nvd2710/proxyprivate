// Cấu hình API
const API_CONFIG = {
    key: '94b8ddc0da-572e9c845e-34c2fa225a',
    baseUrl: 'https://px6.link/api',
    exchangeRate: 24000 // 1 USD = 24,000 VND (có thể cập nhật từ API)
};

// Biến toàn cục
let currentUser = null;
let userBalance = 0;
let userCurrency = 'VND';

// Khởi tạo khi trang web load
document.addEventListener('DOMContentLoaded', function() {
    initializeApp();
});

// Khởi tạo ứng dụng
function initializeApp() {
    // Kiểm tra đăng nhập
    checkAuthStatus();
    
    // Thiết lập event listeners
    setupEventListeners();
    
    // Tải dữ liệu ban đầu
    loadInitialData();
    
    // Cập nhật thống kê
    updateStats();
    
    // Thiết lập auto-refresh
    setInterval(updateStats, 30000);
}

// Kiểm tra trạng thái đăng nhập
function checkAuthStatus() {
    const token = localStorage.getItem('auth_token');
    const userData = localStorage.getItem('user_data');
    
    if (token && userData) {
        currentUser = JSON.parse(userData);
        updateUserInterface();
        loadUserData();
    }
}

// Cập nhật giao diện người dùng
function updateUserInterface() {
    if (currentUser) {
        document.getElementById('username').textContent = currentUser.name;
        document.getElementById('user-email').textContent = currentUser.email;
        document.getElementById('user-avatar').textContent = currentUser.name.charAt(0).toUpperCase();
        
        // Ẩn nút đăng nhập/đăng ký
        document.querySelector('.auth-buttons').style.display = 'none';
        
        // Hiển thị menu người dùng
        const userMenu = document.createElement('div');
        userMenu.innerHTML = `
            <button class="btn-login" onclick="showSection('dashboard')">
                👤 ${currentUser.name}
            </button>
        `;
        document.querySelector('.header-content').appendChild(userMenu);
    }
}

// Thiết lập event listeners
function setupEventListeners() {
    // Form đăng nhập
    document.getElementById('login-form').addEventListener('submit', handleLogin);
    
    // Form đăng ký
    document.getElementById('register-form').addEventListener('submit', handleRegister);
    
    // Cập nhật giá khi thay đổi form
    const proxyTypes = ['ipv6', 'ipv4', 'ipv4-shared'];
    proxyTypes.forEach(type => {
        document.getElementById(`${type}-count`).addEventListener('input', () => updatePrice(type));
        document.getElementById(`${type}-period`).addEventListener('change', () => updatePrice(type));
        document.getElementById(`${type}-country`).addEventListener('change', () => {
            updateAvailableCount(type);
            updatePrice(type);
        });
    });
    
    // Filters proxy
    document.getElementById('filter-status')?.addEventListener('change', filterProxies);
    document.getElementById('filter-type')?.addEventListener('change', filterProxies);
    document.getElementById('filter-comment')?.addEventListener('input', filterProxies);
    
    // Phím tắt
    document.addEventListener('keydown', handleKeyboardShortcuts);
    
    // Đóng modal khi click bên ngoài
    window.addEventListener('click', function(event) {
        const modals = document.querySelectorAll('.modal');
        modals.forEach(modal => {
            if (event.target === modal) {
                modal.style.display = 'none';
            }
        });
    });
}

// Xử lý đăng nhập
async function handleLogin(event) {
    event.preventDefault();
    
    const email = document.getElementById('login-email').value;
    const password = document.getElementById('login-password').value;
    const remember = document.getElementById('remember-me').checked;
    
    try {
        showMessage('Đang đăng nhập...', 'info');
        
        const response = await fetch('api/auth/login.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ email, password, remember })
        });
        
        const data = await response.json();
        
        if (data.success) {
            currentUser = data.user;
            localStorage.setItem('auth_token', data.token);
            localStorage.setItem('user_data', JSON.stringify(data.user));
            
            showMessage('Đăng nhập thành công!', 'success');
            closeModal('login');
            updateUserInterface();
            loadUserData();
        } else {
            showMessage(data.message || 'Đăng nhập thất bại!', 'error');
        }
    } catch (error) {
        console.error('Lỗi đăng nhập:', error);
        showMessage('Lỗi kết nối máy chủ!', 'error');
    }
}

// Xử lý đăng ký
async function handleRegister(event) {
    event.preventDefault();
    
    const name = document.getElementById('register-name').value;
    const email = document.getElementById('register-email').value;
    const phone = document.getElementById('register-phone').value;
    const password = document.getElementById('register-password').value;
    const confirmPassword = document.getElementById('register-confirm-password').value;
    const agreeTerms = document.getElementById('agree-terms').checked;
    
    // Kiểm tra validation
    if (password !== confirmPassword) {
        showMessage('Mật khẩu xác nhận không khớp!', 'error');
        return;
    }
    
    if (!agreeTerms) {
        showMessage('Vui lòng đồng ý với điều khoản sử dụng!', 'error');
        return;
    }
    
    try {
        showMessage('Đang tạo tài khoản...', 'info');
        
        const response = await fetch('api/auth/register.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ name, email, phone, password })
        });
        
        const data = await response.json();
        
        if (data.success) {
            showMessage('Đăng ký thành công! Vui lòng kiểm tra email để kích hoạt tài khoản.', 'success');
            closeModal('register');
            showModal('login');
        } else {
            showMessage(data.message || 'Đăng ký thất bại!', 'error');
        }
    } catch (error) {
        console.error('Lỗi đăng ký:', error);
        showMessage('Lỗi kết nối máy chủ!', 'error');
    }
}

// Đăng xuất
function logout() {
    localStorage.removeItem('auth_token');
    localStorage.removeItem('user_data');
    currentUser = null;
    location.reload();
}

// Quản lý sections
function showSection(sectionId) {
    // Ẩn tất cả sections
    document.querySelectorAll('.section').forEach(section => {
        section.classList.remove('active');
    });
    
    // Hiển thị section được chọn
    document.getElementById(sectionId + '-section').classList.add('active');
    
    // Load dữ liệu cho section
    if (sectionId === 'dashboard') {
        if (!currentUser) {
            showModal('login');
            return;
        }
        loadDashboardData();
    }
}

// Quản lý tabs trong dashboard
function showDashboardTab(tabId) {
    // Ẩn tất cả tabs
    document.querySelectorAll('.dashboard-tab').forEach(tab => {
        tab.classList.add('hidden');
    });
    
    // Hiển thị tab được chọn
    document.getElementById(tabId + '-tab').classList.remove('hidden');
    
    // Cập nhật sidebar active
    document.querySelectorAll('.sidebar-item').forEach(item => {
        item.classList.remove('active');
    });
    event.target.classList.add('active');
    
    // Load dữ liệu cho tab
    switch(tabId) {
        case 'proxies':
            loadProxies();
            break;
        case 'orders':
            loadOrders();
            break;
        case 'payments':
            loadPayments();
            break;
        case 'profile':
            loadProfile();
            break;
    }
}

// Quản lý modals
function showModal(modalId) {
    document.getElementById(modalId + '-modal').style.display = 'block';
}

function closeModal(modalId) {
    document.getElementById(modalId + '-modal').style.display = 'none';
}

function switchModal(modalId) {
    document.querySelectorAll('.modal').forEach(modal => {
        modal.style.display = 'none';
    });
    showModal(modalId);
}

// API calls
async function apiCall(method, params = {}) {
    try {
        const url = new URL(`${API_CONFIG.baseUrl}/${API_CONFIG.key}/${method}/`);
        Object.keys(params).forEach(key => {
            if (params[key] !== undefined && params[key] !== null) {
                url.searchParams.append(key, params[key]);
            }
        });
        
        const response = await fetch(url);
        const data = await response.json();
        
        if (data.status === 'yes') {
            return data;
        } else {
            throw new Error(data.error || 'Lỗi API');
        }
    } catch (error) {
        console.error('API Error:', error);
        showMessage('Lỗi: ' + error.message, 'error');
        return null;
    }
}

// Cập nhật giá proxy
async function updatePrice(type) {
    const count = parseInt(document.getElementById(`${type}-count`).value);
    const period = parseInt(document.getElementById(`${type}-period`).value);
    
    let version = 6; // IPv6 mặc định
    if (type === 'ipv4') version = 4;
    if (type === 'ipv4-shared') version = 3;
    
    const data = await apiCall('getprice', { count, period, version });
    if (data) {
        // Chuyển đổi từ USD sang VND
        const priceVND = Math.round(data.price_single * API_CONFIG.exchangeRate);
        document.getElementById(`${type}-price`).textContent = `${priceVND.toLocaleString('vi-VN')} ₫`;
    }
}

// Cập nhật số lượng khả dụng
async function updateAvailableCount(type) {
    const country = document.getElementById(`${type}-country`).value;
    let version = 6;
    
    if (type === 'ipv4') version = 4;
    if (type === 'ipv4-shared') version = 3;
    
    const data = await apiCall('getcount', { country, version });
    if (data) {
        document.getElementById(`${type}-available`).textContent = `Khả dụng: ${data.count.toLocaleString('vi-VN')}`;
    }
}

// Mua proxy
async function buyProxy(type) {
    if (!currentUser) {
        showModal('login');
        return;
    }
    
    const count = parseInt(document.getElementById(`${type}-count`).value);
    const period = parseInt(document.getElementById(`${type}-period`).value);
    const country = document.getElementById(`${type}-country`).value;
    
    let version = 6;
    if (type === 'ipv4') version = 4;
    if (type === 'ipv4-shared') version = 3;
    
    if (!confirm(`Bạn có chắc chắn muốn mua ${count} proxy ${type.toUpperCase()} trong ${period} ngày?`)) {
        return;
    }
    
    showMessage('Đang xử lý mua hàng...', 'info');
    
    const data = await apiCall('buy', {
        count,
        period,
        country,
        version,
        type: 'http'
    });
    
    if (data) {
        const priceVND = Math.round(data.price * API_CONFIG.exchangeRate);
        showMessage(`Mua thành công ${data.count} proxy! Tổng chi phí: ${priceVND.toLocaleString('vi-VN')} ₫`, 'success');
        loadUserData();
        if (document.getElementById('dashboard-section').classList.contains('active')) {
            loadProxies();
        }
    }
}

// Load dữ liệu người dùng
async function loadUserData() {
    const data = await apiCall('');
    if (data) {
        userBalance = parseFloat(data.balance);
        userCurrency = data.currency;
        
        // Chuyển đổi balance sang VND nếu cần
        let balanceDisplay = userBalance;
        if (userCurrency === 'USD') {
            balanceDisplay = Math.round(userBalance * API_CONFIG.exchangeRate);
            document.getElementById('user-balance').textContent = `${balanceDisplay.toLocaleString('vi-VN')} ₫`;
        } else {
            document.getElementById('user-balance').textContent = `${balanceDisplay.toLocaleString('vi-VN')} ${userCurrency}`;
        }
    }
}

// Load dữ liệu dashboard
function loadDashboardData() {
    loadUserData();
    loadProxies();
}

// Load danh sách proxy
async function loadProxies() {
    const proxyList = document.getElementById('proxy-list');
    if (!proxyList) return;
    
    proxyList.innerHTML = '<div class="loading">Đang tải danh sách proxy...</div>';
    
    const data = await apiCall('getproxy');
    if (data) {
        proxyList.innerHTML = '';
        
        if (data.list_count === 0) {
            proxyList.innerHTML = '<p class="empty-state">Bạn chưa có proxy nào. <a href="#" onclick="showSection(\'home\')">Mua ngay</a></p>';
            return;
        }
        
        Object.values(data.list).forEach(proxy => {
            const proxyItem = document.createElement('div');
            proxyItem.className = 'proxy-item';
            proxyItem.innerHTML = `
                <div class="proxy-info">
                    <div class="proxy-ip">${proxy.ip}</div>
                    <div class="proxy-host">${proxy.host}:${proxy.port}</div>
                </div>
                <div class="proxy-auth">
                    <div class="proxy-user">${proxy.user}</div>
                    <div class="proxy-pass">${proxy.pass}</div>
                </div>
                <div class="proxy-location">
                    <div class="proxy-country">${getCountryName(proxy.country)}</div>
                    <div class="proxy-type">${proxy.type.toUpperCase()}</div>
                </div>
                <div class="proxy-status">
                    <div class="proxy-expiry">${formatDate(proxy.date_end)}</div>
                    <div class="status-badge ${proxy.active === '1' ? 'status-active' : 'status-expired'}">
                        ${proxy.active === '1' ? '✅ Hoạt động' : '❌ Hết hạn'}
                    </div>
                </div>
                <div class="proxy-controls">
                    <button class="control-btn btn-extend" onclick="extendProxy(${proxy.id})" title="Gia hạn">
                        Gia hạn
                    </button>
                    <button class="control-btn btn-check" onclick="checkProxy(${proxy.id})" title="Kiểm tra">
                        Kiểm tra
                    </button>
                    <button class="control-btn btn-delete" onclick="deleteProxy(${proxy.id})" title="Xóa">
                        Xóa
                    </button>
                </div>
            `;
            proxyList.appendChild(proxyItem);
        });
    }
}

// Gia hạn proxy
async function extendProxy(id) {
    const period = prompt('Nhập số ngày muốn gia hạn:', '7');
    if (!period || isNaN(period)) return;
    
    const data = await apiCall('prolong', {
        period: parseInt(period),
        ids: id
    });
    
    if (data) {
        const priceVND = Math.round(data.price * API_CONFIG.exchangeRate);
        showMessage(`Gia hạn thành công! Chi phí: ${priceVND.toLocaleString('vi-VN')} ₫`, 'success');
        loadUserData();
        loadProxies();
    }
}

// Xóa proxy
async function deleteProxy(id) {
    if (!confirm('Bạn có chắc chắn muốn xóa proxy này?')) return;
    
    const data = await apiCall('delete', { ids: id });
    if (data) {
        showMessage(`Đã xóa thành công ${data.count} proxy`, 'success');
        loadProxies();
    }
}

// Kiểm tra proxy
async function checkProxy(id) {
    showMessage('Đang kiểm tra proxy...', 'info');
    const data = await apiCall('check', { ids: id });
    if (data) {
        const status = data.proxy_status ? 'Hoạt động tốt' : 'Không hoạt động';
        const messageType = data.proxy_status ? 'success' : 'error';
        showMessage(`Proxy #${data.proxy_id}: ${status}`, messageType);
    }
}

// Làm mới danh sách proxy
function refreshProxies() {
    loadProxies();
}

// Xuất danh sách proxy
async function exportProxies() {
    const data = await apiCall('getproxy');
    if (data && data.list) {
        let exportText = 'IP:PORT:USER:PASS\n';
        Object.values(data.list).forEach(proxy => {
            exportText += `${proxy.host}:${proxy.port}:${proxy.user}:${proxy.pass}\n`;
        });
        
        const blob = new Blob([exportText], { type: 'text/plain;charset=utf-8' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `proxies_${new Date().toISOString().split('T')[0]}.txt`;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
        
        showMessage('Xuất danh sách proxy thành công!', 'success');
    }
}

// Filter proxy
function filterProxies() {
    const statusFilter = document.getElementById('filter-status').value;
    const typeFilter = document.getElementById('filter-type').value;
    const commentFilter = document.getElementById('filter-comment').value.toLowerCase();
    
    const proxyItems = document.querySelectorAll('.proxy-item');
    
    proxyItems.forEach(item => {
        let show = true;
        
        // Filter by status
        if (statusFilter !== 'all') {
            const statusBadge = item.querySelector('.status-badge');
            const isActive = statusBadge.classList.contains('status-active');
            if (statusFilter === 'active' && !isActive) show = false;
            if (statusFilter === 'expired' && isActive) show = false;
        }
        
        // Filter by type
        if (typeFilter !== 'all') {
            const typeElement = item.querySelector('.proxy-type');
            const proxyType = typeElement.textContent.toLowerCase();
            if (typeFilter === 'http' && !proxyType.includes('http')) show = false;
            if (typeFilter === 'socks' && !proxyType.includes('socks')) show = false;
        }
        
        // Filter by comment (tìm kiếm trong IP, host, user)
        if (commentFilter) {
            const text = item.textContent.toLowerCase();
            if (!text.includes(commentFilter)) show = false;
        }
        
        item.style.display = show ? 'grid' : 'none';
    });
}

// Load dữ liệu ban đầu
async function loadInitialData() {
    const types = ['ipv6', 'ipv4', 'ipv4-shared'];
    
    // Load giá và số lượng khả dụng cho mỗi loại
    types.forEach(type => {
        updatePrice(type);
        updateAvailableCount(type);
    });
    
    // Load danh sách quốc gia
    loadCountries();
}

// Load danh sách quốc gia
async function loadCountries() {
    const data = await apiCall('getcountry');
    if (data && data.list) {
        const countrySelects = document.querySelectorAll('select[id$="-country"]');
        
        countrySelects.forEach(select => {
            const currentValue = select.value;
            select.innerHTML = '';
            
            data.list.forEach(countryCode => {
                const option = document.createElement('option');
                option.value = countryCode;
                option.textContent = getCountryName(countryCode);
                select.appendChild(option);
            });
            
            // Khôi phục giá trị đã chọn
            if (data.list.includes(currentValue)) {
                select.value = currentValue;
            }
        });
    }
}

// Copy API key
function copyApiKey() {
    const apiKey = document.getElementById('user-api-key').textContent;
    navigator.clipboard.writeText(apiKey).then(() => {
        showMessage('Đã sao chép API key vào clipboard!', 'success');
    }).catch(() => {
        // Fallback cho trình duyệt cũ
        const textArea = document.createElement('textarea');
        textArea.value = apiKey;
        document.body.appendChild(textArea);
        textArea.select();
        document.execCommand('copy');
        document.body.removeChild(textArea);
        showMessage('Đã sao chép API key vào clipboard!', 'success');
    });
}

// Cập nhật thống kê
async function updateStats() {
    // Simulate real-time stats (có thể thay thế bằng API thực)
    const stats = {
        users: Math.floor(15247 + Math.random() * 50),
        sold: Math.floor(892156 + Math.random() * 500),
        active: Math.floor(25847 + Math.random() * 200),
        orders: Math.floor(156923 + Math.random() * 50)
    };
    
    document.getElementById('users-count').textContent = stats.users.toLocaleString('vi-VN');
    document.getElementById('sold-proxies').textContent = stats.sold.toLocaleString('vi-VN');
    document.getElementById('active-proxies').textContent = stats.active.toLocaleString('vi-VN');
    document.getElementById('orders-processed').textContent = stats.orders.toLocaleString('vi-VN');
}

// Hiển thị thông báo
function showMessage(message, type = 'info') {
    const messageArea = document.getElementById('message-area');
    if (!messageArea) {
        // Tạo thông báo toàn cục nếu không có message area
        const notification = document.createElement('div');
        notification.className = `message ${type}`;
        notification.textContent = message;
        notification.style.position = 'fixed';
        notification.style.top = '20px';
        notification.style.right = '20px';
        notification.style.zIndex = '9999';
        notification.style.maxWidth = '400px';
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.remove();
        }, 5000);
        return;
    }
    
    const messageDiv = document.createElement('div');
    messageDiv.className = `message ${type}`;
    messageDiv.textContent = message;
    
    messageArea.innerHTML = '';
    messageArea.appendChild(messageDiv);
    
    // Tự động ẩn sau 5 giây
    setTimeout(() => {
        messageDiv.remove();
    }, 5000);
}

// Xử lý phím tắt
function handleKeyboardShortcuts(event) {
    if (event.ctrlKey || event.metaKey) {
        switch(event.key) {
            case 'h':
                event.preventDefault();
                showSection('home');
                break;
            case 'd':
                event.preventDefault();
                if (currentUser) {
                    showSection('dashboard');
                }
                break;
            case 'r':
                event.preventDefault();
                if (document.getElementById('dashboard-section').classList.contains('active')) {
                    refreshProxies();
                }
                break;
            case 'l':
                event.preventDefault();
                if (!currentUser) {
                    showModal('login');
                }
                break;
        }
    }
    
    // ESC để đóng modal
    if (event.key === 'Escape') {
        document.querySelectorAll('.modal').forEach(modal => {
            modal.style.display = 'none';
        });
    }
}

// Utility functions
function getCountryName(countryCode) {
    const countryNames = {
        'vn': '🇻🇳 Việt Nam',
        'us': '🇺🇸 Hoa Kỳ',
        'sg': '🇸🇬 Singapore',
        'jp': '🇯🇵 Nhật Bản',
        'kr': '🇰🇷 Hàn Quốc',
        'th': '🇹🇭 Thái Lan',
        'cn': '🇨🇳 Trung Quốc',
        'hk': '🇭🇰 Hồng Kông',
        'tw': '🇹🇼 Đài Loan',
        'my': '🇲🇾 Malaysia',
        'id': '🇮🇩 Indonesia',
        'ph': '🇵🇭 Philippines',
        'in': '🇮🇳 Ấn Độ',
        'ru': '🇷🇺 Nga',
        'de': '🇩🇪 Đức',
        'fr': '🇫🇷 Pháp',
        'gb': '🇬🇧 Vương quốc Anh',
        'ca': '🇨🇦 Canada',
        'au': '🇦🇺 Úc',
        'br': '🇧🇷 Brazil'
    };
    return countryNames[countryCode] || countryCode.toUpperCase();
}

function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('vi-VN', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit'
    });
}

// Load orders (placeholder)
function loadOrders() {
    // Implement order loading logic
    console.log('Loading orders...');
}

// Load payments (placeholder)
function loadPayments() {
    // Implement payment loading logic
    console.log('Loading payments...');
}

// Load profile (placeholder)
function loadProfile() {
    if (currentUser) {
        document.getElementById('profile-name').value = currentUser.name || '';
        document.getElementById('profile-email').value = currentUser.email || '';
        document.getElementById('profile-phone').value = currentUser.phone || '';
    }
}

// Show tools (placeholder)
function showTool(toolName) {
    showMessage(`Công cụ ${toolName} đang được phát triển...`, 'info');
}

// Auto-save form data
function saveFormData() {
    const formData = {};
    document.querySelectorAll('input[id], select[id]').forEach(input => {
        if (input.id && input.type !== 'password') {
            formData[input.id] = input.value;
        }
    });
    localStorage.setItem('proxyFormData', JSON.stringify(formData));
}

function loadFormData() {
    try {
        const saved = localStorage.getItem('proxyFormData');
        if (saved) {
            const formData = JSON.parse(saved);
            Object.keys(formData).forEach(id => {
                const element = document.getElementById(id);
                if (element && element.type !== 'password') {
                    element.value = formData[id];
                }
            });
        }
    } catch (e) {
        console.log('Không có dữ liệu form đã lưu');
    }
}

// Auto-save khi có thay đổi
document.addEventListener('change', saveFormData);
document.addEventListener('input', debounce(saveFormData, 1000));

// Debounce function
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Load form data sau khi trang load
setTimeout(loadFormData, 500);

// Service Worker để cache
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js')
            .then(registration => {
                console.log('SW đã đăng ký: ', registration);
            })
            .catch(registrationError => {
                console.log('SW đăng ký thất bại: ', registrationError);
            });
    });
}
// assets/js/main.js - Remaining functions

// Setup event listeners
function setupEventListeners() {
    // Price update listeners
    ['ipv6', 'ipv4', 'ipv4-shared'].forEach(type => {
        const countInput = document.getElementById(`${type}-count`);
        const periodSelect = document.getElementById(`${type}-period`);
        const countrySelect = document.getElementById(`${type}-country`);
        
        if (countInput) {
            countInput.addEventListener('input', () => updatePrice(type));
            countInput.addEventListener('blur', validateQuantity);
        }
        if (periodSelect) periodSelect.addEventListener('change', () => updatePrice(type));
        if (countrySelect) countrySelect.addEventListener('change', () => updateAvailableCount(type));
    });
    
    // Form submissions
    const loginForm = document.getElementById('login-form');
    const registerForm = document.getElementById('register-form');
    
    if (loginForm) {
        loginForm.addEventListener('submit', handleLogin);
    }
    
    if (registerForm) {
        registerForm.addEventListener('submit', handleRegister);
    }
    
    // Modal close on outside click
    window.addEventListener('click', function(event) {
        if (event.target.classList.contains('modal')) {
            event.target.style.display = 'none';
        }
    });
    
    // Keyboard shortcuts
    document.addEventListener('keydown', handleKeyboardShortcuts);
    
    // Auto-save form data
    setupAutoSave();
}

// Validate quantity input
function validateQuantity(event) {
    const input = event.target;
    const value = parseInt(input.value);
    const min = parseInt(input.min) || 1;
    const max = parseInt(input.max) || 1000;
    
    if (isNaN(value) || value < min) {
        input.value = min;
        showMessage(`Số lượng tối thiểu là ${min}`, 'warning');
    } else if (value > max) {
        input.value = max;
        showMessage(`Số lượng tối đa là ${max}`, 'warning');
    }
    
    // Update price after validation
    const type = input.id.replace('-count', '');
    updatePrice(type);
}

// Keyboard shortcuts
function handleKeyboardShortcuts(event) {
    // Ctrl/Cmd + K to open search
    if ((event.ctrlKey || event.metaKey) && event.key === 'k') {
        event.preventDefault();
        // Implement search functionality
        console.log('Search shortcut triggered');
    }
    
    // Escape to close modals
    if (event.key === 'Escape') {
        document.querySelectorAll('.modal').forEach(modal => {
            if (modal.style.display === 'block') {
                modal.style.display = 'none';
            }
        });
    }
}

// Auto-save form data
function setupAutoSave() {
    const forms = ['login-form', 'register-form'];
    
    forms.forEach(formId => {
        const form = document.getElementById(formId);
        if (form) {
            const inputs = form.querySelectorAll('input[type="text"], input[type="email"]');
            inputs.forEach(input => {
                // Load saved data
                const savedValue = localStorage.getItem(`autosave_${input.id}`);
                if (savedValue && input.type !== 'password') {
                    input.value = savedValue;
                }
                
                // Save on input
                input.addEventListener('input', () => {
                    if (input.type !== 'password') {
                        localStorage.setItem(`autosave_${input.id}`, input.value);
                    }
                });
            });
        }
    });
}

// Section Management
function showSection(sectionName) {
    // Hide all sections
    document.querySelectorAll('.section').forEach(section => {
        section.classList.remove('active');
    });
    
    // Show target section
    const targetSection = document.getElementById(`${sectionName}-section`);
    if (targetSection) {
        targetSection.classList.add('active');
        
        // Update URL without page reload
        if (history.pushState) {
            history.pushState(null, null, `#${sectionName}`);
        }
    }
    
    // Special handling for dashboard
    if (sectionName === 'dashboard') {
        if (!isLoggedIn) {
            showModal('login');
            return;
        }
        loadUserData();
    }
    
    // Update navigation active state
    updateNavigationState(sectionName);
}

function updateNavigationState(activeSection) {
    document.querySelectorAll('.nav-menu a').forEach(link => {
        link.classList.remove('active');
    });
    
    // Add active class to current section link
    const activeLink = document.querySelector(`[onclick="showSection('${activeSection}')"]`);
    if (activeLink) {
        activeLink.classList.add('active');
    }
}

// Price Calculation with enhanced features
function updatePrice(type) {
    const countInput = document.getElementById(`${type}-count`);
    const periodSelect = document.getElementById(`${type}-period`);
    
    if (!countInput || !periodSelect) return;
    
    const count = parseInt(countInput.value) || 1;
    const period = parseInt(periodSelect.value) || 30;
    
    const basePrice = PRICING[type]?.[period] || 0;
    const totalPrice = basePrice * count;
    
    // Calculate discount for bulk orders
    let discount = 0;
    if (count >= 100) discount = 0.15; // 15% discount for 100+
    else if (count >= 50) discount = 0.10; // 10% discount for 50+
    else if (count >= 20) discount = 0.05; // 5% discount for 20+
    
    const discountAmount = totalPrice * discount;
    const finalPrice = totalPrice - discountAmount;
    
    const priceElement = document.getElementById(`${type}-price`);
    if (priceElement) {
        if (discount > 0) {
            priceElement.innerHTML = `
                <div class="original-price">${formatPrice(totalPrice)}</div>
                <div class="discount-info">Giảm ${(discount * 100).toFixed(0)}%: -${formatPrice(discountAmount)}</div>
                <div class="final-price">${formatPrice(finalPrice)}</div>
            `;
        } else {
            priceElement.textContent = formatPrice(finalPrice);
        }
    }
    
    // Update available count
    updateAvailableCount(type);
    
    // Show price breakdown tooltip
    showPriceBreakdown(type, count, period, basePrice, discount);
}

function showPriceBreakdown(type, count, period, basePrice, discount) {
    const tooltip = document.getElementById(`${type}-price-tooltip`);
    if (tooltip) {
        const perUnit = formatPrice(basePrice);
        const totalBeforeDiscount = formatPrice(basePrice * count);
        const discountText = discount > 0 ? ` (Giảm ${(discount * 100).toFixed(0)}%)` : '';
        
        tooltip.setAttribute('data-tooltip', 
            `${count} proxy × ${perUnit} × ${period} ngày = ${totalBeforeDiscount}${discountText}`
        );
    }
}

// Enhanced available count update
function updateAvailableCount(type) {
    const country = document.getElementById(`${type}-country`).value;
    const availableElement = document.getElementById(`${type}-available`);
    
    if (availableElement && AVAILABLE_COUNTS[country]) {
        let count;
        if (type === 'ipv6') count = AVAILABLE_COUNTS[country].ipv6;
        else if (type === 'ipv4') count = AVAILABLE_COUNTS[country].ipv4;
        else count = AVAILABLE_COUNTS[country].shared;
        
        // Add real-time availability check
        checkRealTimeAvailability(country, type).then(realCount => {
            const finalCount = realCount !== null ? realCount : count;
            
            availableElement.innerHTML = `
                <span class="availability-text">Còn lại: </span>
                <span class="availability-count ${getAvailabilityClass(finalCount)}">${finalCount.toLocaleString()}</span>
                <span class="availability-label"> proxy</span>
            `;
            
            // Add click handler to refresh
            availableElement.onclick = () => updateAvailableCount(type);
            availableElement.style.cursor = 'pointer';
            availableElement.title = 'Nhấp để cập nhật';
        });
    }
}

async function checkRealTimeAvailability(country, type) {
    try {
        const response = await fetch(`${API_BASE_URL}/available/${country}/${type}`);
        if (response.ok) {
            const data = await response.json();
            return data.count;
        }
    } catch (error) {
        console.log('Real-time availability check failed, using cached data');
    }
    return null;
}

function getAvailabilityClass(count) {
    if (count > 100) return 'availability-high';
    if (count > 10) return 'availability-medium';
    return 'availability-low';
}

// Enhanced proxy management functions
async function testProxy(proxyId) {
    const proxy = userProxies.find(p => p.id === proxyId);
    if (!proxy) return;
    
    showMessage('Đang kiểm tra proxy...', 'info');
    
    try {
        const response = await fetch(`${API_BASE_URL}/tools/check-proxy`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${localStorage.getItem('authToken')}`
            },
            body: JSON.stringify({ 
                proxy: `${proxy.ip}:${proxy.port}:${proxy.username}:${proxy.password}` 
            })
        });
        
        const data = await response.json();
        
        if (response.ok && data.status === 'working') {
            showMessage(`Proxy hoạt động bình thường! Ping: ${data.responseTime}ms`, 'success');
        } else {
            showMessage('Proxy không hoạt động. Vui lòng liên hệ hỗ trợ.', 'error');
        }
    } catch (error) {
        console.error('Test proxy error:', error);
        showMessage('Lỗi kiểm tra proxy', 'error');
    }
}

function copyProxy(proxyId) {
    const proxy = userProxies.find(p => p.id === proxyId);
    if (!proxy) return;
    
    const proxyString = `${proxy.ip}:${proxy.port}:${proxy.username}:${proxy.password}`;
    
    navigator.clipboard.writeText(proxyString).then(() => {
        showMessage('Đã sao chép thông tin proxy!', 'success');
        
        // Visual feedback
        const button = event.target;
        button.classList.add('copied');
        setTimeout(() => button.classList.remove('copied'), 2000);
    }).catch(() => {
        // Fallback for older browsers
        const textArea = document.createElement('textarea');
        textArea.value = proxyString;
        document.body.appendChild(textArea);
        textArea.select();
        document.execCommand('copy');
        document.body.removeChild(textArea);
        showMessage('Đã sao chép thông tin proxy!', 'success');
    });
}

async function updateProxyComment(proxyId, comment) {
    try {
        const response = await fetch(`${API_BASE_URL}/proxy/${proxyId}/comment`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${localStorage.getItem('authToken')}`
            },
            body: JSON.stringify({ comment })
        });
        
        if (response.ok) {
            // Update local data
            const proxy = userProxies.find(p => p.id === proxyId);
            if (proxy) {
                proxy.comment = comment;
            }
            showMessage('Đã cập nhật ghi chú!', 'success');
        }
    } catch (error) {
        console.error('Update comment error:', error);
        showMessage('Lỗi cập nhật ghi chú', 'error');
    }
}

async function deleteProxy(proxyId) {
    if (!confirm('Bạn có chắc chắn muốn xóa proxy này? Hành động này không thể hoàn tác.')) {
        return;
    }
    
    try {
        const response = await fetch(`${API_BASE_URL}/proxy/${proxyId}`, {
            method: 'DELETE',
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('authToken')}`
            }
        });
        
        if (response.ok) {
            showMessage('Đã xóa proxy thành công!', 'success');
            await loadUserProxies(); // Reload list
        } else {
            const data = await response.json();
            showMessage(data.error || 'Lỗi xóa proxy', 'error');
        }
    } catch (error) {
        console.error('Delete proxy error:', error);
        showMessage('Lỗi kết nối server', 'error');
    }
}

// Enhanced export functionality
function exportProxies() {
    if (userProxies.length === 0) {
        showMessage('Không có proxy để xuất', 'warning');
        return;
    }
    
    // Show export options modal
    const exportModal = createExportModal();
    document.body.appendChild(exportModal);
    exportModal.style.display = 'block';
}

function createExportModal() {
    const modal = document.createElement('div');
    modal.className = 'modal';
    modal.innerHTML = `
        <div class="modal-content">
            <div class="modal-header">
                <h3>Xuất danh sách Proxy</h3>
                <span class="close" onclick="this.closest('.modal').remove()">&times;</span>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label>Định dạng xuất:</label>
                    <select id="export-format">
                        <option value="txt">Text (.txt)</option>
                        <option value="csv">CSV (.csv)</option>
                        <option value="json">JSON (.json)</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Bao gồm:</label>
                    <label class="checkbox-label">
                        <input type="checkbox" id="include-auth" checked>
                        Thông tin xác thực
                    </label>
                    <label class="checkbox-label">
                        <input type="checkbox" id="include-comment">
                        Ghi chú
                    </label>
                    <label class="checkbox-label">
                        <input type="checkbox" id="include-expiry">
                        Ngày hết hạn
                    </label>
                </div>
                <div class="form-group">
                    <label>Lọc theo trạng thái:</label>
                    <select id="export-filter">
                        <option value="all">Tất cả</option>
                        <option value="active">Chỉ proxy hoạt động</option>
                        <option value="expired">Chỉ proxy hết hạn</option>
                    </select>
                </div>
                <button class="btn-primary" onclick="performExport()">Xuất file</button>
            </div>
        </div>
    `;
    return modal;
}

function performExport() {
    const format = document.getElementById('export-format').value;
    const includeAuth = document.getElementById('include-auth').checked;
    const includeComment = document.getElementById('include-comment').checked;
    const includeExpiry = document.getElementById('include-expiry').checked;
    const filter = document.getElementById('export-filter').value;
    
    // Filter proxies
    let filteredProxies = userProxies;
    if (filter === 'active') {
        filteredProxies = userProxies.filter(p => p.status === 'active');
    } else if (filter === 'expired') {
        filteredProxies = userProxies.filter(p => p.status === 'expired');
    }
    
    let content = '';
    let filename = `proxies_${new Date().toISOString().split('T')[0]}`;
    
    switch (format) {
        case 'txt':
            content = filteredProxies.map(proxy => {
                let line = `${proxy.ip}:${proxy.port}`;
                if (includeAuth) line += `:${proxy.username}:${proxy.password}`;
                if (includeComment && proxy.comment) line += ` # ${proxy.comment}`;
                if (includeExpiry) line += ` (Expires: ${new Date(proxy.expiresAt).toLocaleDateString()})`;
                return line;
            }).join('\n');
            filename += '.txt';
            break;
            
        case 'csv':
            const headers = ['IP', 'Port'];
            if (includeAuth) headers.push('Username', 'Password');
            if (includeComment) headers.push('Comment');
            if (includeExpiry) headers.push('Expires');
            
            content = headers.join(',') + '\n';
            content += filteredProxies.map(proxy => {
                const row = [proxy.ip, proxy.port];
                if (includeAuth) row.push(proxy.username, proxy.password);
                if (includeComment) row.push(proxy.comment || '');
                if (includeExpiry) row.push(new Date(proxy.expiresAt).toLocaleDateString());
                return row.join(',');
            }).join('\n');
            filename += '.csv';
            break;
            
        case 'json':
            const exportData = filteredProxies.map(proxy => {
                const data = { ip: proxy.ip, port: proxy.port };
                if (includeAuth) {
                    data.username = proxy.username;
                    data.password = proxy.password;
                }
                if (includeComment) data.comment = proxy.comment;
                if (includeExpiry) data.expires = proxy.expiresAt;
                return data;
            });
            content = JSON.stringify(exportData, null, 2);
            filename += '.json';
            break;
    }
    
    // Download file
    const blob = new Blob([content], { type: 'text/plain;charset=utf-8' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = filename;
    a.click();
    window.URL.revokeObjectURL(url);
    
    // Close modal and show success message
    document.querySelector('.modal').remove();
    showMessage(`Đã xuất ${filteredProxies.length} proxy thành công!`, 'success');
}

// Dashboard tab management with enhanced features
function showDashboardTab(tabName) {
    // Hide all tabs
    document.querySelectorAll('.dashboard-tab').forEach(tab => {
        tab.classList.add('hidden');
    });
    
    // Show target tab
    const targetTab = document.getElementById(`${tabName}-tab`);
    if (targetTab) {
        targetTab.classList.remove('hidden');
    }
    
    // Update sidebar active state
    document.querySelectorAll('.sidebar-item').forEach(item => {
        item.classList.remove('active');
    });
    
    if (event && event.target) {
        event.target.classList.add('active');
    }
    
    // Load tab-specific data
    switch(tabName) {
        case 'proxies':
            loadUserProxies();
            break;
        case 'orders':
            loadUserOrders();
            break;
        case 'payments':
            loadUserPayments();
            break;
        case 'profile':
            loadUserProfile();
            break;
        case 'api':
            loadApiInfo();
            break;
    }
    
    // Update URL
    if (history.pushState) {
        history.pushState(null, null, `#dashboard/${tabName}`);
    }
}

// Enhanced user data loading
async function loadUserData() {
    if (!currentUser) return;
    
    try {
        // Get fresh user data from server
        const response = await fetch(`${API_BASE_URL}/user`, {
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('authToken')}`
            }
        });
        
        if (response.ok) {
            const userData = await response.json();
            currentUser = { ...currentUser, ...userData };
            
            // Update UI elements
            document.getElementById('username').textContent = currentUser.username;
            document.getElementById('user-balance').textContent = formatPrice(currentUser.balance);
            document.getElementById('user-api-key').textContent = currentUser.apiKey;
            
            // Update avatar with first letter of username
            const avatar = document.getElementById('user-avatar');
            if (avatar) {
                avatar.textContent = currentUser.username.charAt(0).toUpperCase();
            }
        }
    } catch (error) {
        console.error('Load user data error:', error);
    }
    
    // Load default tab
    loadUserProxies();
}

// Enhanced orders loading
async function loadUserOrders() {
    const ordersContainer = document.querySelector('#orders-tab .orders-list');
    
    try {
        ordersContainer.innerHTML = '<div class="loading">Đang tải lịch sử đơn hàng...</div>';
        
        const response = await fetch(`${API_BASE_URL}/orders`, {
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('authToken')}`
            }
        });
        
        if (response.ok) {
            const orders = await response.json();
            
            if (orders.length === 0) {
                ordersContainer.innerHTML = '<p class="empty-state">Chưa có đơn hàng nào.</p>';
            } else {
                ordersContainer.innerHTML = generateOrdersTable(orders);
            }
        } else {
            throw new Error('Failed to load orders');
        }
    } catch (error) {
        console.error('Load orders error:', error);
        ordersContainer.innerHTML = '<div class="error-state">Lỗi tải đơn hàng. <button onclick="loadUserOrders()">Thử lại</button></div>';
    }
}

function generateOrdersTable(orders) {
    let html = `
        <div class="orders-table-container">
            <table class="orders-table">
                <thead>
                    <tr>
                        <th>Mã đơn</th>
                        <th>Loại proxy</th>
                        <th>Số lượng</th>
                        <th>Thời gian</th>
                        <th>Tổng tiền</th>
                        <th>Trạng thái</th>
                        <th>Ngày tạo</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
    `;
    
    orders.forEach(order => {
        const statusClass = order.status === 'completed' ? 'status-active' : 'status-pending';
        const statusText = order.status === 'completed' ? 'Hoàn thành' : 'Đang xử lý';
        const createdDate = new Date(order.createdAt).toLocaleDateString('vi-VN');
        
        html += `
            <tr>
                <td><code>${order.id.substring(0, 8)}</code></td>
                <td>${order.type.toUpperCase()}</td>
                <td>${order.count}</td>
                <td>${order.period} ngày</td>
                <td>${formatPrice(order.totalPrice)}</td>
                <td><span class="status ${statusClass}">${statusText}</span></td>
                <td>${createdDate}</td>
                <td>
                    <button class="btn-small btn-primary" onclick="viewOrderDetails('${order.id}')">Chi tiết</button>
                </td>
            </tr>
        `;
    });
    
    html += '</tbody></table></div>';
    return html;
}

// Enhanced payments loading
async function loadUserPayments() {
    const paymentsContainer = document.querySelector('#payments-tab .payments-list');
    
    try {
        paymentsContainer.innerHTML = '<div class="loading">Đang tải lịch sử thanh toán...</div>';
        
        const response = await fetch(`${API_BASE_URL}/payments`, {
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('authToken')}`
            }
        });
        
        if (response.ok) {
            const payments = await response.json();
            
            if (payments.length === 0) {
                paymentsContainer.innerHTML = `
                    <div class="empty-state">
                        <p>Chưa có giao dịch nào.</p>
                        <button class="btn-primary" onclick="showTopUpModal()">Nạp tiền ngay</button>
                    </div>
                `;
            } else {
                paymentsContainer.innerHTML = generatePaymentsTable(payments);
            }
        } else {
            throw new Error('Failed to load payments');
        }
    } catch (error) {
        console.error('Load payments error:', error);
        paymentsContainer.innerHTML = '<div class="error-state">Lỗi tải lịch sử thanh toán. <button onclick="loadUserPayments()">Thử lại</button></div>';
    }
}

function generatePaymentsTable(payments) {
    let html = `
        <div class="payments-header">
            <h3>Lịch sử giao dịch</h3>
            <button class="btn-primary" onclick="showTopUpModal()">Nạp tiền</button>
        </div>
        <div class="payments-table-container">
            <table class="payments-table">
                <thead>
                    <tr>
                        <th>Mã GD</th>
                        <th>Loại</th>
                        <th>Số tiền</th>
                        <th>Phương thức</th>
                        <th>Trạng thái</th>
                        <th>Ngày tạo</th>
                        <th>Ghi chú</th>
                    </tr>
                </thead>
                <tbody>
    `;
    
    payments.forEach(payment => {
        const statusClass = payment.status === 'completed' ? 'status-active' : 
                          payment.status === 'pending' ? 'status-pending' : 'status-expired';
        const statusText = payment.status === 'completed' ? 'Thành công' : 
                          payment.status === 'pending' ? 'Đang xử lý' : 'Thất bại';
        const createdDate = new Date(payment.createdAt).toLocaleDateString('vi-VN');
        const typeText = payment.type === 'topup' ? 'Nạp tiền' : 'Mua proxy';
        const amountClass = payment.type === 'topup' ? 'amount-positive' : 'amount-negative';
        const amountPrefix = payment.type === 'topup' ? '+' : '-';
        
        html += `
            <tr>
                <td><code>${payment.id.substring(0, 8)}</code></td>
                <td>${typeText}</td>
                <td><span class="${amountClass}">${amountPrefix}${formatPrice(payment.amount)}</span></td>
                <td>${payment.method || 'N/A'}</td>
                <td><span class="status ${statusClass}">${statusText}</span></td>
                <td>${createdDate}</td>
                <td>${payment.note || ''}</td>
            </tr>
        `;
    });
    
    html += '</tbody></table></div>';
    return html;
}

// Top-up modal
function showTopUpModal() {
    const modal = document.createElement('div');
    modal.className = 'modal';
    modal.innerHTML = `
        <div class="modal-content">
            <div class="modal-header">
                <h3>Nạp tiền vào tài khoản</h3>
                <span class="close" onclick="this.closest('.modal').remove()">&times;</span>
            </div>
            <div class="modal-body">
                <div class="current-balance">
                    <p>Số dư hiện tại: <strong>${formatPrice(currentUser.balance)}</strong></p>
                </div>
                
                <div class="form-group">
                    <label>Số tiền nạp:</label>
                    <select id="topup-amount" onchange="updateTopUpAmount()">
                        <option value="100000">100.000 ₫</option>
                        <option value="200000">200.000 ₫</option>
                        <option value="500000">500.000 ₫</option>
                        <option value="1000000">1.000.000 ₫</option>
                        <option value="custom">Số tiền khác...</option>
                    </select>
                    <input type="number" id="custom-amount" style="display:none" placeholder="Nhập số tiền...">
                </div>
                
                <div class="form-group">
                    <label>Phương thức thanh toán:</label>
                    <div class="payment-methods">
                        <label class="payment-method">
                            <input type="radio" name="payment-method" value="momo" checked>
                            <img src="assets/images/momo.png" alt="MoMo" class="payment-icon">
                            MoMo
                        </label>
                        <label class="payment-method">
                            <input type="radio" name="payment-method" value="banking">
                            <img src="assets/images/banking.png" alt="Banking" class="payment-icon">
                            Chuyển khoản
                        </label>
                        <label class="payment-method">
                            <input type="radio" name="payment-method" value="card">
                            <img src="assets/images/card.png" alt="Card" class="payment-icon">
                            Thẻ ATM/Visa
                        </label>
                    </div>
                </div>
                
                <div class="bonus-info">
                    <p>🎁 Nạp từ 500.000₫ được tặng thêm 5%</p>
                    <p>🎁 Nạp từ 1.000.000₫ được tặng thêm 10%</p>
                </div>
                
                <button class="btn-primary btn-full" onclick="processTopUp()">Nạp tiền</button>
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
    modal.style.display = 'block';
}

function updateTopUpAmount() {
    const select = document.getElementById('topup-amount');
    const customInput = document.getElementById('custom-amount');
    
    if (select.value === 'custom') {
        customInput.style.display = 'block';
        customInput.focus();
    } else {
        customInput.style.display = 'none';
    }
}

async function processTopUp() {
    const amountSelect = document.getElementById('topup-amount');
    const customAmount = document.getElementById('custom-amount');
    const paymentMethod = document.querySelector('input[name="payment-method"]:checked').value;
    
    let amount = amountSelect.value === 'custom' ? 
                parseInt(customAmount.value) : 
                parseInt(amountSelect.value);
    
    if (!amount || amount < 10000) {
        showMessage('Số tiền nạp tối thiểu là 10.000₫', 'error');
        return;
    }
    
    try {
        showLoading('Đang xử lý thanh toán...');
        
        const response = await fetch(`${API_BASE_URL}/topup`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${localStorage.getItem('authToken')}`
            },
            body: JSON.stringify({ amount, method: paymentMethod })
        });
        
        const data = await response.json();
        
        if (response.ok) {
            // Close modal
            document.querySelector('.modal').remove();
            
            // Show payment instructions
            showPaymentInstructions(data);
        } else {
            showMessage(data.error || 'Lỗi tạo đơn nạp tiền', 'error');
        }
    } catch (error) {
        console.error('Top up error:', error);
        showMessage('Lỗi kết nối server', 'error');
    } finally {
        hideLoading();
    }
}

function showPaymentInstructions(paymentData) {
    const modal = document.createElement('div');
    modal.className = 'modal';
    modal.innerHTML = `
        <div class="modal-content">
            <div class="modal-header">
                <h3>Hướng dẫn thanh toán</h3>
                <span class="close" onclick="this.closest('.modal').remove()">&times;</span>
            </div>
            <div class="modal-body">
                <div class="payment-info">
                    <p><strong>Mã đơn hàng:</strong> <code>${paymentData.orderId}</code></p>
                    <p><strong>Số tiền:</strong> <strong>${formatPrice(paymentData.amount)}</strong></p>
                    <p><strong>Phương thức:</strong> ${paymentData.method}</p>
                </div>
                
                <div class="payment-instructions">
                    ${generatePaymentInstructions(paymentData)}
                </div>
                
                <div class="payment-status">
                    <p>Trạng thái: <span class="status status-pending">Chờ thanh toán</span></p>
                    <button class="btn-secondary" onclick="checkPaymentStatus('${paymentData.orderId}')">Kiểm tra thanh toán</button>
                </div>
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
    modal.style.display = 'block';
}

function generatePaymentInstructions(paymentData) {
    switch (paymentData.method) {
        case 'momo':
            return `
                <h4>Thanh toán qua MoMo:</h4>
                <ol>
                    <li>Mở ứng dụng MoMo</li>
                    <li>Chọn "Chuyển tiền" → "Đến số điện thoại"</li>
                    <li>Nhập số: <strong>0123456789</strong></li>
                    <li>Số tiền: <strong>${formatPrice(paymentData.amount)}</strong></li>
                    <li>Nội dung: <strong>${paymentData.orderId}</strong></li>
                    <li>Xác nhận chuyển tiền</li>
                </ol>
            `;
        case 'banking':
            return `
                <h4>Chuyển khoản ngân hàng:</h4>
                <div class="bank-info">
                    <p><strong>Ngân hàng:</strong> Vietcombank</p>
                    <p><strong>Số tài khoản:</strong> 1234567890</p>
                    <p><strong>Chủ tài khoản:</strong> PROXY PRIVATE</p>
                    <p><strong>Số tiền:</strong> ${formatPrice(paymentData.amount)}</p>
                    <p><strong>Nội dung:</strong> ${paymentData.orderId}</p>
                </div>
            `;
        case 'card':
            return `
                <h4>Thanh toán thẻ ATM/Visa:</h4>
                <p>Bạn sẽ được chuyển đến cổng thanh toán an toàn để thực hiện giao dịch.</p>
                <button class="btn-primary" onclick="redirectToPaymentGateway('${paymentData.orderId}')">Thanh toán ngay</button>
            `;
        default:
            return '<p>Phương thức thanh toán không hợp lệ.</p>';
    }
}

// Enhanced profile management
function loadUserProfile() {
    if (!currentUser) return;
    
    const form = document.getElementById('profile-form');
    if (form) {
        document.getElementById('profile-name').value = currentUser.name || '';
        document.getElementById('profile-email').value = currentUser.email || '';
        document.getElementById('profile-phone').value = currentUser.phone || '';
        document.getElementById('profile-birthday').value = currentUser.birthday || '';
    }
}

// API key management
function copyApiKey() {
    const apiKey = document.getElementById('user-api-key').textContent;
    navigator.clipboard.writeText(apiKey).then(() => {
        showMessage('Đã sao chép API key!', 'success');
        
        // Visual feedback
        const button = event.target;
        const originalText = button.textContent;
        button.textContent = 'Đã sao chép!';
        button.classList.add('copied');
        
        setTimeout(() => {
            button.textContent = originalText;
            button.classList.remove('copied');
        }, 2000);
    }).catch(() => {
        showMessage('Lỗi sao chép API key', 'error');
    });
}

async function regenerateApiKey() {
    if (!confirm('Bạn có chắc chắn muốn tạo lại API key? API key cũ sẽ không còn hoạt động.')) {
        return;
    }
    
    try {
        const response = await fetch(`${API_BASE_URL}/user/regenerate-api-key`, {
            method: 'POST',
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('authToken')}`
            }
        });
        
        if (response.ok) {
            const data = await response.json();
            currentUser.apiKey = data.apiKey;
            document.getElementById('user-api-key').textContent = data.apiKey;
            showMessage('Đã tạo API key mới thành công!', 'success');
        } else {
            throw new Error('Failed to regenerate API key');
        }
    } catch (error) {
        console.error('Regenerate API key error:', error);
        showMessage('Lỗi tạo API key mới', 'error');
    }
}

// Enhanced stats animation
function updateStats() {
    const stats = [
        { id: 'users-count', target: 15247, suffix: '' },
        { id: 'sold-proxies', target: 892156, suffix: '' },
        { id: 'active-proxies', target: 25847, suffix: '' },
        { id: 'orders-processed', target: 156923, suffix: '' }
    ];
    
    // Add intersection observer for animation trigger
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const stat = stats.find(s => s.id === entry.target.id);
                if (stat) {
                    animateNumber(stat.id, stat.target, stat.suffix);
                    observer.unobserve(entry.target);
                }
            }
        });
    });
    
    stats.forEach(stat => {
        const element = document.getElementById(stat.id);
        if (element) {
            observer.observe(element);
        }
    });
}

function animateNumber(elementId, target, suffix = '') {
    const element = document.getElementById(elementId);
    if (!element) return;
    
    let current = 0;
    const increment = target / 100;
    const duration = 2000; // 2 seconds
    const stepTime = duration / 100;
    
    const timer = setInterval(() => {
        current += increment;
        if (current >= target) {
            current = target;
            clearInterval(timer);
        }
        
        const displayValue = Math.floor(current).toLocaleString('vi-VN');
        element.textContent = displayValue + suffix;
    }, stepTime);
}

// Initialize URL routing
function initializeRouting() {
    // Handle initial URL
    const hash = window.location.hash.substring(1);
    if (hash) {
        if (hash.startsWith('dashboard/')) {
            const tab = hash.split('/')[1];
            showSection('dashboard');
            setTimeout(() => showDashboardTab(tab), 100);
        } else {
            showSection(hash);
        }
    }
    
    // Handle browser back/forward
    window.addEventListener('popstate', () => {
        const hash = window.location.hash.substring(1);
        if (hash) {
            if (hash.startsWith('dashboard/')) {
                const tab = hash.split('/')[1];
                showSection('dashboard');
                setTimeout(() => showDashboardTab(tab), 100);
            } else {
                showSection(hash);
            }
        } else {
            showSection('home');
        }
    });
}

// Initialize routing when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    initializeRouting();
});

// Service Worker registration for offline support
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js')
            .then(registration => {
                console.log('SW registered: ', registration);
            })
            .catch(registrationError => {
                console.log('SW registration failed: ', registrationError);
            });
    });
}

