<?php
/**
 * Functions Helper - ProxyPrivate.vn
 * File: api/config/functions.php
 */

// Cấu hình chung
define('SITE_NAME', 'ProxyPrivate.vn');
define('SITE_URL', 'https://proxyprivate.vn');
define('API_PROXY_KEY', '94b8ddc0da-572e9c845e-34c2fa225a');
define('API_PROXY_BASE', 'https://px6.link/api');

/**
 * Lấy IP thực của client
 */
function getClientIP() {
    $headers = [
        'HTTP_CF_CONNECTING_IP',     // Cloudflare
        'HTTP_X_FORWARDED_FOR',      // Load balancer/proxy
        'HTTP_X_FORWARDED',          // Proxy
        'HTTP_X_CLUSTER_CLIENT_IP',  // Cluster
        'HTTP_FORWARDED_FOR',        // Proxy
        'HTTP_FORWARDED',            // Proxy
        'REMOTE_ADDR'                // Standard
    ];
    
    foreach ($headers as $header) {
        if (!empty($_SERVER[$header])) {
            $ips = explode(',', $_SERVER[$header]);
            $ip = trim($ips[0]);
            
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                return $ip;
            }
        }
    }
    
    return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
}

/**
 * Tạo token bảo mật
 */
function generateSecureToken($length = 32) {
    return bin2hex(random_bytes($length / 2));
}

/**
 * Tạo API key
 */
function generateApiKey() {
    return generateSecureToken(10) . '-' . generateSecureToken(10) . '-' . generateSecureToken(10);
}

/**
 * Lấy thông tin thiết bị từ User Agent
 */
function getDeviceInfo($user_agent) {
    $device_info = [];
    
    // Detect OS
    if (preg_match('/windows nt/i', $user_agent)) {
        $device_info['os'] = 'Windows';
    } elseif (preg_match('/macintosh|mac os x/i', $user_agent)) {
        $device_info['os'] = 'macOS';
    } elseif (preg_match('/linux/i', $user_agent)) {
        $device_info['os'] = 'Linux';
    } elseif (preg_match('/android/i', $user_agent)) {
        $device_info['os'] = 'Android';
    } elseif (preg_match('/iphone|ipad|ipod/i', $user_agent)) {
        $device_info['os'] = 'iOS';
    } else {
        $device_info['os'] = 'Unknown';
    }
    
    // Detect Browser
    if (preg_match('/chrome/i', $user_agent)) {
        $device_info['browser'] = 'Chrome';
    } elseif (preg_match('/firefox/i', $user_agent)) {
        $device_info['browser'] = 'Firefox';
    } elseif (preg_match('/safari/i', $user_agent)) {
        $device_info['browser'] = 'Safari';
    } elseif (preg_match('/edge/i', $user_agent)) {
        $device_info['browser'] = 'Edge';
    } else {
        $device_info['browser'] = 'Unknown';
    }
    
    // Detect Mobile
    $device_info['is_mobile'] = preg_match('/mobile|android|iphone|ipad|ipod/i', $user_agent);
    
    return json_encode($device_info);
}

/**
 * Rate limiting functions
 */
function getRateLimitKey($key) {
    return sys_get_temp_dir() . '/rate_limit_' . md5($key) . '.txt';
}

function isRateLimited($key, $max_attempts, $time_window) {
    $file = getRateLimitKey($key);
    
    if (!file_exists($file)) {
        return false;
    }
    
    $data = json_decode(file_get_contents($file), true);
    if (!$data) {
        return false;
    }
    
    // Xóa attempts cũ hơn time_window
    $current_time = time();
    $data['attempts'] = array_filter($data['attempts'], function($timestamp) use ($current_time, $time_window) {
        return ($current_time - $timestamp) < $time_window;
    });
    
    // Lưu lại data đã filter
    file_put_contents($file, json_encode($data));
    
    return count($data['attempts']) >= $max_attempts;
}

function incrementRateLimit($key, $time_window) {
    $file = getRateLimitKey($key);
    $current_time = time();
    
    $data = [];
    if (file_exists($file)) {
        $data = json_decode(file_get_contents($file), true) ?: [];
    }
    
    if (!isset($data['attempts'])) {
        $data['attempts'] = [];
    }
    
    // Xóa attempts cũ
    $data['attempts'] = array_filter($data['attempts'], function($timestamp) use ($current_time, $time_window) {
        return ($current_time - $timestamp) < $time_window;
    });
    
    // Thêm attempt mới
    $data['attempts'][] = $current_time;
    
    file_put_contents($file, json_encode($data));
}

