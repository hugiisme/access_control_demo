<?php 
    function createResource($name, $description, $org_id, $resource_type_id, $entity_id) {
        global $conn;

        // Bọc chuỗi trong dấu nháy đơn
        $name_value = "'" . $name . "'";
        $description_value = ($description === null || $description === '') ? "NULL" : "'" . $description . "'";
        $org_id_value = ($org_id === null || $org_id === '') ? "NULL" : (int)$org_id;

        $sql = "INSERT INTO resources (name, description, org_id, resource_type_id, entity_id)
                VALUES ($name_value, $description_value, $org_id_value, $resource_type_id, $entity_id)";

        if (!query($sql)) {
            $msg = "Lỗi khi tạo resource: " . mysqli_error($conn);
            $msgType = "error";
            redirect_with_message($msgType, $msg);
            exit;
        }
    }

    function updateResource($id, $name, $description, $org_id, $resource_type_id, $entity_id) {
        global $conn;

        $name_value = "'" . $name . "'";
        $description_value = ($description === null || $description === '') ? "NULL" : "'" . $description . "'";
        $org_id_value = ($org_id === null || $org_id === '') ? "NULL" : (int)$org_id;

        $sql = "UPDATE resources 
                SET name = $name_value, 
                    description = $description_value, 
                    org_id = $org_id_value, 
                    resource_type_id = $resource_type_id, 
                    entity_id = $entity_id
                WHERE id = $id";

        if (!query($sql)) {
            $msg = "Lỗi khi cập nhật resource: " . mysqli_error($conn);
            $msgType = "error";
            redirect_with_message($msgType, $msg);
            exit;
        }
    }

    function updateResourceTypeVersion($resource_type_id) {
        debug_log("info", "Cập nhật version cho resource_type_id: $resource_type_id");
        global $conn;
        $sql = "UPDATE resource_types 
                SET version = version + 1 
                WHERE id = $resource_type_id";
        if (!query($sql)) {
            $msg = "Lỗi khi cập nhật version của resource type: " . mysqli_error($conn);
            $msgType = "error";
            redirect_with_message($msgType, $msg);
            exit;
        }
    }

    function assignResourceRole($resource_id, $role_id) {
        global $conn;
        $sql = "INSERT INTO resource_roles (resource_id, role_id) 
                VALUES ($resource_id, $role_id)
                ON DUPLICATE KEY UPDATE role_id = role_id"; // tránh lỗi duplicate
        if (!query($sql)) {
            $msg = "Lỗi khi gán vai trò cho resource: " . mysqli_error($conn);
            $msgType = "error";
            redirect_with_message($msgType, $msg);
            exit;
        }
    }

    function assignResourceRoleToUser($user_id, $resource_type_id, $entity_id, $role_name) {
        // TODO: fix
        // Nếu đã có role cao hơn thì thôi
        global $conn;

        // Lấy resource_id
        $sql_resource = "SELECT id FROM resources 
                         WHERE resource_type_id = $resource_type_id AND entity_id = $entity_id";
        $result = query($sql_resource);
        if ($result && mysqli_num_rows($result) > 0) {
            $resource = mysqli_fetch_assoc($result);
            $resource_id = $resource['id'];
        } else {
            $msg = "Không tìm thấy resource để gán vai trò.";
            $msgType = "error";
            redirect_with_message($msgType, $msg);
            exit;
        }

        // Lấy role_id từ tên vai trò
        $sql_role = "SELECT id FROM user_resource_roles WHERE name = '" . mysqli_real_escape_string($conn, $role_name) . "'";
        $result_role = query($sql_role);
        if ($result_role && mysqli_num_rows($result_role) > 0) {
            $role = mysqli_fetch_assoc($result_role);
            $role_id = $role['id'];
        } else {
            $msg = "Không tìm thấy vai trò người dùng.";
            $msgType = "error";
            redirect_with_message($msgType, $msg);
            exit;
        }

        // Gán vai trò cho người dùng trên resource
        $sql_assign = "INSERT INTO user_resources (user_id, resource_id, resource_role_id) 
                       VALUES ($user_id, $resource_id, $role_id)
                       ON DUPLICATE KEY UPDATE resource_role_id = resource_role_id"; // tránh lỗi duplicate
        if (!query($sql_assign)) {
            $msg = "Lỗi khi gán vai trò cho người dùng trên resource: " . mysqli_error($conn);
            $msgType = "error";
            redirect_with_message($msgType, $msg);
            exit;
        }
    }
?>
