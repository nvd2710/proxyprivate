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