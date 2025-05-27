<?php
/**
 * Admin Panel - ProxyPrivate.vn
 * File: admin/index.php
 */

session_start();
require_once '../api/config/database.php';
require_once '../api/config/functions.php';

// Kiểm tra đăng nhập admin
if (!isset($_SESSION['admin_id'])) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';
        
        // Kiểm tra admin login
        $stmt = $pdo->prepare("
            SELECT id, name, email, password_hash 
            FROM users 
            WHERE email = ? AND id = 1
        "); // Admin luôn có ID = 1
        $stmt->execute([$username]);
        $admin = $stmt->fetch();
        
        if ($admin && password_verify($password, $admin['password_hash'])) {
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_name'] = $admin['name'];
            $_SESSION['admin_email'] = $admin['email'];
            header('Location: index.php');
            exit;
        } else {
            $login_error = 'Tên đăng nhập hoặc mật khẩu không chính xác';
        }
    }
    
    // Hiển thị form đăng nhập
    include 'login.php';
    exit;
}

// Lấy thống kê dashboard
$stats = [];

// Tổng người dùng
$stmt = $pdo->query("SELECT COUNT(*) as total FROM users WHERE id > 1");
$stats['total_users'] = $stmt->fetch()['total'];

// Người dùng mới hôm nay
$stmt = $pdo->query("SELECT COUNT(*) as total FROM users WHERE DATE(created_at) = CURDATE() AND id > 1");
$stats['new_users_today'] = $stmt->fetch()['total'];

// Tổng proxy đang hoạt động
$stmt = $pdo->query("SELECT COUNT(*) as total FROM proxies WHERE status = 'active'");
$stats['active_proxies'] = $stmt->fetch()['total'];

// Tổng đơn hàng
$stmt = $pdo->query("SELECT COUNT(*) as total FROM orders WHERE status = 'completed'");
$stats['total_orders'] = $stmt->fetch()['total'];

