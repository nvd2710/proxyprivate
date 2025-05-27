<?php
/**
 * Cấu hình Database - ProxyPrivate.vn
 * File: api/config/database.php
 */

// Cấu hình database
define('DB_HOST', 'localhost');
define('DB_NAME', 'vuminhti_proxyprivate_vn');  // ✅ Đúng tên database
define('DB_USER', 'vuminhti_proxy_user');       // ✅ Đúng username  
define('DB_PASS', 'Anhtjen2710@');              // ✅ Đúng password
define('DB_CHARSET', 'utf8mb4');

// Cấu hình timezone
date_default_timezone_set('Asia/Ho_Chi_Minh');

try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . DB_CHARSET,
    ];
    
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    
} catch (PDOException $e) {
    error_log("Database connection failed: " . $e->getMessage());
    
    if (!headers_sent()) {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code(500);
    }
    
    echo json_encode([
        'success' => false,
        'message' => 'Không thể kết nối đến cơ sở dữ liệu'
    ]);
    exit;
}

// Helper function để thực hiện query an toàn
function executeQuery($query, $params = []) {
    global $pdo;
    try {
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        return $stmt;
    } catch (PDOException $e) {
        error_log("Query error: " . $e->getMessage() . " | Query: " . $query);
        throw new Exception('Lỗi truy vấn cơ sở dữ liệu');
    }
}

// Helper function để lấy một bản ghi
function fetchOne($query, $params = []) {
    $stmt = executeQuery($query, $params);
    return $stmt->fetch();
}

// Helper function để lấy nhiều bản ghi
function fetchAll($query, $params = []) {
    $stmt = executeQuery($query, $params);
    return $stmt->fetchAll();
}

// Helper function để thực hiện insert và trả về ID
function insertAndGetId($query, $params = []) {
    global $pdo;
    executeQuery($query, $params);
    return $pdo->lastInsertId();
}
?>