function resetRateLimit($key) {
    $file = getRateLimitKey($key);
    if (file_exists($file)) {
        unlink($file);
    }
}

/**
 * Lấy cấu hình hệ thống
 */
function getSetting($key, $default = null) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT setting_value FROM system_settings WHERE setting_key = ?");
        $stmt->execute([$key]);
        $result = $stmt->fetch();
        return $result ? $result['setting_value'] : $default;
    } catch (Exception $e) {
        error_log("Error getting setting {$key}: " . $e->getMessage());
        return $default;
    }
}

/**
 * Cập nhật cấu hình hệ thống
 */
function setSetting($key, $value, $type = 'string') {
    global $pdo;
    try {
        $stmt = $pdo->prepare("
            INSERT INTO system_settings (setting_key, setting_value, setting_type) 
            VALUES (?, ?, ?) 
            ON DUPLICATE KEY UPDATE setting_value = ?, updated_at = NOW()
        ");
        $stmt->execute([$key, $value, $type, $value]);
        return true;
    } catch (Exception $e) {
        error_log("Error setting {$key}: " . $e->getMessage());
        return false;
    }
}

/**
 * Log hoạt động
 */
function logActivity($user_id, $action, $resource_type = null, $resource_id = null, $description = null, $metadata = null) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("
            INSERT INTO activity_logs (user_id, action, resource_type, resource_id, description, metadata, ip_address, user_agent)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $user_id,
            $action,
            $resource_type,
            $resource_id,
            $description,
            $metadata ? json_encode($metadata) : null,
            getClientIP(),
            $_SERVER['HTTP_USER_AGENT'] ?? null
        ]);
    } catch (Exception $e) {
        error_log("Error logging activity: " . $e->getMessage());
    }
}

/**
 * Gửi email
 */
function sendEmail($to, $subject, $body, $is_html = true) {
    // Cấu hình email (có thể sử dụng PHPMailer hoặc service khác)
    $from = getSetting('admin_email', 'noreply@proxyprivate.vn');
    $site_name = getSetting('site_name', SITE_NAME);
    
    $headers = [
        'From: ' . $site_name . ' <' . $from . '>',
        'Reply-To: ' . $from,
        'X-Mailer: PHP/' . phpversion(),
        'MIME-Version: 1.0'
    ];
    
    if ($is_html) {
        $headers[] = 'Content-Type: text/html; charset=UTF-8';
    } else {
        $headers[] = 'Content-Type: text/plain; charset=UTF-8';
    }
    
    // Log email để debug
    error_log("Sending email to: {$to}, Subject: {$subject}");
    
    // Trong production, thay thế bằng service email thực
    return mail($to, $subject, $body, implode("\r\n", $headers));
}

/**
 * Lấy template email
 */
function getEmailTemplate($template_name, $variables = []) {
    $templates = [
        'email_verification' => '
            <!DOCTYPE html>
            <html>
            <head>
                <meta charset="utf-8">
                <title>Xác thực email - ProxyPrivate.vn</title>
            </head>
            <body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
                <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
                    <h1 style="color: #e74c3c;">Chào mừng đến với ProxyPrivate.vn!</h1>
                    <p>Xin chào <strong>{name}</strong>,</p>
                    <p>Cảm ơn bạn đã đăng ký tài khoản tại ProxyPrivate.vn. Để hoàn tất quá trình đăng ký, vui lòng click vào link bên dưới để xác thực email:</p>
                    <div style="text-align: center; margin: 30px 0;">
                        <a href="{verification_link}" style="background-color: #e74c3c; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; display: inline-block;">Xác thực email</a>
                    </div>
                    <p><strong>Lưu ý:</strong> Link này sẽ hết hạn sau {expires_hours} giờ.</p>
                    <p>Nếu bạn không thể click vào nút trên, hãy copy và paste link sau vào trình duyệt:</p>
                    <p style="word-break: break-all; background-color: #f5f5f5; padding: 10px; border-radius: 4px;">{verification_link}</p>
                    <hr style="margin: 30px 0;">
                    <p style="font-size: 12px; color: #666;">
                        Email này được gửi tự động từ hệ thống ProxyPrivate.vn<br>
                        Nếu bạn không đăng ký tài khoản, vui lòng bỏ qua email này.
                    </p>
                </div>
            </body>
            </html>
        ',
        
        'password_reset' => '
            <!DOCTYPE html>
            <html>
            <head>
                <meta charset="utf-8">
                <title>Reset mật khẩu - ProxyPrivate.vn</title>
            </head>
            <body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
                <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
                    <h1 style="color: #e74c3c;">Reset mật khẩu</h1>
                    <p>Xin chào <strong>{name}</strong>,</p>
                    <p>Chúng tôi nhận được yêu cầu reset mật khẩu cho tài khoản của bạn. Click vào link bên dưới để tạo mật khẩu mới:</p>
                    <div style="text-align: center; margin: 30px 0;">
                        <a href="{reset_link}" style="background-color: #e74c3c; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; display: inline-block;">Reset mật khẩu</a>
                    </div>
                    <p><strong>Lưu ý:</strong> Link này sẽ hết hạn sau {expires_hours} giờ.</p>
                    <p>Nếu bạn không yêu cầu reset mật khẩu, vui lòng bỏ qua email này.</p>
                    <hr style="margin: 30px 0;">
                    <p style="font-size: 12px; color: #666;">
                        Email này được gửi tự động từ hệ thống ProxyPrivate.vn
                    </p>
                </div>
            </body>
            </html>
        '
    ];
    
    if (!isset($templates[$template_name])) {
        return '';
    }
    
    $template = $templates[$template_name];
    
    // Thay thế variables
    foreach ($variables as $key => $value) {
        $template = str_replace('{' . $key . '}', $value, $template);
    }
    
    return $template;
}

