<?php
    include_once __DIR__ . "/../../config/database.php";
    include_once __DIR__ . "/../../includes/query_helper.php";
    include_once __DIR__ . "/../../includes/resource_manager.php";

    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    header("Content-Type: application/json; charset=UTF-8");

    $org_id = isset($_GET['org_id']) ? intval($_GET['org_id']) : 0;
    $role_id  = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $checked  = isset($_POST['checked']) ? intval($_POST['checked']) : 0;
    $user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;

    if (!$org_id || !$user_id || !$role_id) {
        http_response_code(400);
        echo json_encode(["error" => "Thiếu tham số"]);
        exit;
    }

    if ($checked) {
        global $conn;
        $sql = "INSERT INTO user_system_roles (user_id, system_role_id) 
                VALUES ($user_id, $role_id)";
        $result = query($sql);
        if (!$result) {
            die("Insert failed");
        }
        
        $entity_id = mysqli_insert_id($conn);
        $table_name = "user_system_roles";
        $resource_type_id = mysqli_fetch_assoc(getResourceTypeByName($table_name))["id"];
        $resource_name = $table_name . "_" . $entity_id;
        $resource_description = "Resource for {$table_name} ID {$entity_id}";
        $org_id = null; 
        $creator_id = $_SESSION['user']['id'] ?? null;
        $role_name = "creator";
        
        createResource($resource_name, $resource_description, $org_id, $resource_type_id, $entity_id);
        updateResourceTypeVersion($resource_type_id);
        assignResourceRoleToUser($user_id, $resource_type_id, $entity_id, $role_name);
        echo json_encode(["status" => "inserted"]);
    } else {
        $find_entity_sql = "SELECT id FROM user_system_roles WHERE user_id = $user_id AND system_role_id = $role_id";
        $find_entity_result = query($find_entity_sql);
        $entity_row = mysqli_fetch_assoc($find_entity_result);
        $entity_id = $entity_row ? $entity_row["id"] : 0;
        if (!$entity_id) {
            http_response_code(400);
            echo json_encode(["error" => "Quan hệ người dùng - vai trò hệ thống không tồn tại"]);
            exit;
        }
        $sql = "DELETE FROM user_system_roles WHERE id = $entity_id";
        query($sql);

        // Xóa resource tương ứng
        $table_name = "user_system_roles";
        $resource_type_id = mysqli_fetch_assoc(getResourceTypeByName($table_name))["id"];

        // Tìm resource id trong bảng resources
        $res_sql = "SELECT id FROM resources 
                    WHERE resource_type_id = $resource_type_id 
                      AND entity_id = $entity_id";
        $res_row = mysqli_fetch_assoc(query($res_sql));
        $resource_id = $res_row ? $res_row["id"] : 0;

        if ($resource_id) {
            $deleteResourceQuery = "DELETE FROM resources WHERE id = $resource_id";
            query($deleteResourceQuery);
        }   
        echo json_encode(["status" => "deleted", "entity_id" => $entity_id]);
    }
    
?>
