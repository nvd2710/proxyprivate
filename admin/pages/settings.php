<?php
/**
 * Admin Settings Page
 * File: admin/pages/settings.php
 */

// Xử lý cập nhật cài đặt
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        foreach ($_POST as $key => $value) {
            if (strpos($key, 'setting_') === 0) {
                $setting_key = substr($key, 8); // Bỏ "setting_" prefix
                setSetting($setting_key, $value);
            }
        }
        $success_message = 'Cập nhật cài đặt thành công!';
    } catch (Exception $e) {
        $error_message = 'Lỗi cập nhật cài đặt: ' . $e->getMessage();
    }
}

// Lấy tất cả cài đặt
$all_settings = fetchAll("SELECT * FROM system_settings ORDER BY setting_key");
$settings = [];
foreach ($all_settings as $setting) {
    $settings[$setting['setting_key']] = $setting['setting_value'];
}
?>

<div class="content-header">
    <h2>Cấu hình hệ thống</h2>
    <p>Quản lý các thiết lập và cấu hình của website</p>
</div>

<?php if (isset($success_message)): ?>
<div class="alert alert-success">
    <i class="fas fa-check-circle"></i>
    <?= htmlspecialchars($success_message) ?>
</div>
<?php endif; ?>

<?php if (isset($error_message)): ?>
<div class="alert alert-danger">
    <i class="fas fa-exclamation-triangle"></i>
    <?= htmlspecialchars($error_message) ?>
</div>
<?php endif; ?>

