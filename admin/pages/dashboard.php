<?php
/**
 * Admin Dashboard Page
 * File: admin/pages/dashboard.php
 */

// Lấy dữ liệu cho biểu đồ
$chart_data = [];

// Doanh thu 30 ngày qua
for ($i = 29; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-{$i} days"));
    $stmt = $pdo->prepare("
        SELECT COALESCE(SUM(final_amount), 0) as revenue 
        FROM orders 
        WHERE status = 'completed' AND DATE(created_at) = ?
    ");
    $stmt->execute([$date]);
    $revenue = $stmt->fetch()['revenue'];
    
    $chart_data['revenue'][] = [
        'date' => date('d/m', strtotime($date)),
        'value' => (float)$revenue
    ];
}

// Đơn hàng 30 ngày qua
for ($i = 29; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-{$i} days"));
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as orders 
        FROM orders 
        WHERE DATE(created_at) = ?
    ");
    $stmt->execute([$date]);
    $orders = $stmt->fetch()['orders'];
    
    $chart_data['orders'][] = [
        'date' => date('d/m', strtotime($date)),
        'value' => (int)$orders
    ];
}

// Lấy đơn hàng gần đây
$recent_orders = fetchAll("
    SELECT o.*, u.name as user_name, u.email as user_email
    FROM orders o
    JOIN users u ON o.user_id = u.id
    ORDER BY o.created_at DESC
    LIMIT 10
");

// Lấy người dùng mới
$new_users = fetchAll("
    SELECT id, name, email, created_at, status
    FROM users
    WHERE id > 1
    ORDER BY created_at DESC
    LIMIT 10
");

// Lấy proxy sắp hết hạn
$expiring_proxies = fetchAll("
    SELECT p.*, u.name as user_name, u.email as user_email
    FROM proxies p
    JOIN users u ON p.user_id = u.id
    WHERE p.status = 'active' AND p.expires_at BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 7 DAY)
    ORDER BY p.expires_at ASC
    LIMIT 10
");

// Tính toán thay đổi so với tháng trước
$last_month_revenue = fetchOne("
    SELECT COALESCE(SUM(final_amount), 0) as revenue 
    FROM orders 
    WHERE status = 'completed' 
    AND YEAR(created_at) = YEAR(DATE_SUB(NOW(), INTERVAL 1 MONTH))
    AND MONTH(created_at) = MONTH(DATE_SUB(NOW(), INTERVAL 1 MONTH))
")['revenue'];

$revenue_change = $last_month_revenue > 0 ? 
    (($stats['monthly_revenue'] - $last_month_revenue) / $last_month_revenue) * 100 : 0;

$last_month_orders = fetchOne("
    SELECT COUNT(*) as orders 
    FROM orders 
    WHERE YEAR(created_at) = YEAR(DATE_SUB(NOW(), INTERVAL 1 MONTH))
    AND MONTH(created_at) = MONTH(DATE_SUB(NOW(), INTERVAL 1 MONTH))
")['orders'];

$orders_change = $last_month_orders > 0 ? 
    (($stats['total_orders'] - $last_month_orders) / $last_month_orders) * 100 : 0;
?>

<!-- Dashboard Cards -->
<div class="dashboard-cards">
    <div class="dashboard-card">
        <div class="card-header">
            <div class="card-title">Tổng người dùng</div>
            <div class="card-icon users">
                <i class="fas fa-users"></i>
            </div>
        </div>
        <div class="card-value"><?= number_format($stats['total_users']) ?></div>
        <div class="card-change positive">
            <i class="fas fa-arrow-up"></i>
            +<?= $stats['new_users_today'] ?> hôm nay
        </div>
    </div>
    
    <div class="dashboard-card">
        <div class="card-header">
            <div class="card-title">Proxy hoạt động</div>
            <div class="card-icon proxies">
                <i class="fas fa-server"></i>
            </div>
        </div>
        <div class="card-value"><?= number_format($stats['active_proxies']) ?></div>
        <div class="card-change <?= $stats['expiring_proxies'] > 0 ? 'negative' : 'positive' ?>">
            <i class="fas fa-exclamation-triangle"></i>
            <?= $stats['expiring_proxies'] ?> sắp hết hạn
        </div>
    </div>
    
    <div class="dashboard-card">
        <div class="card-header">
            <div class="card-title">Đơn hàng</div>
            <div class="card-icon orders">
                <i class="fas fa-shopping-cart"></i>
            </div>
        </div>
        <div class="card-value"><?= number_format($stats['total_orders']) ?></div>
        <div class="card-change <?= $orders_change >= 0 ? 'positive' : 'negative' ?>">
            <i class="fas fa-arrow-<?= $orders_change >= 0 ? 'up' : 'down' ?>"></i>
            <?= $orders_change >= 0 ? '+' : '' ?><?= number_format($orders_change, 1) ?>% so với tháng trước
        </div>
    </div>
    
    <div class="dashboard-card">
        <div class="card-header">
            <div class="card-title">Doanh thu tháng</div>
            <div class="card-icon revenue">
                <i class="fas fa-chart-line"></i>
            </div>
        </div>
        <div class="card-value"><?= formatCurrency($stats['monthly_revenue']) ?></div>
        <div class="card-change <?= $revenue_change >= 0 ? 'positive' : 'negative' ?>">
            <i class="fas fa-arrow-<?= $revenue_change >= 0 ? 'up' : 'down' ?>"></i>
            <?= $revenue_change >= 0 ? '+' : '' ?><?= number_format($revenue_change, 1) ?>% so với tháng trước
        </div>
    </div>
</div>

<!-- Charts Row -->
<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 30px;">
    <div class="chart-container">
        <div class="chart-title">Doanh thu 30 ngày qua</div>
        <canvas id="revenueChart" width="400" height="200"></canvas>
    </div>
    
    <div class="chart-container">
        <div class="chart-title">Đơn hàng 30 ngày qua</div>
        <canvas id="ordersChart" width="400" height="200"></canvas>
    </div>
</div>

<!-- Tables Row -->
<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 30px;">
    <!-- Recent Orders -->
    <div class="data-table">
        <div class="table-header">
            <div class="table-title">Đơn hàng gần đây</div>
            <a href="?page=orders" class="btn btn-primary btn-sm">Xem tất cả</a>
        </div>
        <table>
            <thead>
                <tr>
                    <th>Mã đơn</th>
                    <th>Khách hàng</th>
                    <th>Số tiền</th>
                    <th>Trạng thái</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recent_orders as $order): ?>
                <tr>
                    <td><?= htmlspecialchars($order['order_number']) ?></td>
                    <td>
                        <div><?= htmlspecialchars($order['user_name']) ?></div>
                        <small style="color: #6c757d;"><?= htmlspecialchars($order['user_email']) ?></small>
                    </td>
                    <td><?= formatCurrency($order['final_amount']) ?></td>
                    <td>
                        <span class="status-badge status-<?= $order['status'] ?>">
                            <?php
                            $status_text = [
                                'pending' => 'Chờ xử lý',
                                'processing' => 'Đang xử lý',
                                'completed' => 'Hoàn thành',
                                'failed' => 'Thất bại',
                                'cancelled' => 'Đã hủy'
                            ];
                            echo $status_text[$order['status']] ?? $order['status'];
                            ?>
                        </span>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($recent_orders)): ?>
                <tr>
                    <td colspan="4" style="text-align: center; color: #6c757d; padding: 30px;">
                        Chưa có đơn hàng nào
                    </td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <!-- New Users -->
    <div class="data-table">
        <div class="table-header">
            <div class="table-title">Người dùng mới</div>
            <a href="?page=users" class="btn btn-primary btn-sm">Xem tất cả</a>
        </div>
        <table>
            <thead>
                <tr>
                    <th>Tên</th>
                    <th>Email</th>
                    <th>Ngày đăng ký</th>
                    <th>Trạng thái</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($new_users as $user): ?>
                <tr>
                    <td><?= htmlspecialchars($user['name']) ?></td>
                    <td><?= htmlspecialchars($user['email']) ?></td>
                    <td><?= formatDateVN($user['created_at'], 'd/m/Y') ?></td>
                    <td>
                        <span class="status-badge status-<?= $user['status'] ?>">
                            <?php
                            $status_text = [
                                'active' => 'Hoạt động',
                                'inactive' => 'Chưa kích hoạt',
                                'banned' => 'Bị khóa'
                            ];
                            echo $status_text[$user['status']] ?? $user['status'];
                            ?>
                        </span>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($new_users)): ?>
                <tr>
                    <td colspan="4" style="text-align: center; color: #6c757d; padding: 30px;">
                        Chưa có người dùng mới
                    </td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Expiring Proxies -->
<?php if (!empty($expiring_proxies)): ?>
<div class="data-table">
    <div class="table-header">
        <div class="table-title">Proxy sắp hết hạn (7 ngày tới)</div>
        <a href="?page=proxies&filter=expiring" class="btn btn-warning btn-sm">
            <i class="fas fa-exclamation-triangle"></i>
            Xem tất cả
        </a>
    </div>
    <table>
        <thead>
            <tr>
                <th>IP</th>
                <th>Khách hàng</th>
                <th>Quốc gia</th>
                <th>Loại</th>
                <th>Hết hạn</th>
                <th>Còn lại</th>
                <th>Thao tác</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($expiring_proxies as $proxy): ?>
            <?php
            $country_info = getCountryInfo($proxy['country_code']);
            $expires_timestamp = strtotime($proxy['expires_at']);
            $days_left = ceil(($expires_timestamp - time()) / 86400);
            ?>
            <tr>
                <td>
                    <code><?= htmlspecialchars($proxy['ip_address']) ?></code>
                    <br>
                    <small style="color: #6c757d;"><?= htmlspecialchars($proxy['host']) ?>:<?= $proxy['port'] ?></small>
                </td>
                <td>
                    <div><?= htmlspecialchars($proxy['user_name']) ?></div>
                    <small style="color: #6c757d;"><?= htmlspecialchars($proxy['user_email']) ?></small>
                </td>
                <td>
                    <?= $country_info['flag'] ?> <?= $country_info['name'] ?>
                </td>
                <td>
                    <span class="status-badge" style="background-color: #e9ecef; color: #495057;">
                        <?= strtoupper($proxy['type']) ?>
                    </span>
                </td>
                <td><?= formatDateVN($proxy['expires_at'], 'd/m/Y H:i') ?></td>
                <td>
                    <span class="<?= $days_left <= 2 ? 'text-danger' : ($days_left <= 5 ? 'text-warning' : 'text-success') ?>">
                        <?= $days_left ?> ngày
                    </span>
                </td>
                <td>
                    <button class="btn btn-success btn-sm" onclick="extendProxy(<?= $proxy['id'] ?>)">
                        <i class="fas fa-clock"></i>
                        Gia hạn
                    </button>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<!-- Quick Actions -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-top: 30px;">
    <button class="btn btn-primary" onclick="showQuickStats()">
        <i class="fas fa-chart-bar"></i>
        Thống kê nhanh
    </button>
    
    <button class="btn btn-success" onclick="exportData()">
        <i class="fas fa-download"></i>
        Xuất dữ liệu
    </button>
    
    <button class="btn btn-warning" onclick="sendNotification()">
        <i class="fas fa-bell"></i>
        Gửi thông báo
    </button>
    
    <button class="btn btn-danger" onclick="systemMaintenance()">
        <i class="fas fa-tools"></i>
        Bảo trì hệ thống
    </button>
</div>

<script>
// Dashboard JavaScript
document.addEventListener('DOMContentLoaded', function() {
    // Cập nhật thời gian hiện tại
    updateCurrentTime();
    setInterval(updateCurrentTime, 1000);
    
    // Tạo biểu đồ
    createRevenueChart();
    createOrdersChart();
    
    // Auto refresh mỗi 5 phút
    setInterval(() => {
        location.reload();
    }, 300000);
});

function updateCurrentTime() {
    const now = new Date();
    const timeString = now.toLocaleTimeString('vi-VN') + ' ' + now.toLocaleDateString('vi-VN');
    document.getElementById('current-time').textContent = timeString;
}

function createRevenueChart() {
    const ctx = document.getElementById('revenueChart').getContext('2d');
    const data = <?= json_encode($chart_data['revenue']) ?>;
    
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: data.map(item => item.date),
            datasets: [{
                label: 'Doanh thu (VND)',
                data: data.map(item => item.value),
                borderColor: '#667eea',
                backgroundColor: 'rgba(102, 126, 234, 0.1)',
                borderWidth: 2,
                fill: true,
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return new Intl.NumberFormat('vi-VN').format(value) + ' ₫';
                        }
                    }
                }
            }
        }
    });
}

