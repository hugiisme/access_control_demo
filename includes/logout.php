<?php
// Load các file cần thiết
include __DIR__ . "/../includes/notify.php";
include __DIR__ . "/../config/database.php";

// Đảm bảo session đã khởi tạo
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Lấy user_id từ session đúng cấu trúc
$user_id = $_SESSION['user']['id'] ?? null;

if ($user_id) {
    $user_id = (int) $user_id; // tránh SQL injection (dù ở đây ID là số)

    $sql = "DELETE 
            FROM user_permission_snapshots 
            WHERE user_id = $user_id;";
    if (!query($conn, $sql)) {
        error_log("MySQL error on logout: " . mysqli_error($conn));
    }
} else {
    error_log("Logout: No user_id found in session");
}

// Xóa toàn bộ dữ liệu session
$_SESSION = [];
session_destroy();

// Thêm thông báo
add_notification("success", "Đăng xuất thành công.");

// Chuyển hướng về trang login hoặc trang chủ
header("Location: /index.php");
exit();
?>
