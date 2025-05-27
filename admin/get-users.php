<?php
/**
 * API lấy danh sách người dùng cho admin
 * File: api/admin/get-users.php
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
    
    // Lấy parameters
    $page = max(1, (int)($_GET['page'] ?? 1));
    $limit = min(100, max(10, (int)($_GET['limit'] ?? 20)));
    $search = trim($_GET['search'] ?? '');
    $status = $_GET['status'] ?? 'all';
    $sort = $_GET['sort'] ?? 'created_at';
    $order = ($_GET['order'] ?? 'desc') === 'asc' ? 'ASC' : 'DESC';
    
    $offset = ($page - 1) * $limit;
    
    // Build WHERE clause
    $where_conditions = ["u.id > 1"]; // Exclude admin
    $params = [];
    
    if (!empty($search)) {
        $where_conditions[] = "(u.name LIKE ? OR u.email LIKE ? OR u.phone LIKE ?)";
        $search_param = "%{$search}%";
        $params[] = $search_param;
        $params[] = $search_param;
        $params[] = $search_param;
    }
    
    if ($status !== 'all') {
        $where_conditions[] = "u.status = ?";
        $params[] = $status;
    }
    
    $where_clause = implode(' AND ', $where_conditions);
    
    // Validate sort column
    $allowed_sorts = ['id', 'name', 'email', 'status', 'balance', 'created_at', 'last_login_at'];
    if (!in_array($sort, $allowed_sorts)) {
        $sort = 'created_at';
    }
    
    // Get total count
    $count_query = "SELECT COUNT(*) as total FROM users u WHERE {$where_clause}";
    $stmt = $pdo->prepare($count_query);
    $stmt->execute($params);
    $total = (int)$stmt->fetch()['total'];
    
    // Get users data
    $query = "
        SELECT 
            u.id,
            u.name,
            u.email,
            u.phone,
            u.balance,
            u.currency,
            u.status,
            u.created_at,
            u.last_login_at,
            u.last_login_ip,
            COUNT(DISTINCT p.id) as total_proxies,
            COUNT(DISTINCT CASE WHEN p.status = 'active' THEN p.id END) as active_proxies,
            COUNT(DISTINCT o.id) as total_orders,
            COALESCE(SUM(CASE WHEN t.type = 'deposit' AND t.status = 'completed' THEN t.amount END), 0) as total_deposits
        FROM users u
        LEFT JOIN proxies p ON u.id = p.user_id
        LEFT JOIN orders o ON u.id = o.user_id  
        LEFT JOIN transactions t ON u.id = t.user_id
        WHERE {$where_clause}
        GROUP BY u.id
        ORDER BY u.{$sort} {$order}
        LIMIT {$offset}, {$limit}
    ";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $users = $stmt->fetchAll();
    
    // Format data
    $formatted_users = [];
    foreach ($users as $user) {
        $formatted_users[] = [
            'id' => (int)$user['id'],
            'name' => $user['name'],
            'email' => $user['email'],
            'phone' => $user['phone'],
            'balance' => (float)$user['balance'],
            'currency' => $user['currency'],
            'status' => $user['status'],
            'total_proxies' => (int)$user['total_proxies'],
            'active_proxies' => (int)$user['active_proxies'],
            'total_orders' => (int)$user['total_orders'],
            'total_deposits' => (float)$user['total_deposits'],
            'created_at' => $user['created_at'],
            'last_login_at' => $user['last_login_at'],
            'last_login_ip' => $user['last_login_ip'],
            'formatted_balance' => formatCurrency($user['balance'], $user['currency']),
            'formatted_created_at' => formatDateVN($user['created_at']),
            'formatted_last_login' => $user['last_login_at'] ? formatDateVN($user['last_login_at']) : 'Chưa đăng nhập'
        ];
    }
    
    // Pagination info
    $total_pages = ceil($total / $limit);
    
    echo json_encode([
        'success' => true,
        'data' => $formatted_users,
        'pagination' => [
            'current_page' => $page,
            'per_page' => $limit,
            'total' => $total,
            'total_pages' => $total_pages,
            'has_prev' => $page > 1,
            'has_next' => $page < $total_pages
        ],
        'filters' => [
            'search' => $search,
            'status' => $status,
            'sort' => $sort,
            'order' => $order
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} catch (PDOException $e) {
    error_log("Database error in get-users: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Lỗi cơ sở dữ liệu'
    ]);
}

// Helper functions
function formatCurrency($amount, $currency = 'VND') {
    if ($currency === 'VND') {
        return number_format($amount, 0, ',', '.') . ' ₫';
    }
    return number_format($amount, 2) . ' ' . $currency;
}

function formatDateVN($date) {
    if (!$date) return '';
    return date('d/m/Y H:i', strtotime($date));
}
?>