function createOrdersChart() {
    const ctx = document.getElementById('ordersChart').getContext('2d');
    const data = <?= json_encode($chart_data['orders']) ?>;
    
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: data.map(item => item.date),
            datasets: [{
                label: 'Số đơn hàng',
                data: data.map(item => item.value),
                backgroundColor: 'rgba(244, 67, 54, 0.8)',
                borderColor: '#f44336',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
                    }
                }
            }
        }
    });
}

function extendProxy(proxyId) {
    const days = prompt('Nhập số ngày muốn gia hạn:', '30');
    if (!days || isNaN(days) || days <= 0) {
        return;
    }
    
    fetch('api/admin/extend-proxy.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            proxy_id: proxyId,
            days: parseInt(days)
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('success', 'Thành công', `Đã gia hạn proxy ${days} ngày`);
            setTimeout(() => location.reload(), 1000);
        } else {
            showToast('error', 'Lỗi', data.message);
        }
    })
    .catch(error => {
        showToast('error', 'Lỗi', 'Có lỗi xảy ra khi gia hạn proxy');
    });
}

function showQuickStats() {
    const modal = createModal('Thống kê nhanh', `
        <div class="quick-stats">
            <div class="stat-row">
                <span>Doanh thu hôm nay:</span>
                <strong><?= formatCurrency($stats['revenue_today']) ?></strong>
            </div>
            <div class="stat-row">
                <span>Đơn hàng hôm nay:</span>
                <strong><?= $stats['orders_today'] ?></strong>
            </div>
            <div class="stat-row">
                <span>Người dùng mới hôm nay:</span>
                <strong><?= $stats['new_users_today'] ?></strong>
            </div>
            <div class="stat-row">
                <span>Proxy sắp hết hạn:</span>
                <strong style="color: #dc3545;"><?= $stats['expiring_proxies'] ?></strong>
            </div>
        </div>
        <style>
        .quick-stats { margin: 20px 0; }
        .stat-row { 
            display: flex; 
            justify-content: space-between; 
            padding: 10px 0; 
            border-bottom: 1px solid #eee; 
        }
        .stat-row:last-child { border-bottom: none; }
        </style>
    `);
    showModal(modal);
}

