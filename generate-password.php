<?php
// File: generate-password.php - Xóa sau khi dùng!

$new_password = 'ProxyAdmin2025!';  // Đổi thành password bạn muốn
$hash = password_hash($new_password, PASSWORD_DEFAULT);

echo "<h2>🔐 Tạo Password Admin Mới</h2>";
echo "<strong>Password mới:</strong> {$new_password}<br>";
echo "<strong>Hash:</strong> {$hash}<br><br>";

echo "<h3>📋 Chạy lệnh SQL này trong phpMyAdmin:</h3>";
echo "<textarea style='width:100%;height:100px;'>";
echo "UPDATE users SET password_hash = '{$hash}' WHERE email = 'admin@proxyprivate.vn';";
echo "</textarea><br><br>";

echo "<h3>✅ Sau khi chạy SQL:</h3>";
echo "- Email: <strong>admin@proxyprivate.vn</strong><br>";
echo "- Password: <strong>{$new_password}</strong><br>";

echo "<br><strong style='color:red;'>⚠️ XÓA FILE NÀY SAU KHI DÙNG!</strong>";
?>