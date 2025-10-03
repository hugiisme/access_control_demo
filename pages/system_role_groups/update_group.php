<?php
    include_once __DIR__ . "/../../config/database.php";
    include_once __DIR__ . "/../../includes/query_helper.php";

    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    header("Content-Type: application/json; charset=UTF-8");

    $group_id = isset($_GET['group_id']) ? intval($_GET['group_id']) : 0;
    $role_id  = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $checked  = isset($_POST['checked']) ? intval($_POST['checked']) : 0;

    if (!$group_id || !$role_id) {
        http_response_code(400);
        echo json_encode(["error" => "Thiếu tham số"]);
        exit;
    }

    if ($checked) {
        // Thêm vai trò vào nhóm (nếu chưa có)
        $sql = "INSERT IGNORE INTO system_role_group_roles (system_role_group_id, system_role_id) 
                VALUES ($group_id, $role_id)";
        query($sql);
        echo json_encode(["status" => "inserted"]);
    } else {
        // Xóa vai trò khỏi nhóm
        $sql = "DELETE FROM system_role_group_roles 
                WHERE system_role_group_id = $group_id AND system_role_id = $role_id";
        query($sql);
        echo json_encode(["status" => "deleted"]);
    }
?>
