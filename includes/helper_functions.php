<?php 
    function redirectWithError($msg, $redirect = "/index.php") {
        add_notification("error", $msg, 4);
        header("Location: $redirect");
        exit;
    }

    function getOrgName() {
        global $conn;
        
        $query = "SELECT id, name FROM organizations ORDER BY id ASC";
        $result = query($conn, $query);
        if(!$result) {
            add_notification("error", "Lỗi khi lấy danh sách tổ chức cha", 4000);
            echo "Query failed: " . mysqli_error($conn);
            exit;
        }
        return $result;
    }

    function getOrgType(){
        global $conn;

        $query = "SELECT id, name FROM org_types ORDER BY id ASC";
        $result = query($conn, $query);
        if(!$result) {
            add_notification("error", "Lỗi khi lấy danh sách loại tổ chức", 4000);
            echo "Query failed: " . mysqli_error($conn);
            exit;
        }
        return $result;
    }
    function getActionList() {
        global $conn;
        
        $query = "SELECT id, name FROM actions ORDER BY id ASC";
        $result = query($conn, $query);
        if(!$result) {
            add_notification("error", "Lỗi khi lấy danh sách hành động", 4000);
            echo "Query failed: " . mysqli_error($conn);
            exit;
        }
        return $result;
    }

    function getResourceTypesList() {
        global $conn;
        $query = "SELECT id, name FROM resource_types";
        $result = query($conn, $query);
        if (!$result) {
            add_notification("error", "Lỗi khi lấy danh sách loại tài nguyên", 4000);
            echo "Query failed: " . mysqli_error($conn);
            exit;
        }
        return $result;
    }

    function permissionList() {
        global $conn;
        $query = "SELECT 
                    p.id AS id,
                    a.name AS action_name,
                    rt.name aS resource_type_name
                FROM permissions p
                JOIN actions a ON p.action_id = a.id 
                JOIN resource_types rt ON p.resource_type_id = rt.id";
        $result = query($conn, $query);
        if (!$result) {
            add_notification("error", "Lỗi khi lấy danh sách loại tài nguyên", 4000);
            echo "Query failed: " . mysqli_error($conn);
            exit;
        }
        return $result;
    }

    function org_permissionList($org_id) {
        global $conn;
        $query = "SELECT p.id, a.name AS action_name, rt.name AS resource_type_name
                  FROM org_permissions op
                  JOIN permissions p ON op.permission_id = p.id
                  JOIN actions a ON p.action_id = a.id
                  JOIN resource_types rt ON p.resource_type_id = rt.id
                  WHERE op.org_id = $org_id";
        return query($conn, $query);
    }

    function group_permissionList($group_id) {
        global $conn;
        $query = "SELECT p.id, a.name AS action_name, rt.name AS resource_type_name
                  FROM system_role_group_permissions srgp
                  JOIN permissions p ON srgp.permission_id = p.id
                  JOIN actions a ON p.action_id = a.id
                  JOIN resource_types rt ON p.resource_type_id = rt.id
                  WHERE srgp.system_role_group_id = $group_id";
        return query($conn, $query);
    }

    function getUserList($org_id) {
        global $conn;
        $query = "SELECT u.id, u.name 
                FROM user_orgs uo
                JOIN users u ON uo.user_id = u.id
                JOIN organizations o ON uo.org_id = o.id
                WHERE o.id = $org_id";
        return query($conn, $query);
    }
?>