function exportData() {
    const options = [
        { value: 'users', text: 'Danh sách người dùng' },
        { value: 'orders', text: 'Danh sách đơn hàng' },
        { value: 'proxies', text: 'Danh sách proxy' },
        { value: 'transactions', text: 'Lịch sử giao dịch' }
    ];
    
    const select = options.map(opt => 
        `<option value="${opt.value}">${opt.text}</option>`
    ).join('');
    
    const modal = createModal('Xuất dữ liệu', `
        <div class="form-group">
            <label>Chọn loại dữ liệu:</label>
            <select id="exportType" class="form-select">
                ${select}
            </select>
        </div>
        <div class="form-group">
            <label>Định dạng:</label>
            <select id="exportFormat" class="form-select">
                <option value="csv">CSV</option>
                <option value="excel">Excel</option>
                <option value="pdf">PDF</option>
            </select>
        </div>
    `, [
        { text: 'Hủy', class: 'btn-secondary', onclick: 'closeModal()' },
        { text: 'Xuất dữ liệu', class: 'btn-primary', onclick: 'doExport()' }
    ]);
    showModal(modal);
}

function doExport() {
    const type = document.getElementById('exportType').value;
    const format = document.getElementById('exportFormat').value;
    
    window.open(`api/admin/export.php?type=${type}&format=${format}`, '_blank');
    closeModal();
}

