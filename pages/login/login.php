<?php 
    require_once __DIR__ . '/../../config/database.php';
    require_once __DIR__ . '/../../includes/notifications/notify.php';
    require_once __DIR__ . '/../../includes/access_control.php';
    require_once __DIR__ . '/../../includes/helper_function.php';
    require_once __DIR__ . '/../../includes/query_helper.php';

    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $userID = $_GET['row_id'] ?? null;

    if (!$userID) {
        $msg = "Không tìm thấy ID người dùng";
        $msgType = "error";
        redirect_with_message($msgType, $msg, "/index.php?view=home");
        exit();
    }

    $result = getUserByID($userID);
    if (!$result || mysqli_num_rows($result) === 0) {
        $msq = "Người dùng không tồn tại";
        $msgType = "error";
        redirect_with_message($msgType, $msg, "/index.php?view=home");  
        exit();
    } 

    $user = mysqli_fetch_assoc($result);
    $_SESSION['user'] = $user;
    buildSnapshot($user['id']);
    $msg = "Đăng nhập thành công vào tài khoản " . $user["id"] . " " . htmlspecialchars($user['name']);
    $msgType = "success";
    redirect_with_message($msgType, $msg, "/index.php?view=home");
    exit();
?>