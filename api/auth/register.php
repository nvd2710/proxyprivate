<?php
/**
 * API Đăng ký - ProxyPrivate.vn (Fixed - Không email verification)
 * File: api/auth/register.php
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
    
    $name = trim($input['name'] ?? '');
    $email = trim($input['email'] ?? '');
    $phone = trim($input['phone'] ?? '');
    $password = $input['password'] ?? '';
    
    // Validate dữ liệu đầu vào
    if (empty($name)) {
        throw new Exception('Họ và tên không được để trống');
    }
    
    if (strlen($name) < 2 || strlen($name) > 100) {
        throw new Exception('Họ và tên phải từ 2-100 ký tự');
    }
    
    if (empty($email)) {
        throw new Exception('Email không được để trống');
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Email không đúng định dạng');
    }
    
    if (empty($password)) {
        throw new Exception('Mật khẩu không được để trống');
    }
    
    if (strlen($password) < 6) {
        throw new Exception('Mật khẩu phải có ít nhất 6 ký tự');
    }
    
    // Validate số điện thoại nếu có
    if (!empty($phone)) {
        $phone = preg_replace('/[^0-9+]/', '', $phone);
        if (strlen($phone) < 10 || strlen($phone) > 15) {
            throw new Exception('Số điện thoại không hợp lệ');
        }
    }
    
    // Bắt đầu transaction
    $pdo->beginTransaction();
    
    try {
        // Kiểm tra email đã tồn tại chưa
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            throw new Exception('Email này đã được sử dụng');
        }
        
        // Kiểm tra số điện thoại đã tồn tại chưa (nếu có)
        if (!empty($phone)) {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE phone = ?");
            $stmt->execute([$phone]);
            if ($stmt->fetch()) {
                throw new Exception('Số điện thoại này đã được sử dụng');
            }
        }
        
        // Tạo API key unique
        do {
            $api_key = bin2hex(random_bytes(16)) . '-' . bin2hex(random_bytes(16)) . '-' . bin2hex(random_bytes(16));
            $stmt = $pdo->prepare("SELECT id FROM users WHERE api_key = ?");
            $stmt->execute([$api_key]);
        } while ($stmt->fetch());
        
        // Hash password
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        
        // Insert user mới - TRỰC TIẾP ACTIVE
        $stmt = $pdo->prepare("
            INSERT INTO users (name, email, phone, password_hash, api_key, status, email_verified_at, created_at)
            VALUES (?, ?, ?, ?, ?, 'active', NOW(), NOW())
        ");
        $stmt->execute([$name, $email, $phone ?: null, $password_hash, $api_key]);
        
        $user_id = $pdo->lastInsertId();
        
        // Tạo thông báo chào mừng
        $stmt = $pdo->prepare("
            INSERT INTO notifications (user_id, title, content, type)
            VALUES (?, ?, ?, 'info')
        ");
        $welcome_message = "Chào mừng bạn đến với ProxyPrivate.vn! Tài khoản của bạn đã được kích hoạt và sẵn sàng sử dụng.";
        $stmt->execute([$user_id, 'Chào mừng!', $welcome_message]);
        
        // Commit transaction
        $pdo->commit();
        
        // Tạo session token để auto login
        $session_token = bin2hex(random_bytes(32));
        $expires_at = date('Y-m-d H:i:s', strtotime('+2 hours'));
        
        $client_ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        // Lưu session vào database
        $stmt = $pdo->prepare("
            INSERT INTO user_sessions (user_id, session_token, ip_address, user_agent, expires_at)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$user_id, $session_token, $client_ip, $user_agent, $expires_at]);
        
        // Response thành công với auto login
        echo json_encode([
            'success' => true,
            'message' => 'Đăng ký thành công! Tài khoản đã được kích hoạt.',
            'user_id' => $user_id,
            'auto_login' => true,
            'token' => $session_token,
            'user' => [
                'id' => (int)$user_id,
                'name' => $name,
                'email' => $email,
                'phone' => $phone,
                'api_key' => $api_key,
                'balance' => 0.00,
                'currency' => 'VND',
                'status' => 'active'
            ]
        ]);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} catch (PDOException $e) {
    error_log("Database error in register: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Lỗi hệ thống. Vui lòng thử lại sau'
    ]);
}
?>