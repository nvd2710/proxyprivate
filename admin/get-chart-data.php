<?php
/**
 * API lấy dữ liệu biểu đồ cho admin
 * File: api/admin/get-chart-data.php
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

require_once '../config/database.php';
require_once '../config/functions.php';

try {
    // Kiểm tra admin session
    session_start();
    if (!isset($_SESSION['admin_id'])) {
        throw new Exception('Unauthorized');
    }
    
    $type = $_GET['type'] ?? 'revenue';
    $days = (int)($_GET['days'] ?? 30);
    
    $chart_data = [];
    
    if ($type === 'revenue') {
        // Doanh thu theo ngày
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-{$i} days"));
            
            $stmt = $pdo->prepare("
                SELECT COALESCE(SUM(final_amount), 0) as revenue 
                FROM orders 
                WHERE status = 'completed' AND DATE(created_at) = ?
            ");
            $stmt->execute([$date]);
            $revenue = $stmt->fetch()['revenue'];
            
            $chart_data[] = [
                'date' => date('d/m', strtotime($date)),
                'value' => (float)$revenue,
                'label' => formatCurrency($revenue)
            ];
        }
        
    } elseif ($type === 'orders') {
        // Đơn hàng theo ngày
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-{$i} days"));
            
            $stmt = $pdo->prepare("
                SELECT COUNT(*) as orders 
                FROM orders 
                WHERE DATE(created_at) = ?
            ");
            $stmt->execute([$date]);
            $orders = $stmt->fetch()['orders'];
            
            $chart_data[] = [
                'date' => date('d/m', strtotime($date)),
                'value' => (int)$orders,
                'label' => $orders . ' đơn'
            ];
        }
        
    } elseif ($type === 'users') {
        // Người dùng mới theo ngày
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-{$i} days"));
            
            $stmt = $pdo->prepare("
                SELECT COUNT(*) as users 
                FROM users 
                WHERE DATE(created_at) = ? AND id > 1
            ");
            $stmt->execute([$date]);
            $users = $stmt->fetch()['users'];
            
            $chart_data[] = [
                'date' => date('d/m', strtotime($date)),
                'value' => (int)$users,
                'label' => $users . ' users'
            ];
        }
    }
    
    echo json_encode([
        'success' => true,
        'type' => $type,
        'days' => $days,
        'data' => $chart_data
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

// Helper function nếu chưa có
if (!function_exists('formatCurrency')) {
    function formatCurrency($amount, $currency = 'VND') {
        if ($currency === 'VND') {
            return number_format($amount, 0, ',', '.') . ' ₫';
        }
        return number_format($amount, 2) . ' ' . $currency;
    }
}
?>