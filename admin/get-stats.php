<?php
/**
 * API lấy thống kê cho admin
 * File: api/admin/get-stats.php
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

require_once '../config/database.php';
require_once '../config/functions.php';

try {
    // Kiểm tra admin session (đơn giản)
    session_start();
    if (!isset($_SESSION['admin_id'])) {
        throw new Exception('Unauthorized');
    }
    
    // Lấy thống kê
    $stats = [];
    
    // Tổng người dùng (trừ admin)
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM users WHERE id > 1");
    $stats['total_users'] = (int)$stmt->fetch()['total'];
    
    // Người dùng mới hôm nay
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM users WHERE DATE(created_at) = CURDATE() AND id > 1");
    $stats['new_users_today'] = (int)$stmt->fetch()['total'];
    
    // Tổng proxy đang hoạt động
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM proxies WHERE status = 'active'");
    $stats['active_proxies'] = (int)$stmt->fetch()['total'];
    
    // Tổng đơn hàng hoàn thành
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM orders WHERE status = 'completed'");
    $stats['total_orders'] = (int)$stmt->fetch()['total'];
    
    // Doanh thu tháng này
    $stmt = $pdo->query("
        SELECT COALESCE(SUM(final_amount), 0) as revenue 
        FROM orders 
        WHERE status = 'completed' 
        AND YEAR(created_at) = YEAR(NOW()) 
        AND MONTH(created_at) = MONTH(NOW())
    ");
    $stats['monthly_revenue'] = (float)$stmt->fetch()['revenue'];
    
    // Đơn hàng hôm nay
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM orders WHERE DATE(created_at) = CURDATE()");
    $stats['orders_today'] = (int)$stmt->fetch()['total'];
    
    // Doanh thu hôm nay
    $stmt = $pdo->query("
        SELECT COALESCE(SUM(final_amount), 0) as revenue 
        FROM orders 
        WHERE status = 'completed' AND DATE(created_at) = CURDATE()
    ");
    $stats['revenue_today'] = (float)$stmt->fetch()['revenue'];
    
    // Proxy sắp hết hạn (7 ngày tới)
    $stmt = $pdo->query("
        SELECT COUNT(*) as total 
        FROM proxies 
        WHERE status = 'active' 
        AND expires_at BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 7 DAY)
    ");
    $stats['expiring_proxies'] = (int)$stmt->fetch()['total'];
    
    // Response thành công
    echo json_encode([
        'success' => true,
        'stats' => $stats,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} catch (PDOException $e) {
    error_log("Database error in get-stats: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Lỗi cơ sở dữ liệu'
    ]);
}
?>