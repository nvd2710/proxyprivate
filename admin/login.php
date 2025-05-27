<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập Admin - ProxyPrivate.vn</title>
    <link rel="stylesheet" href="assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="login-container">
        <div class="login-box">
            <div class="login-header">
                <h1>🛡️ Admin Panel</h1>
                <p>ProxyPrivate.vn</p>
            </div>
            
            <?php if (isset($login_error)): ?>
            <div class="login-error">
                <i class="fas fa-exclamation-triangle"></i>
                <?= htmlspecialchars($login_error) ?>
            </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label class="form-label">Email quản trị:</label>
                    <input type="email" name="username" class="form-input" placeholder="admin@proxyprivate.vn" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Mật khẩu:</label>
                    <input type="password" name="password" class="form-input" placeholder="Nhập mật khẩu" required>
                </div>
                
                <button type="submit" class="btn btn-primary btn-full">
                    <i class="fas fa-sign-in-alt"></i>
                    Đăng nhập
                </button>
            </form>
            
            <div style="text-align: center; margin-top: 20px; font-size: 12px; color: #6c757d;">
                © 2025 ProxyPrivate.vn - Admin Panel
            </div>
        </div>
    </div>
</body>
</html>