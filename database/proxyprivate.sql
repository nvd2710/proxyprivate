-- =====================================================
-- Database ProxyPrivate.vn - Phiên bản đơn giản
-- Không cần email verification
-- File: proxyprivate_simple.sql
-- =====================================================

-- Thiết lập timezone và charset
SET time_zone = '+07:00';
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- =====================================================
-- 1. BẢNG NGƯỜI DÙNG
-- =====================================================

CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL COMMENT 'Họ và tên',
    email VARCHAR(255) UNIQUE NOT NULL COMMENT 'Email đăng nhập',
    phone VARCHAR(20) COMMENT 'Số điện thoại',
    password_hash VARCHAR(255) NOT NULL COMMENT 'Mật khẩu đã mã hóa',
    api_key VARCHAR(100) UNIQUE NOT NULL COMMENT 'API key riêng',
    balance DECIMAL(15,2) DEFAULT 0.00 COMMENT 'Số dư tài khoản (VND)',
    currency VARCHAR(3) DEFAULT 'VND' COMMENT 'Loại tiền tệ',
    avatar VARCHAR(255) COMMENT 'Đường dẫn ảnh đại diện',
    birthday DATE COMMENT 'Ngày sinh',
    address TEXT COMMENT 'Địa chỉ',
    status ENUM('active', 'inactive', 'banned') DEFAULT 'active' COMMENT 'Trạng thái tài khoản',
    email_verified_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Thời gian xác thực email',
    last_login_at TIMESTAMP NULL COMMENT 'Lần đăng nhập cuối',
    last_login_ip VARCHAR(45) COMMENT 'IP đăng nhập cuối',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Thời gian tạo',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Thời gian cập nhật',
    
    INDEX idx_email (email),
    INDEX idx_api_key (api_key),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_vietnamese_ci COMMENT='Bảng thông tin người dùng';

-- =====================================================
-- 2. BẢNG PROXY
-- =====================================================

