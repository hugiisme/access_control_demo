<?php
    include_once __DIR__ . "/../includes/notify.php";
    include_once __DIR__ . "/../config/database.php";
    require_once __DIR__ . "/../includes/query_helper.php";
    require_once __DIR__ . "/../includes/helper_function.php";

    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $user_id = $_SESSION['user']['id'] ?? null;

    if ($user_id) {
        $user_id = (int) $user_id; 

        $sql = "DELETE 
                FROM user_permission_snapshots 
                WHERE user_id = $user_id;";
        if (!query($sql)) {
            $msg = "Lỗi MySQL khi đăng xuất " . mysqli_error($conn);
            $msgType = "error";
            redirect_with_message($msgType, $msg);
        }
    } else {
        $msg = "Lỗi khi đăng xuất: Không tìm thấy user_id trong session";
        $msgType = "error";
        redirect_with_message($msg, $msgType);
    }

    $_SESSION = [];
    session_destroy();

    // Thêm thông báo
    $msg = "Đăng xuất thành công.";
    $msgType = "success";
    redirect_with_message($msgType, $msg);

    // Chuyển hướng về trang login hoặc trang chủ
    header("Location: /index.php");
    exit();
?>
