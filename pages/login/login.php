<?php 
    require_once __DIR__ . '/../../config/database.php';
    require_once __DIR__ . '/../../includes/notify.php';
    require_once __DIR__ . '/../../includes/access_control.php';

    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $userID = $_GET['id'] ?? null;

    if (!$userID) {
        add_notification("error", "Không tìm thấy ID người dùng.");
        header("Location: /index.php?pageName=home");
        exit();
    }

    $query = "SELECT * FROM users WHERE id = $userID";
    $result = query($conn, $query);

    if (!$result || mysqli_num_rows($result) === 0) {
        add_notification("error", "Người dùng không tồn tại");
        header("Location: /index.php?pageName=home");
        exit();
    } 

    $user = mysqli_fetch_assoc($result);
    $_SESSION['user'] = $user;
    buildSnapshot($user['id']);
    
    add_notification("success", "Đăng nhập thành công vào tài khoản " . htmlspecialchars($user['name']));
    add_notification("info", "ID người dùng: " . htmlspecialchars($user['id']));
    header("Location: /index.php?pageName=home");
    exit();
?>