<?php 
    require_once "table_renderer.php";
    require_once "config/database.php";
    require_once "includes/access_control.php";

    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $tableName = "system_role_group_permissions";
    $userId = $_SESSION['user']['id'] ?? null;

    if (!$userId) {
        echo "<h2>Vui lòng đăng nhập để xem dữ liệu.</h2>";
        exit;
    }

    $system_role_group_id = isset($_GET['id']) ? (int)$_GET['id'] : null;

    if (!$system_role_group_id) {
        add_notification("error", "Không tìm thấy ID nhóm vai trò");
        header("Location: /index.php?pageName=organizations");
        exit;
    }
?>