/**
 * Lấy base URL
 */
function getBaseUrl() {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    return $protocol . '://' . $host;
}

/**
 * Validate session token
 */
function validateSession($token) {
    global $pdo;
    
    if (empty($token)) {
        return false;
    }
    
    try {
        $stmt = $pdo->prepare("
            SELECT s.*, u.id as user_id, u.name, u.email, u.status 
            FROM user_sessions s
            JOIN users u ON s.user_id = u.id
            WHERE s.session_token = ? AND s.is_active = 1 AND s.expires_at > NOW() AND u.status = 'active'
        ");
        $stmt->execute([$token]);
        $session = $stmt->fetch();
        
        if ($session) {
            // Cập nhật last activity
            $stmt = $pdo->prepare("UPDATE user_sessions SET last_activity = NOW() WHERE id = ?");
            $stmt->execute([$session['id']]);
            
            return $session;
        }
        
        return false;
    } catch (Exception $e) {
        error_log("Error validating session: " . $e->getMessage());
        return false;
    }
}

/**
 * Require authentication
 */
function requireAuth() {
    $token = null;
    
    // Lấy token từ header Authorization
    $headers = getallheaders();
    if (isset($headers['Authorization'])) {
        $auth_header = $headers['Authorization'];
        if (preg_match('/Bearer\s+(.*)$/i', $auth_header, $matches)) {
            $token = $matches[1];
        }
    }
    
    // Lấy token từ POST data
    if (!$token) {
        $input = json_decode(file_get_contents('php://input'), true);
        $token = $input['token'] ?? $_POST['token'] ?? $_GET['token'] ?? null;
    }
    
    $session = validateSession($token);
    if (!$session) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'Phiên đăng nhập không hợp lệ hoặc đã hết hạn'
        ]);
        exit;
    }
    
    return $session;
}

/**
 * Format tiền tệ VND
 */
function formatCurrency($amount, $currency = 'VND') {
    if ($currency === 'VND') {
        return number_format($amount, 0, ',', '.') . ' ₫';
    } else {
        return number_format($amount, 2, '.', ',') . ' ' . $currency;
    }
}

/**
 * Chuyển đổi tiền tệ
 */
function convertCurrency($amount, $from_currency, $to_currency) {
    if ($from_currency === $to_currency) {
        return $amount;
    }
    
    $exchange_rate = (float)getSetting('currency_exchange_rate', 24000);
    
    if ($from_currency === 'USD' && $to_currency === 'VND') {
        return $amount * $exchange_rate;
    } elseif ($from_currency === 'VND' && $to_currency === 'USD') {
        return $amount / $exchange_rate;
    }
    
    return $amount;
}

/**
 * Tạo số đơn hàng unique
 */
function generateOrderNumber() {
    return 'PV' . date('ymd') . '-' . strtoupper(substr(uniqid(), -6));
}

/**
 * Tạo transaction ID unique
 */
function generateTransactionId() {
    return 'TXN' . date('ymdHis') . '-' . strtoupper(substr(uniqid(), -4));
}

/**
 * Call API proxy service
 */