CREATE TABLE proxies (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL COMMENT 'ID người dùng sở hữu',
    external_id VARCHAR(50) COMMENT 'ID từ hệ thống proxy bên ngoài',
    ip_address VARCHAR(45) NOT NULL COMMENT 'Địa chỉ IP proxy',
    host VARCHAR(45) NOT NULL COMMENT 'Host kết nối',
    port INT NOT NULL COMMENT 'Cổng kết nối',
    username VARCHAR(50) NOT NULL COMMENT 'Tên đăng nhập proxy',
    password VARCHAR(50) NOT NULL COMMENT 'Mật khẩu proxy',
    type ENUM('http', 'https', 'socks4', 'socks5') NOT NULL COMMENT 'Loại proxy',
    protocol_version ENUM('ipv4', 'ipv6', 'ipv4_shared') NOT NULL COMMENT 'Phiên bản giao thức',
    country_code VARCHAR(2) NOT NULL COMMENT 'Mã quốc gia (ISO 2)',
    country_name VARCHAR(100) COMMENT 'Tên quốc gia',
    city VARCHAR(100) COMMENT 'Thành phố',
    status ENUM('active', 'expired', 'suspended') DEFAULT 'active' COMMENT 'Trạng thái proxy',
    expires_at TIMESTAMP NOT NULL COMMENT 'Thời gian hết hạn',
    last_checked_at TIMESTAMP NULL COMMENT 'Lần kiểm tra cuối',
    is_working BOOLEAN DEFAULT TRUE COMMENT 'Proxy có hoạt động không',
    bandwidth_used BIGINT DEFAULT 0 COMMENT 'Băng thông đã sử dụng (bytes)',
    max_bandwidth BIGINT COMMENT 'Giới hạn băng thông (bytes)',
    comment TEXT COMMENT 'Ghi chú của người dùng',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Thời gian tạo',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Thời gian cập nhật',
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_status (status),
    INDEX idx_expires_at (expires_at),
    INDEX idx_country_code (country_code),
    INDEX idx_external_id (external_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_vietnamese_ci COMMENT='Bảng quản lý proxy';

-- =====================================================
-- 3. BẢNG ĐƠN HÀNG
-- =====================================================

CREATE TABLE orders (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL COMMENT 'ID người dùng',
    order_number VARCHAR(20) UNIQUE NOT NULL COMMENT 'Số đơn hàng',
    type ENUM('buy_proxy', 'extend_proxy', 'topup_balance') NOT NULL COMMENT 'Loại đơn hàng',
    proxy_type ENUM('ipv4', 'ipv6', 'ipv4_shared') COMMENT 'Loại proxy mua',
    proxy_count INT COMMENT 'Số lượng proxy',
    proxy_period INT COMMENT 'Thời gian proxy (ngày)',
    country_code VARCHAR(2) COMMENT 'Mã quốc gia proxy',
    protocol ENUM('http', 'https', 'socks4', 'socks5') COMMENT 'Giao thức proxy',
    amount DECIMAL(15,2) NOT NULL COMMENT 'Số tiền đơn hàng',
    currency VARCHAR(3) DEFAULT 'VND' COMMENT 'Loại tiền tệ',
    discount_amount DECIMAL(15,2) DEFAULT 0.00 COMMENT 'Số tiền giảm giá',
    final_amount DECIMAL(15,2) NOT NULL COMMENT 'Số tiền cuối cùng',
    status ENUM('pending', 'processing', 'completed', 'failed', 'cancelled') DEFAULT 'pending' COMMENT 'Trạng thái đơn hàng',
    payment_method ENUM('balance', 'bank_transfer', 'momo', 'zalopay', 'vnpay', 'paypal') COMMENT 'Phương thức thanh toán',
    payment_status ENUM('pending', 'paid', 'failed', 'refunded') DEFAULT 'pending' COMMENT 'Trạng thái thanh toán',
    notes TEXT COMMENT 'Ghi chú đơn hàng',
    processed_at TIMESTAMP NULL COMMENT 'Thời gian xử lý',
    completed_at TIMESTAMP NULL COMMENT 'Thời gian hoàn thành',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Thời gian tạo',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Thời gian cập nhật',
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_order_number (order_number),
    INDEX idx_status (status),
    INDEX idx_payment_status (payment_status),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_vietnamese_ci COMMENT='Bảng đơn hàng';

-- =====================================================
-- 4. BẢNG GIAO DỊCH THANH TOÁN
-- =====================================================

CREATE TABLE transactions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL COMMENT 'ID người dùng',
    order_id INT COMMENT 'ID đơn hàng (nếu có)',
    transaction_id VARCHAR(100) UNIQUE NOT NULL COMMENT 'Mã giao dịch',
    type ENUM('deposit', 'withdrawal', 'payment', 'refund', 'bonus') NOT NULL COMMENT 'Loại giao dịch',
    amount DECIMAL(15,2) NOT NULL COMMENT 'Số tiền giao dịch',
    currency VARCHAR(3) DEFAULT 'VND' COMMENT 'Loại tiền tệ',
    balance_before DECIMAL(15,2) NOT NULL COMMENT 'Số dư trước giao dịch',
    balance_after DECIMAL(15,2) NOT NULL COMMENT 'Số dư sau giao dịch',
    payment_method ENUM('balance', 'bank_transfer', 'momo', 'zalopay', 'vnpay', 'paypal') COMMENT 'Phương thức thanh toán',
    gateway_transaction_id VARCHAR(255) COMMENT 'Mã giao dịch từ cổng thanh toán',
    gateway_response TEXT COMMENT 'Phản hồi từ cổng thanh toán',
    status ENUM('pending', 'completed', 'failed', 'cancelled') DEFAULT 'pending' COMMENT 'Trạng thái giao dịch',
    description TEXT COMMENT 'Mô tả giao dịch',
    ip_address VARCHAR(45) COMMENT 'Địa chỉ IP thực hiện giao dịch',
    user_agent TEXT COMMENT 'User agent',
    processed_at TIMESTAMP NULL COMMENT 'Thời gian xử lý',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Thời gian tạo',
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE SET NULL,
    INDEX idx_user_id (user_id),
    INDEX idx_order_id (order_id),
    INDEX idx_transaction_id (transaction_id),
    INDEX idx_type (type),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_vietnamese_ci COMMENT='Bảng giao dịch thanh toán';

-- =====================================================
-- 5. BẢNG PHIÊN ĐĂNG NHẬP
-- =====================================================

CREATE TABLE user_sessions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL COMMENT 'ID người dùng',
    session_token VARCHAR(255) UNIQUE NOT NULL COMMENT 'Token phiên đăng nhập',
    device_info TEXT COMMENT 'Thông tin thiết bị',
    ip_address VARCHAR(45) COMMENT 'Địa chỉ IP',
    user_agent TEXT COMMENT 'User agent',
    last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Hoạt động cuối',
    expires_at TIMESTAMP NOT NULL COMMENT 'Thời gian hết hạn',
    is_active BOOLEAN DEFAULT TRUE COMMENT 'Phiên có hoạt động không',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Thời gian tạo',
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_session_token (session_token),
    INDEX idx_expires_at (expires_at),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_vietnamese_ci COMMENT='Bảng phiên đăng nhập';

-- =====================================================
-- 6. BẢNG CẤU HÌNH HỆ THỐNG
-- =====================================================

CREATE TABLE system_settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    setting_key VARCHAR(100) UNIQUE NOT NULL COMMENT 'Khóa cấu hình',
    setting_value TEXT COMMENT 'Giá trị cấu hình',
    setting_type ENUM('string', 'integer', 'float', 'boolean', 'json') DEFAULT 'string' COMMENT 'Kiểu dữ liệu',
    description TEXT COMMENT 'Mô tả cấu hình',
    is_public BOOLEAN DEFAULT FALSE COMMENT 'Có thể truy cập công khai',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Thời gian tạo',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Thời gian cập nhật',
    
    INDEX idx_setting_key (setting_key),
    INDEX idx_is_public (is_public)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_vietnamese_ci COMMENT='Bảng cấu hình hệ thống';

-- =====================================================
-- 7. BẢNG THÔNG BÁO
-- =====================================================

CREATE TABLE notifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT COMMENT 'ID người dùng (null = thông báo toàn hệ thống)',
    title VARCHAR(255) NOT NULL COMMENT 'Tiêu đề thông báo',
    content TEXT NOT NULL COMMENT 'Nội dung thông báo',
    type ENUM('info', 'success', 'warning', 'error', 'system') DEFAULT 'info' COMMENT 'Loại thông báo',
    is_read BOOLEAN DEFAULT FALSE COMMENT 'Đã đọc chưa',
    action_url VARCHAR(255) COMMENT 'Link hành động',
    expires_at TIMESTAMP COMMENT 'Thời gian hết hạn hiển thị',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Thời gian tạo',
    read_at TIMESTAMP NULL COMMENT 'Thời gian đọc',
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_is_read (is_read),
    INDEX idx_type (type),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_vietnamese_ci COMMENT='Bảng thông báo';

-- =====================================================
-- 8. BẢNG LOG HOẠT ĐỘNG (TÙY CHỌN)
-- =====================================================

CREATE TABLE activity_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT COMMENT 'ID người dùng (null nếu là hệ thống)',
    action VARCHAR(100) NOT NULL COMMENT 'Hành động thực hiện',
    resource_type VARCHAR(50) COMMENT 'Loại tài nguyên (user, proxy, order, etc.)',
    resource_id INT COMMENT 'ID tài nguyên',
    description TEXT COMMENT 'Mô tả chi tiết',
    metadata JSON COMMENT 'Dữ liệu bổ sung (JSON)',
    ip_address VARCHAR(45) COMMENT 'Địa chỉ IP',
    user_agent TEXT COMMENT 'User agent',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Thời gian tạo',
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_user_id (user_id),
    INDEX idx_action (action),
    INDEX idx_resource_type (resource_type),
    INDEX idx_resource_id (resource_id),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_vietnamese_ci COMMENT='Bảng log hoạt động hệ thống';

-- =====================================================
-- DỮ LIỆU MẪU - CẤU HÌNH HỆ THỐNG
-- =====================================================

INSERT INTO system_settings (setting_key, setting_value, setting_type, description, is_public) VALUES
('site_name', 'ProxyPrivate.vn', 'string', 'Tên website', TRUE),
('site_description', 'Dịch vụ proxy riêng tư chất lượng cao cho người Việt', 'string', 'Mô tả website', TRUE),
('site_url', 'https://proxyprivate.vn', 'string', 'URL website', TRUE),
('admin_email', 'admin@proxyprivate.vn', 'string', 'Email quản trị', FALSE),
('support_email', 'support@proxyprivate.vn', 'string', 'Email hỗ trợ', TRUE),
('contact_phone', '+84 123 456 789', 'string', 'Số điện thoại liên hệ', TRUE),
('contact_address', 'Hà Nội, Việt Nam', 'string', 'Địa chỉ liên hệ', TRUE),

-- Cấu hình API
('api_proxy_endpoint', 'https://px6.link/api', 'string', 'Endpoint API proxy', FALSE),
('api_proxy_key', '94b8ddc0da-572e9c845e-34c2fa225a', 'string', 'API key proxy service', FALSE),
('api_timeout', '30', 'integer', 'Timeout API (giây)', FALSE),

-- Cấu hình tiền tệ
('currency_exchange_rate', '24000', 'float', 'Tỷ giá USD sang VND', FALSE),
('default_currency', 'VND', 'string', 'Loại tiền tệ mặc định', TRUE),
('min_deposit_amount', '100000', 'integer', 'Số tiền nạp tối thiểu (VND)', TRUE),
('max_deposit_amount', '50000000', 'integer', 'Số tiền nạp tối đa (VND)', TRUE),

-- Cấu hình chức năng
('enable_registration', '1', 'boolean', 'Cho phép đăng ký tài khoản mới', TRUE),
('require_email_verification', '0', 'boolean', 'Yêu cầu xác thực email (đã tắt)', FALSE),
('session_lifetime', '7200', 'integer', 'Thời gian sống của session (giây)', FALSE),
('maintenance_mode', '0', 'boolean', 'Chế độ bảo trì', TRUE),

-- Cấu hình bảo mật
('force_https', '1', 'boolean', 'Bắt buộc HTTPS', FALSE),
('api_rate_limit', '100', 'integer', 'Giới hạn API (requests/phút)', FALSE),
('login_rate_limit', '5', 'integer', 'Giới hạn đăng nhập (attempts/15 phút)', FALSE),

-- Social links
('facebook_url', 'https://facebook.com/proxyprivate.vn', 'string', 'Link Facebook', TRUE),
('telegram_url', 'https://t.me/proxyprivate_vn', 'string', 'Link Telegram', TRUE),

-- Email settings (để trống, sẽ cấu hình sau)
('smtp_host', '', 'string', 'SMTP Host', FALSE),
('smtp_port', '587', 'integer', 'SMTP Port', FALSE),
('smtp_user', '', 'string', 'SMTP Username', FALSE),
('smtp_pass', '', 'string', 'SMTP Password', FALSE),
('smtp_security', 'tls', 'string', 'SMTP Security (tls/ssl)', FALSE),

-- Payment gateway settings (để trống, sẽ cấu hình sau)
('momo_enabled', '0', 'boolean', 'Kích hoạt MoMo', FALSE),
('vnpay_enabled', '0', 'boolean', 'Kích hoạt VNPay', FALSE),
('zalopay_enabled', '0', 'boolean', 'Kích hoạt ZaloPay', FALSE);

-- =====================================================
-- DỮ LIỆU MẪU - ADMIN USER
-- =====================================================

-- Tạo user admin (password: admin123456)
INSERT INTO users (
    name, 
    email, 
    password_hash, 
    api_key, 
    balance, 
    currency,
    status, 
    email_verified_at,
    created_at
) VALUES (
    'Administrator', 
    'admin@proxyprivate.vn', 
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 
    '94b8ddc0da-572e9c845e-34c2fa225a',
    1000000.00,
    'VND',
    'active', 
    NOW(),
    NOW()
);

-- =====================================================
-- THÔNG BÁO CHÀO MỪNG
-- =====================================================

INSERT INTO notifications (user_id, title, content, type) VALUES
(1, 'Chào mừng Admin!', 'Hệ thống ProxyPrivate.vn đã được cài đặt thành công. Vui lòng đổi mật khẩu admin và cấu hình hệ thống.', 'info');

-- =====================================================
-- VIEWS VÀ INDEXES TỐI ƯU
-- =====================================================

-- Tạo view thống kê người dùng
CREATE VIEW user_statistics AS
SELECT 
    u.id,
    u.name,
    u.email,
    u.balance,
    u.status,
    COUNT(DISTINCT p.id) as total_proxies,
    COUNT(DISTINCT CASE WHEN p.status = 'active' THEN p.id END) as active_proxies,
    COUNT(DISTINCT o.id) as total_orders,
    SUM(CASE WHEN t.type = 'deposit' THEN t.amount ELSE 0 END) as total_deposits,
    SUM(CASE WHEN t.type = 'payment' THEN t.amount ELSE 0 END) as total_payments,
    u.created_at as member_since,
    u.last_login_at
FROM users u
LEFT JOIN proxies p ON u.id = p.user_id
LEFT JOIN orders o ON u.id = o.user_id
LEFT JOIN transactions t ON u.id = t.user_id
GROUP BY u.id;

-- Tạo view thống kê hệ thống
CREATE VIEW system_statistics AS
SELECT 
    (SELECT COUNT(*) FROM users WHERE status = 'active') as total_active_users,
    (SELECT COUNT(*) FROM users WHERE DATE(created_at) = CURDATE()) as new_users_today,
    (SELECT COUNT(*) FROM proxies WHERE status = 'active') as total_active_proxies,
    (SELECT COUNT(*) FROM orders WHERE status = 'completed') as total_completed_orders,
    (SELECT SUM(final_amount) FROM orders WHERE status = 'completed') as total_revenue,
    (SELECT SUM(amount) FROM transactions WHERE type = 'deposit' AND status = 'completed') as total_deposits,
    (SELECT COUNT(*) FROM orders WHERE DATE(created_at) = CURDATE()) as orders_today,
    (SELECT AVG(final_amount) FROM orders WHERE status = 'completed') as average_order_value;

-- =====================================================
-- TRIGGERS TỰ ĐỘNG
-- =====================================================

DELIMITER //

-- Trigger cập nhật balance khi có giao dịch
CREATE TRIGGER update_user_balance_after_transaction 
AFTER INSERT ON transactions
FOR EACH ROW
BEGIN
    IF NEW.status = 'completed' THEN
        UPDATE users 
        SET balance = NEW.balance_after 
        WHERE id = NEW.user_id;
    END IF;
END//

-- Trigger log khi tạo đơn hàng
CREATE TRIGGER log_order_creation 
AFTER INSERT ON orders
FOR EACH ROW
BEGIN
    INSERT INTO activity_logs (user_id, action, resource_type, resource_id, description)
    VALUES (NEW.user_id, 'order_created', 'order', NEW.id, 
            CONCAT('Tạo đơn hàng mới: ', NEW.order_number, ' - Giá trị: ', NEW.final_amount, ' ', NEW.currency));
END//

-- Trigger cập nhật proxy hết hạn
CREATE TRIGGER update_expired_proxies
BEFORE UPDATE ON proxies
FOR EACH ROW
BEGIN
    IF NEW.expires_at < NOW() AND OLD.status = 'active' THEN
        SET NEW.status = 'expired';
    END IF;
END//

DELIMITER ;

-- =====================================================
-- BẬT LẠI FOREIGN KEY CHECKS
-- =====================================================

SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================
-- HOÀN THÀNH
-- =====================================================

SELECT 'Database ProxyPrivate.vn đã được tạo thành công!' as message,
       'Không cần email verification - Đăng ký tự động active' as note,
       'Admin: admin@proxyprivate.vn / admin123456' as admin_info;