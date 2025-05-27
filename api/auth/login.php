<?php
/**
 * API Đăng nhập - ProxyPrivate.vn
 * File: api/auth/login.php
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Chỉ cho phép POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Phương thức không được hỗ trợ'
    ]);
    exit;
}

require_once '../config/database.php';
require_once '../config/functions.php';

try {
    // Lấy dữ liệu JSON từ request
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Dữ liệu đầu vào không hợp lệ');
    }
    
    $email = trim($input['email'] ?? '');
    $password = $input['password'] ?? '';
    $remember = isset($input['remember']) ? (bool)$input['remember'] : false;
    
    // Validate dữ liệu đầu vào
    if (empty($email)) {
        throw new Exception('Email không được để trống');
    }
    
    if (empty($password)) {
        throw new Exception('Mật khẩu không được để trống');
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Email không đúng định dạng');
    }
    
    // Kiểm tra rate limiting (tối đa 5 lần trong 15 phút)
    $client_ip = getClientIP();
    $rate_limit_key = "login_attempts_{$client_ip}";
    
    if (isRateLimited($rate_limit_key, 5, 900)) { // 5 attempts in 15 minutes
        throw new Exception('Quá nhiều lần đăng nhập thất bại. Vui lòng thử lại sau 15 phút');
    }
    
    // Tìm user trong database
    $stmt = $pdo->prepare("
        SELECT id, name, email, password_hash, api_key, balance, currency, status, 
               email_verified_at, last_login_at, phone, avatar 
        FROM users 
        WHERE email = ? AND status != 'banned'
    ");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        // Tăng counter rate limiting
        incrementRateLimit($rate_limit_key, 900);
        throw new Exception('Email hoặc mật khẩu không chính xác');
    }
    
    // Kiểm tra mật khẩu
    if (!password_verify($password, $user['password_hash'])) {
        // Tăng counter rate limiting
        incrementRateLimit($rate_limit_key, 900);
        
        // Log failed login attempt
        logActivity($user['id'], 'login_failed', 'user', $user['id'], 
                   'Đăng nhập thất bại từ IP: ' . $client_ip);
        
        throw new Exception('Email hoặc mật khẩu không chính xác');
    }
    
    // Kiểm tra trạng thái tài khoản
    if ($user['status'] === 'inactive') {
        throw new Exception('Tài khoản chưa được kích hoạt. Vui lòng kiểm tra email để kích hoạt');
    }
    
    if ($user['status'] === 'banned') {
        throw new Exception('Tài khoản đã bị khóa. Vui lòng liên hệ hỗ trợ');
    }
    
    // Tạo session token
    $session_token = generateSecureToken(64);
    $expires_at = $remember ? 
        date('Y-m-d H:i:s', strtotime('+30 days')) : 
        date('Y-m-d H:i:s', strtotime('+2 hours'));
    
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $device_info = getDeviceInfo($user_agent);
    
    // Lưu session vào database
    $stmt = $pdo->prepare("
        INSERT INTO user_sessions (user_id, session_token, device_info, ip_address, user_agent, expires_at)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $user['id'], 
        $session_token, 
        $device_info, 
        $client_ip, 
        $user_agent, 
        $expires_at
    ]);
    
    // Cập nhật last_login
    $stmt = $pdo->prepare("
        UPDATE users 
        SET last_login_at = NOW(), last_login_ip = ? 
        WHERE id = ?
    ");
    $stmt->execute([$client_ip, $user['id']]);
    
    // Xóa các session cũ của user (chỉ giữ lại 5 session gần nhất)
    $stmt = $pdo->prepare("
        DELETE FROM user_sessions 
        WHERE user_id = ? AND id NOT IN (
            SELECT id FROM (
                SELECT id FROM user_sessions 
                WHERE user_id = ? 
                ORDER BY created_at DESC 
                LIMIT 5
            ) as recent_sessions
        )
    ");
    $stmt->execute([$user['id'], $user['id']]);
    
    // Reset rate limiting khi đăng nhập thành công
    resetRateLimit($rate_limit_key);
    
    // Log successful login
    logActivity($user['id'], 'login_success', 'user', $user['id'], 
               'Đăng nhập thành công từ IP: ' . $client_ip . ' - Device: ' . $device_info);
    
    // Chuẩn bị dữ liệu response
    $response_data = [
        'success' => true,
        'message' => 'Đăng nhập thành công',
        'token' => $session_token,
        'user' => [
            'id' => (int)$user['id'],
            'name' => $user['name'],
            'email' => $user['email'],
            'phone' => $user['phone'],
            'avatar' => $user['avatar'],
            'balance' => (float)$user['balance'],
            'currency' => $user['currency'],
            'api_key' => $user['api_key'],
            'email_verified' => !is_null($user['email_verified_at']),
            'last_login' => $user['last_login_at']
        ],
        'expires_at' => $expires_at
    ];
    
    echo json_encode($response_data);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} catch (PDOException $e) {
    error_log("Database error in login: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Lỗi hệ thống. Vui lòng thử lại sau'
    ]);
}
?>