function callProxyAPI($method, $params = []) {
    $url = API_PROXY_BASE . '/' . API_PROXY_KEY . '/' . $method . '/';
    
    if (!empty($params)) {
        $url .= '?' . http_build_query($params);
    }
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_USERAGENT => 'ProxyPrivate.vn/1.0'
    ]);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        throw new Exception('Lỗi kết nối API: ' . $error);
    }
    
    if ($http_code !== 200) {
        throw new Exception('API trả về lỗi: ' . $http_code);
    }
    
    $data = json_decode($response, true);
    if (!$data) {
        throw new Exception('Dữ liệu API không hợp lệ');
    }
    
    if ($data['status'] !== 'yes') {
        throw new Exception($data['error'] ?? 'Lỗi API không xác định');
    }
    
    return $data;
}

/**
 * Lấy thông tin quốc gia từ code
 */
function getCountryInfo($country_code) {
    $countries = [
        'vn' => ['name' => 'Việt Nam', 'flag' => '🇻🇳'],
        'us' => ['name' => 'Hoa Kỳ', 'flag' => '🇺🇸'],
        'sg' => ['name' => 'Singapore', 'flag' => '🇸🇬'],
        'jp' => ['name' => 'Nhật Bản', 'flag' => '🇯🇵'],
        'kr' => ['name' => 'Hàn Quốc', 'flag' => '🇰🇷'],
        'th' => ['name' => 'Thái Lan', 'flag' => '🇹🇭'],
        'cn' => ['name' => 'Trung Quốc', 'flag' => '🇨🇳'],
        'hk' => ['name' => 'Hồng Kông', 'flag' => '🇭🇰'],
        'tw' => ['name' => 'Đài Loan', 'flag' => '🇹🇼'],
        'my' => ['name' => 'Malaysia', 'flag' => '🇲🇾'],
        'id' => ['name' => 'Indonesia', 'flag' => '🇮🇩'],
        'ph' => ['name' => 'Philippines', 'flag' => '🇵🇭'],
        'in' => ['name' => 'Ấn Độ', 'flag' => '🇮🇳'],
        'ru' => ['name' => 'Nga', 'flag' => '🇷🇺'],
        'de' => ['name' => 'Đức', 'flag' => '🇩🇪'],
        'fr' => ['name' => 'Pháp', 'flag' => '🇫🇷'],
        'gb' => ['name' => 'Vương quốc Anh', 'flag' => '🇬🇧'],
        'ca' => ['name' => 'Canada', 'flag' => '🇨🇦'],
        'au' => ['name' => 'Úc', 'flag' => '🇦🇺'],
        'br' => ['name' => 'Brazil', 'flag' => '🇧🇷']
    ];
    
    return $countries[strtolower($country_code)] ?? ['name' => strtoupper($country_code), 'flag' => '🏳️'];
}

/**
 * Sanitize input
 */
function sanitizeInput($input, $type = 'string') {
    switch ($type) {
        case 'email':
            return filter_var(trim($input), FILTER_SANITIZE_EMAIL);
        case 'int':
            return (int)$input;
        case 'float':
            return (float)$input;
        case 'bool':
            return (bool)$input;
        case 'url':
            return filter_var(trim($input), FILTER_SANITIZE_URL);
        default:
            return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }
}

/**
 * Validate input
 */
function validateInput($input, $rules) {
    $errors = [];
    
    foreach ($rules as $field => $rule_set) {
        $value = $input[$field] ?? null;
        
        foreach ($rule_set as $rule => $params) {
            switch ($rule) {
                case 'required':
                    if (empty($value)) {
                        $errors[$field] = ucfirst($field) . ' không được để trống';
                    }
                    break;
                    
                case 'min_length':
                    if (strlen($value) < $params) {
                        $errors[$field] = ucfirst($field) . ' phải có ít nhất ' . $params . ' ký tự';
                    }
                    break;
                    
                case 'max_length':
                    if (strlen($value) > $params) {
                        $errors[$field] = ucfirst($field) . ' không được quá ' . $params . ' ký tự';
                    }
                    break;
                    
                case 'email':
                    if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                        $errors[$field] = ucfirst($field) . ' không đúng định dạng';
                    }
                    break;
                    
                case 'numeric':
                    if (!is_numeric($value)) {
                        $errors[$field] = ucfirst($field) . ' phải là số';
                    }
                    break;
                    
                case 'min_value':
                    if ((float)$value < $params) {
                        $errors[$field] = ucfirst($field) . ' phải lớn hơn hoặc bằng ' . $params;
                    }
                    break;
                    
                case 'max_value':
                    if ((float)$value > $params) {
                        $errors[$field] = ucfirst($field) . ' phải nhỏ hơn hoặc bằng ' . $params;
                    }
                    break;
            }
            
            // Dừng validate field này nếu đã có lỗi
            if (isset($errors[$field])) {
                break;
            }
        }
    }
    
    return $errors;
}

