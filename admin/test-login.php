<?php
// File: admin/test-login.php
session_start();

// Test trực tiếp không qua form
$_SESSION['admin_id'] = 1;
$_SESSION['admin_name'] = 'Administrator';
$_SESSION['admin_email'] = 'admin@proxyprivate.vn';

echo "✅ Session đã được tạo!<br>";
echo "Admin ID: " . $_SESSION['admin_id'] . "<br>";
echo "Admin Name: " . $_SESSION['admin_name'] . "<br>";
echo "Admin Email: " . $_SESSION['admin_email'] . "<br><br>";

echo "<a href='index.php' style='background:#28a745;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;'>Vào Admin Dashboard</a>";

echo "<br><br><strong>⚠️ Xóa file này sau khi dùng!</strong>";
?>