<form method="POST" action="">
    <div class="settings-tabs">
        <div class="tab-navigation">
            <button type="button" class="tab-btn active" onclick="showTab('general')">
                <i class="fas fa-cog"></i> Cài đặt chung
            </button>
            <button type="button" class="tab-btn" onclick="showTab('email')">
                <i class="fas fa-envelope"></i> Email
            </button>
            <button type="button" class="tab-btn" onclick="showTab('payment')">
                <i class="fas fa-credit-card"></i> Thanh toán
            </button>
            <button type="button" class="tab-btn" onclick="showTab('api')">
                <i class="fas fa-code"></i> API
            </button>
            <button type="button" class="tab-btn" onclick="showTab('security')">
                <i class="fas fa-shield-alt"></i> Bảo mật
            </button>
            <button type="button" class="tab-btn" onclick="showTab('maintenance')">
                <i class="fas fa-tools"></i> Bảo trì
            </button>
        </div>
        
        <!-- General Settings -->
        <div id="general-tab" class="tab-content active">
            <div class="form-container">
                <h3>Thông tin website</h3>
                
                <div class="form-grid">
                    <div class="form-group">
                    <button type="button" class="btn btn-secondary" onclick="testEmail()">
                        <i class="fas fa-paper-plane"></i>
                        Test gửi email
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Payment Settings -->
        <div id="payment-tab" class="tab-content">
            <div class="form-container">
                <h3>Cổng thanh toán</h3>
                
                <div class="payment-gateway">
                    <h4>MoMo</h4>
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label">
                                <input type="checkbox" name="setting_momo_enabled" value="1" 
                                       <?= ($settings['momo_enabled'] ?? '0') == '1' ? 'checked' : '' ?>>
                                Kích hoạt MoMo
                            </label>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Partner Code:</label>
                            <input type="text" name="setting_momo_partner_code" class="form-input" 
                                   value="<?= htmlspecialchars($settings['momo_partner_code'] ?? '') ?>">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Access Key:</label>
                            <input type="text" name="setting_momo_access_key" class="form-input" 
                                   value="<?= htmlspecialchars($settings['momo_access_key'] ?? '') ?>">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Secret Key:</label>
                            <input type="password" name="setting_momo_secret_key" class="form-input" 
                                   value="<?= htmlspecialchars($settings['momo_secret_key'] ?? '') ?>">
                        </div>
                    </div>
                </div>
                
                <div class="payment-gateway">
                    <h4>VNPay</h4>
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label">
                                <input type="checkbox" name="setting_vnpay_enabled" value="1" 
                                       <?= ($settings['vnpay_enabled'] ?? '0') == '1' ? 'checked' : '' ?>>
                                Kích hoạt VNPay
                            </label>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">TMN Code:</label>
                            <input type="text" name="setting_vnpay_tmn_code" class="form-input" 
                                   value="<?= htmlspecialchars($settings['vnpay_tmn_code'] ?? '') ?>">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Hash Secret:</label>
                            <input type="password" name="setting_vnpay_hash_secret" class="form-input" 
                                   value="<?= htmlspecialchars($settings['vnpay_hash_secret'] ?? '') ?>">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">URL:</label>
                            <input type="url" name="setting_vnpay_url" class="form-input" 
                                   value="<?= htmlspecialchars($settings['vnpay_url'] ?? 'https://sandbox.vnpayment.vn/paymentv2/vpcpay.html') ?>">
                        </div>
                    </div>
                </div>
                
                <div class="payment-gateway">
                    <h4>ZaloPay</h4>
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label">
                                <input type="checkbox" name="setting_zalopay_enabled" value="1" 
                                       <?= ($settings['zalopay_enabled'] ?? '0') == '1' ? 'checked' : '' ?>>
                                Kích hoạt ZaloPay
                            </label>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">App ID:</label>
                            <input type="text" name="setting_zalopay_app_id" class="form-input" 
                                   value="<?= htmlspecialchars($settings['zalopay_app_id'] ?? '') ?>">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Key 1:</label>
                            <input type="password" name="setting_zalopay_key1" class="form-input" 
                                   value="<?= htmlspecialchars($settings['zalopay_key1'] ?? '') ?>">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Key 2:</label>
                            <input type="password" name="setting_zalopay_key2" class="form-input" 
                                   value="<?= htmlspecialchars($settings['zalopay_key2'] ?? '') ?>">
                        </div>
                    </div>
                </div>
                
                <h3>Cài đặt thanh toán</h3>
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Số tiền nạp tối thiểu (VND):</label>
                        <input type="number" name="setting_min_deposit_amount" class="form-input" 
                               value="<?= htmlspecialchars($settings['min_deposit_amount'] ?? '100000') ?>">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Số tiền nạp tối đa (VND):</label>
                        <input type="number" name="setting_max_deposit_amount" class="form-input" 
                               value="<?= htmlspecialchars($settings['max_deposit_amount'] ?? '50000000') ?>">
                    </div>
                </div>
            </div>
        </div>
        
        <!-- API Settings -->
        <div id="api-tab" class="tab-content">
            <div class="form-container">
                <h3>Cấu hình API Proxy</h3>
                
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">API Endpoint:</label>
                        <input type="url" name="setting_api_proxy_endpoint" class="form-input" 
                               value="<?= htmlspecialchars($settings['api_proxy_endpoint'] ?? 'https://px6.link/api') ?>">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">API Key:</label>
                        <input type="text" name="setting_api_proxy_key" class="form-input" 
                               value="<?= htmlspecialchars($settings['api_proxy_key'] ?? '94b8ddc0da-572e9c845e-34c2fa225a') ?>">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Timeout (giây):</label>
                        <input type="number" name="setting_api_timeout" class="form-input" 
                               value="<?= htmlspecialchars($settings['api_timeout'] ?? '30') ?>">
                    </div>
                    
                    <div class="form-group">
                        <button type="button" class="btn btn-secondary" onclick="testProxyAPI()">
                            <i class="fas fa-plug"></i>
                            Test kết nối API
                        </button>
                    </div>
                </div>
                
                <h3>Rate Limiting</h3>
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Giới hạn API (requests/phút):</label>
                        <input type="number" name="setting_api_rate_limit" class="form-input" 
                               value="<?= htmlspecialchars($settings['api_rate_limit'] ?? '100') ?>">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Giới hạn đăng nhập (attempts/15 phút):</label>
                        <input type="number" name="setting_login_rate_limit" class="form-input" 
                               value="<?= htmlspecialchars($settings['login_rate_limit'] ?? '5') ?>">
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Security Settings -->
        <div id="security-tab" class="tab-content">
            <div class="form-container">
                <h3>Bảo mật</h3>
                
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Thời gian sống session (giây):</label>
                        <input type="number" name="setting_session_lifetime" class="form-input" 
                               value="<?= htmlspecialchars($settings['session_lifetime'] ?? '7200') ?>">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">
                            <input type="checkbox" name="setting_force_https" value="1" 
                                   <?= ($settings['force_https'] ?? '1') == '1' ? 'checked' : '' ?>>
                            Bắt buộc HTTPS
                        </label>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">
                            <input type="checkbox" name="setting_enable_2fa" value="1" 
                                   <?= ($settings['enable_2fa'] ?? '0') == '1' ? 'checked' : '' ?>>
                            Kích hoạt 2FA (đang phát triển)
                        </label>
                    </div>
                </div>
                
                <h3>reCAPTCHA</h3>
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">
                            <input type="checkbox" name="setting_recaptcha_enabled" value="1" 
                                   <?= ($settings['recaptcha_enabled'] ?? '0') == '1' ? 'checked' : '' ?>>
                            Kích hoạt reCAPTCHA
                        </label>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Site Key:</label>
                        <input type="text" name="setting_recaptcha_site_key" class="form-input" 
                               value="<?= htmlspecialchars($settings['recaptcha_site_key'] ?? '') ?>">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Secret Key:</label>
                        <input type="password" name="setting_recaptcha_secret_key" class="form-input" 
                               value="<?= htmlspecialchars($settings['recaptcha_secret_key'] ?? '') ?>">
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Maintenance Settings -->
        <div id="maintenance-tab" class="tab-content">
            <div class="form-container">
                <h3>Chế độ bảo trì</h3>
                
                <div class="form-group">
                    <label class="form-label">
                        <input type="checkbox" name="setting_maintenance_mode" value="1" 
                               <?= ($settings['maintenance_mode'] ?? '0') == '1' ? 'checked' : '' ?>>
                        Bật chế độ bảo trì
                    </label>
                    <small class="form-help">Khi bật, chỉ admin mới có thể truy cập website</small>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Thông báo bảo trì:</label>
                    <textarea name="setting_maintenance_message" class="form-textarea"><?= htmlspecialchars($settings['maintenance_message'] ?? 'Website đang trong quá trình bảo trì. Vui lòng quay lại sau.') ?></textarea>
                </div>
                
                <h3>Dọn dẹp dữ liệu</h3>
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Xóa log cũ hơn (ngày):</label>
                        <input type="number" name="setting_cleanup_logs_days" class="form-input" 
                               value="<?= htmlspecialchars($settings['cleanup_logs_days'] ?? '90') ?>">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Xóa session hết hạn (ngày):</label>
                        <input type="number" name="setting_cleanup_sessions_days" class="form-input" 
                               value="<?= htmlspecialchars($settings['cleanup_sessions_days'] ?? '7') ?>">
                    </div>
                    
                    <div class="form-group">
                        <button type="button" class="btn btn-warning" onclick="runCleanup()">
                            <i class="fas fa-broom"></i>
                            Chạy dọn dẹp ngay
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="form-actions">
        <button type="submit" class="btn btn-primary">
            <i class="fas fa-save"></i>
            Lưu cài đặt
        </button>
        
        <button type="button" class="btn btn-secondary" onclick="resetSettings()">
            <i class="fas fa-undo"></i>
            Reset về mặc định
        </button>
    </div>