/**
 * Response helper
 */
function jsonResponse($data, $status_code = 200) {
    http_response_code($status_code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Error response helper
 */
function errorResponse($message, $status_code = 400, $details = null) {
    $response = [
        'success' => false,
        'message' => $message
    ];
    
    if ($details) {
        $response['details'] = $details;
    }
    
    jsonResponse($response, $status_code);
}

/**
 * Success response helper
 */
function successResponse($message, $data = null) {
    $response = [
        'success' => true,
        'message' => $message
    ];
    
    if ($data) {
        $response['data'] = $data;
    }
    
    jsonResponse($response);
}

/**
 * Pagination helper
 */
function paginate($query, $params, $page = 1, $per_page = 20) {
    global $pdo;
    
    $offset = ($page - 1) * $per_page;
    
    // Count total records
    $count_query = preg_replace('/SELECT.*?FROM/i', 'SELECT COUNT(*) as total FROM', $query);
    $stmt = $pdo->prepare($count_query);
    $stmt->execute($params);
    $total = $stmt->fetch()['total'];
    
    // Get paginated results
    $paginated_query = $query . " LIMIT {$offset}, {$per_page}";
    $stmt = $pdo->prepare($paginated_query);
    $stmt->execute($params);
    $data = $stmt->fetchAll();
    
    return [
        'data' => $data,
        'pagination' => [
            'current_page' => $page,
            'per_page' => $per_page,
            'total' => $total,
            'total_pages' => ceil($total / $per_page),
            'has_more' => ($page * $per_page) < $total
        ]
    ];
}

/**
 * Upload file helper
 */
function uploadFile($file, $allowed_types = ['jpg', 'jpeg', 'png', 'gif'], $max_size = 5242880) { // 5MB
    if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        throw new Exception('File không hợp lệ');
    }
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Lỗi upload file');
    }
    
    if ($file['size'] > $max_size) {
        throw new Exception('File quá lớn. Tối đa ' . ($max_size / 1024 / 1024) . 'MB');
    }
    
    $file_info = pathinfo($file['name']);
    $extension = strtolower($file_info['extension'] ?? '');
    
    if (!in_array($extension, $allowed_types)) {
        throw new Exception('Loại file không được hỗ trợ. Chỉ chấp nhận: ' . implode(', ', $allowed_types));
    }
    
    // Tạo tên file unique
    $filename = uniqid() . '_' . time() . '.' . $extension;
    $upload_dir = '../uploads/' . date('Y/m/');
    
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    $file_path = $upload_dir . $filename;
    
    if (!move_uploaded_file($file['tmp_name'], $file_path)) {
        throw new Exception('Không thể lưu file');
    }
    
    return str_replace('../', '', $file_path);
}

/**
 * Generate random string
 */
function generateRandomString($length = 10, $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ') {
    $string = '';
    for ($i = 0; $i < $length; $i++) {
        $string .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $string;
}

/**
 * Verify reCAPTCHA
 */
function verifyRecaptcha($response) {
    $secret_key = getSetting('recaptcha_secret_key');
    if (empty($secret_key)) {
        return true; // Skip if not configured
    }
    
    $verify_url = 'https://www.google.com/recaptcha/api/siteverify';
    $data = [
        'secret' => $secret_key,
        'response' => $response,
        'remoteip' => getClientIP()
    ];
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $verify_url,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query($data),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10
    ]);
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    if (!$response) {
        return false;
    }
    
    $result = json_decode($response, true);
    return isset($result['success']) && $result['success'] === true;
}

/**
 * Format date Vietnamese
 */
function formatDateVN($date, $format = 'd/m/Y H:i') {
    if (is_string($date)) {
        $date = new DateTime($date);
    }
    return $date->format($format);
}

/**
 * Time ago function
 */
function timeAgo($datetime) {
    $time = time() - strtotime($datetime);
    
    if ($time < 60) {
        return 'vừa xong';
    } elseif ($time < 3600) {
        return floor($time / 60) . ' phút trước';
    } elseif ($time < 86400) {
        return floor($time / 3600) . ' giờ trước';
    } elseif ($time < 2592000) {
        return floor($time / 86400) . ' ngày trước';
    } elseif ($time < 31536000) {
        return floor($time / 2592000) . ' tháng trước';
    } else {
        return floor($time / 31536000) . ' năm trước';
    }
}
?>