function sendNotification() {
    const modal = createModal('Gửi thông báo', `
        <div class="form-group">
            <label>Tiêu đề:</label>
            <input type="text" id="notifTitle" class="form-input" placeholder="Nhập tiêu đề thông báo">
        </div>
        <div class="form-group">
            <label>Nội dung:</label>
            <textarea id="notifContent" class="form-textarea" placeholder="Nhập nội dung thông báo"></textarea>
        </div>
        <div class="form-group">
            <label>Gửi đến:</label>
            <select id="notifTarget" class="form-select">
                <option value="all">Tất cả người dùng</option>
                <option value="active">Người dùng đang hoạt động</option>
                <option value="new">Người dùng mới (7 ngày)</option>
            </select>
        </div>
    `, [
        { text: 'Hủy', class: 'btn-secondary', onclick: 'closeModal()' },
        { text: 'Gửi thông báo', class: 'btn-primary', onclick: 'doSendNotification()' }
    ]);
    showModal(modal);
}

function doSendNotification() {
    const title = document.getElementById('notifTitle').value;
    const content = document.getElementById('notifContent').value;
    const target = document.getElementById('notifTarget').value;
    
    if (!title || !content) {
        showToast('error', 'Lỗi', 'Vui lòng nhập đầy đủ thông tin');
        return;
    }
    
    fetch('api/admin/send-notification.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ title, content, target })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('success', 'Thành công', 'Đã gửi thông báo');
            closeModal();
        } else {
            showToast('error', 'Lỗi', data.message);
        }
    });
}

function systemMaintenance() {
    if (!confirm('Bạn có chắc chắn muốn chuyển hệ thống sang chế độ bảo trì?')) {
        return;
    }
    
    fetch('api/admin/maintenance.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ action: 'enable' })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('warning', 'Bảo trì', 'Đã bật chế độ bảo trì hệ thống');
        } else {
            showToast('error', 'Lỗi', data.message);
        }
    });
}
</script>

<!-- Chart.js CDN -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>