</form>

<style>
.settings-tabs {
    background: white;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    overflow: hidden;
}

.tab-navigation {
    display: flex;
    background-color: #f8f9fa;
    border-bottom: 1px solid #dee2e6;
    overflow-x: auto;
}

.tab-btn {
    padding: 15px 20px;
    border: none;
    background: none;
    cursor: pointer;
    border-bottom: 3px solid transparent;
    transition: all 0.3s ease;
    white-space: nowrap;
    font-weight: 500;
}

.tab-btn:hover,
.tab-btn.active {
    background-color: white;
    border-bottom-color: #667eea;
    color: #667eea;
}

.tab-content {
    display: none;
    padding: 30px;
}

.tab-content.active {
    display: block;
}

.payment-gateway {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 8px;
    margin-bottom: 20px;
}

.payment-gateway h4 {
    margin-bottom: 15px;
    color: #495057;
}

.form-actions {
    background: white;
    padding: 20px 30px;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    margin-top: 20px;
    display: flex;
    gap: 15px;
}

.form-help {
    color: #6c757d;
    font-size: 12px;
    margin-top: 5px;
    display: block;
}

.alert {
    padding: 15px 20px;
    border-radius: 8px;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
}

.alert i {
    margin-right: 10px;
    font-size: 18px;
}

.alert-success {
    background-color: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.alert-danger {
    background-color: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

.content-header {
    margin-bottom: 30px;
}

.content-header h2 {
    color: #343a40;
    margin-bottom: 5px;
}

.content-header p {
    color: #6c757d;
    margin: 0;
}

@media (max-width: 768px) {
    .tab-navigation {
        flex-direction: column;
    }
    
    .form-grid {
        grid-template-columns: 1fr;
    }
    
    .form-actions {
        flex-direction: column;
    }
}
</style>

<script>
function showTab(tabName) {
    // Ẩn tất cả tab content
    document.querySelectorAll('.tab-content').forEach(tab => {
        tab.classList.remove('active');
    });
    
    // Ẩn tất cả tab button active
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    
    // Hiển thị tab được chọn
    document.getElementById(tabName + '-tab').classList.add('active');
    event.target.classList.add('active');
}

function testEmail() {
    const email = prompt('Nhập email để test gửi:');
    if (!email) return;
    
    fetch('api/admin/test-email.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ email: email })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('success', 'Thành công', 'Email test đã được gửi');
        } else {
            showToast('error', 'Lỗi', data.message);
        }
    });
}

function testProxyAPI() {
    fetch('api/admin/test-proxy-api.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('success', 'Thành công', 'Kết nối API proxy thành công');
        } else {
            showToast('error', 'Lỗi', data.message);
        }
    });
}

function runCleanup() {
    if (!confirm('Bạn có chắc chắn muốn chạy dọn dẹp dữ liệu?')) {
        return;
    }
    
    fetch('api/admin/cleanup.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('success', 'Thành công', 'Đã dọn dẹp dữ liệu cũ');
        } else {
            showToast('error', 'Lỗi', data.message);
        }
    });
}

