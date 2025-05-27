<?php
// File: api/admin/test.php
header('Content-Type: application/json');

echo json_encode([
    'success' => true,
    'message' => 'API admin hoạt động!',
    'timestamp' => date('Y-m-d H:i:s')
]);
?>