// Doanh thu tháng này
$stmt = $pdo->query("
    SELECT COALESCE(SUM(final_amount), 0) as revenue 
    FROM orders 
    WHERE status = 'completed' AND YEAR(created_at) = YEAR(NOW()) AND MONTH(created_at) = MONTH(NOW())
");
$stats['monthly_revenue'] = $stmt->fetch()['revenue'];

// Đơn hàng hôm nay
$stmt = $pdo->query("SELECT COUNT(*) as total FROM orders WHERE DATE(created_at) = CURDATE()");
$stats['orders_today'] = $stmt->fetch()['total'];

// Doanh thu hôm nay
$stmt = $pdo->query("
    SELECT COALESCE(SUM(final_amount), 0) as revenue 
    FROM orders 
    WHERE status = 'completed' AND DATE(created_at) = CURDATE()
");
$stats['revenue_today'] = $stmt->fetch()['revenue'];

// Proxy sắp hết hạn (trong 7 ngày)
$stmt = $pdo->query("
    SELECT COUNT(*) as total 
    FROM proxies 
    WHERE status = 'active' AND expires_at BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 7 DAY)
");
$stats['expiring_proxies'] = $stmt->fetch()['total'];

// Lấy page hiện tại
$page = $_GET['page'] ?? 'dashboard';
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - ProxyPrivate.vn</title>
    <link rel="stylesheet" href="assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="admin-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <h2>🛡️ Admin Panel</h2>
                <p>ProxyPrivate.vn</p>
            </div>
            
            <nav class="sidebar-nav">
                <a href="?page=dashboard" class="nav-item <?= $page === 'dashboard' ? 'active' : '' ?>">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
                
                <a href="?page=users" class="nav-item <?= $page === 'users' ? 'active' : '' ?>">
                    <i class="fas fa-users"></i>
                    <span>Quản lý người dùng</span>
                </a>
                
                <a href="?page=proxies" class="nav-item <?= $page === 'proxies' ? 'active' : '' ?>">
                    <i class="fas fa-server"></i>
                    <span>Quản lý Proxy</span>
                </a>
                
                <a href="?page=orders" class="nav-item <?= $page === 'orders' ? 'active' : '' ?>">
                    <i class="fas fa-shopping-cart"></i>
                    <span>Đơn hàng</span>
                </a>
                
                <a href="?page=transactions" class="nav-item <?= $page === 'transactions' ? 'active' : '' ?>">
                    <i class="fas fa-credit-card"></i>
                    <span>Giao dịch</span>
                </a>
                
                <a href="?page=settings" class="nav-item <?= $page === 'settings' ? 'active' : '' ?>">
                    <i class="fas fa-cog"></i>
                    <span>Cấu hình</span>
                </a>
                
                <a href="?page=logs" class="nav-item <?= $page === 'logs' ? 'active' : '' ?>">
                    <i class="fas fa-list-alt"></i>
                    <span>Nhật ký hoạt động</span>
                </a>
                
                <a href="?page=statistics" class="nav-item <?= $page === 'statistics' ? 'active' : '' ?>">
                    <i class="fas fa-chart-bar"></i>
                    <span>Thống kê</span>
                </a>
            </nav>
            
            <div class="sidebar-footer">
                <div class="admin-info">
                    <div class="admin-avatar">
                        <?= substr($_SESSION['admin_name'], 0, 1) ?>
                    </div>
                    <div class="admin-details">
                        <div class="admin-name"><?= htmlspecialchars($_SESSION['admin_name']) ?></div>
                        <div class="admin-role">Quản trị viên</div>
                    </div>
                </div>
                <a href="logout.php" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i>
                    Đăng xuất
                </a>
            </div>
        </aside>
        
        <!-- Main Content -->
        <main class="main-content">
            <header class="main-header">
                <div class="header-left">
                    <button class="sidebar-toggle" onclick="toggleSidebar()">
                        <i class="fas fa-bars"></i>
                    </button>
                    <h1 class="page-title">
                        <?php
                        $titles = [
                            'dashboard' => 'Dashboard',
                            'users' => 'Quản lý người dùng',
                            'proxies' => 'Quản lý Proxy',
                            'orders' => 'Đơn hàng',
                            'transactions' => 'Giao dịch',
                            'settings' => 'Cấu hình hệ thống',
                            'logs' => 'Nhật ký hoạt động',
                            'statistics' => 'Thống kê'
                        ];
                        echo $titles[$page] ?? 'Dashboard';
                        ?>
                    </h1>
                </div>
                
                <div class="header-right">
                    <div class="header-stats">
                        <div class="stat-item">
                            <span class="stat-label">Online:</span>
                            <span class="stat-value"><?= $stats['total_users'] ?></span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-label">Hôm nay:</span>
                            <span class="stat-value"><?= formatCurrency($stats['revenue_today']) ?></span>
                        </div>
                    </div>
                    
                    <div class="current-time">
                        <i class="fas fa-clock"></i>
                        <span id="current-time"><?= date('H:i:s d/m/Y') ?></span>
                    </div>
                </div>
            </header>
            
            <div class="content-area">
                <?php
                // Include page content
                switch ($page) {
                    case 'dashboard':
                        include 'pages/dashboard.php';
                        break;
                    case 'users':
                        include 'pages/users.php';
                        break;
                    case 'proxies':
                        include 'pages/proxies.php';
                        break;
                    case 'orders':
                        include 'pages/orders.php';
                        break;
                    case 'transactions':
                        include 'pages/transactions.php';
                        break;
                    case 'settings':
                        include 'pages/settings.php';
                        break;
                    case 'logs':
                        include 'pages/logs.php';
                        break;
                    case 'statistics':
                        include 'pages/statistics.php';
                        break;
                    default:
                        include 'pages/dashboard.php';
                }
                ?>
            </div>
        </main>
    </div>
    
    <!-- Toast Messages -->
    <div id="toast-container"></div>
    
    <script src="assets/js/admin.js"></script>
</body>
</html>