function resetSettings() {
    if (!confirm('Bạn có chắc chắn muốn reset tất cả cài đặt về mặc định?')) {
        return;
    }
    
    fetch('api/admin/reset-settings.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('success', 'Thành công', 'Đã reset cài đặt về mặc định');
            setTimeout(() => location.reload(), 1000);
        } else {
            showToast('error', 'Lỗi', data.message);
        }
    });
}
</script>
                        <label class="form-label">Tên website:</label>
                        <input type="text" name="setting_site_name" class="form-input" 
                               value="<?= htmlspecialchars($settings['site_name'] ?? 'ProxyPrivate.vn') ?>">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">URL website:</label>
                        <input type="url" name="setting_site_url" class="form-input" 
                               value="<?= htmlspecialchars($settings['site_url'] ?? 'https://proxyprivate.vn') ?>">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Email liên hệ:</label>
                        <input type="email" name="setting_contact_email" class="form-input" 
                               value="<?= htmlspecialchars($settings['contact_email'] ?? 'contact@proxyprivate.vn') ?>">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Số điện thoại:</label>
                        <input type="tel" name="setting_contact_phone" class="form-input" 
                               value="<?= htmlspecialchars($settings['contact_phone'] ?? '+84 123 456 789') ?>">
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Mô tả website:</label>
                    <textarea name="setting_site_description" class="form-textarea"><?= htmlspecialchars($settings['site_description'] ?? 'Dịch vụ proxy riêng tư chất lượng cao') ?></textarea>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Địa chỉ:</label>
                    <textarea name="setting_contact_address" class="form-textarea"><?= htmlspecialchars($settings['contact_address'] ?? 'Hà Nội, Việt Nam') ?></textarea>
                </div>
                
                <h3>Cài đặt chức năng</h3>
                
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">
                            <input type="checkbox" name="setting_enable_registration" value="1" 
                                   <?= ($settings['enable_registration'] ?? '1') == '1' ? 'checked' : '' ?>>
                            Cho phép đăng ký tài khoản mới
                        </label>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">
                            <input type="checkbox" name="setting_require_email_verification" value="1" 
                                   <?= ($settings['require_email_verification'] ?? '1') == '1' ? 'checked' : '' ?>>
                            Yêu cầu xác thực email
                        </label>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Loại tiền tệ mặc định:</label>
                        <select name="setting_default_currency" class="form-select">
                            <option value="VND" <?= ($settings['default_currency'] ?? 'VND') == 'VND' ? 'selected' : '' ?>>VND</option>
                            <option value="USD" <?= ($settings['default_currency'] ?? 'VND') == 'USD' ? 'selected' : '' ?>>USD</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Tỷ giá USD/VND:</label>
                        <input type="number" name="setting_currency_exchange_rate" class="form-input" 
                               value="<?= htmlspecialchars($settings['currency_exchange_rate'] ?? '24000') ?>">
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Email Settings -->
        <div id="email-tab" class="tab-content">
            <div class="form-container">
                <h3>Cấu hình SMTP</h3>
                
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">SMTP Host:</label>
                        <input type="text" name="setting_smtp_host" class="form-input" 
                               value="<?= htmlspecialchars($settings['smtp_host'] ?? 'mail.proxyprivate.vn') ?>">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">SMTP Port:</label>
                        <input type="number" name="setting_smtp_port" class="form-input" 
                               value="<?= htmlspecialchars($settings['smtp_port'] ?? '587') ?>">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">SMTP Username:</label>
                        <input type="email" name="setting_smtp_user" class="form-input" 
                               value="<?= htmlspecialchars($settings['smtp_user'] ?? 'noreply@proxyprivate.vn') ?>">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">SMTP Password:</label>
                        <input type="password" name="setting_smtp_pass" class="form-input" 
                               value="<?= htmlspecialchars($settings['smtp_pass'] ?? '') ?>" placeholder="Nhập mật khẩu SMTP">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">SMTP Security:</label>
                        <select name="setting_smtp_security" class="form-select">
                            <option value="tls" <?= ($settings['smtp_security'] ?? 'tls') == 'tls' ? 'selected' : '' ?>>TLS</option>
                            <option value="ssl" <?= ($settings['smtp_security'] ?? 'tls') == 'ssl' ? 'selected' : '' ?>>SSL</option>
                            <option value="none" <?= ($settings['smtp_security'] ?? 'tls') == 'none' ? 'selected' : '' ?>>None</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Email người gửi:</label>
                        <input type="email" name="setting_from_email" class="form-input" 
                               value="<?= htmlspecialchars($settings['from_email'] ?? 'noreply@proxyprivate.vn') ?>">
                    </div>
                </div>
                
                <div class="form-group">