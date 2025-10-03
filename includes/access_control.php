<?php 
    function getUserSnapshotVersion($user_id, $resource_type_id){
        $query = "SELECT resource_type_version 
                    FROM user_permission_snapshots
                    WHERE user_id = $user_id 
                        AND resource_type_id = $resource_type_id";
        $result = query($query);
        return $result;
    }

    function checkVersionMatch($user_id, $resource_type_id){
        $resource_type = mysqli_fetch_assoc(getResourceTypeById($resource_type_id));
        if(!$resource_type){
            $msg = "Loại tài nguyên không tồn tại";
            $msgType = "error";
            debug_log($msgType, $msg);
            return false;
        }
        
        $resource_type_version = (int) $resource_type["version"];
        $user_snapshot_row = mysqli_fetch_assoc(getUserSnapshotVersion($user_id, $resource_type_id));
        if(!$user_snapshot_row){ 
            // Chưa có snapshot -> return false nhưng KHÔNG lặp vô hạn
            return false;
        }
        $user_snapshot_version = (int) $user_snapshot_row["resource_type_version"];
        return $resource_type_version === $user_snapshot_version;
    }

    function deleteSnapshot($user_id){
        global $conn;
        $sql = "DELETE FROM user_permission_snapshots WHERE user_id = $user_id";
        if(!query($sql)){
            $msg = "Lỗi khi xóa snapshot: " .  mysqli_error($conn);
            $msg .= "\n Query: " . $sql;
            $msgType = "error";
            debug_log($msgType, $msg);
        }
    }

    function getInheritedActions($action_id){
        $sql = "WITH RECURSIVE action_tree AS (
                    SELECT id AS parent_id, id AS child_id
                    FROM actions
                    UNION ALL
                    SELECT ar.parent_action_id, ar.child_action_id
                    FROM action_relations ar
                    JOIN action_tree t ON t.child_id = ar.parent_action_id
                )
                SELECT a.*
                FROM action_tree at
                JOIN actions a ON a.id = at.child_id
                WHERE at.parent_id = " . intval($action_id);

        $result = query($sql);

        $actions = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $actions[] = $row; 
        }

        return $actions;
    }

    function getSystemRolePermission($user_id) {
        $sql = "SELECT 
                    usr.user_id AS `user_id`,
                    a.id AS `action_id`,
                    'NULL' AS `resource_id`,
                    rt.id AS `resource_type_id`,
                    sr.org_id AS `org_id`,
                    rt.version AS `resource_type_version`,
                    CONCAT('System role ', sr.name) AS `source`
                FROM user_system_roles usr 
                JOIN system_role_permissions srp ON usr.system_role_id = srp.system_role_id
                JOIN system_roles sr ON srp.system_role_id = sr.id
                JOIN permissions p ON srp.permission_id = p.id
                JOIN actions a ON p.action_id = a.id
                JOIN resource_types rt ON p.resource_type_id = rt.id
                WHERE usr.user_id = $user_id";
        $result = query($sql);
        return $result;
    }

    function getResourceRolePermission($user_id) {
        $sql = "SELECT 
                    ur.user_id AS `user_id`,
                    a.id AS `action_id`,
                    r.id AS `resource_id`,
                    r.resource_type_id AS `resource_type_id`,
                    r.org_id AS `org_id`, 
                    rt.version AS `resource_type_version`,
                    CONCAT('Resource role ', urr.name) AS `source`
                FROM user_resources ur
                JOIN resources r ON ur.resource_id = r.id
                JOIN user_resource_roles urr ON ur.resource_role_id = urr.id
                JOIN resource_role_actions rra ON rra.resource_role_id = urr.id
                JOIN actions a ON rra.action_id = a.id
                JOIN resource_types rt ON r.resource_type_id = rt.id
                WHERE ur.user_id = $user_id";
        $result = query($sql);
        return $result;    
    }

    function insertIntoSnapshot($user_id, $action_id, $resource_id = null, $resource_type_id, $org_id = null, $resource_type_version, $source) {
        global $conn;
        $actions = getInheritedActions($action_id);

        // Escape và bọc chuỗi source
        $safe_source = "'" . mysqli_real_escape_string($conn, $source) . "'";

        // Nếu các giá trị là null -> đổi thành SQL NULL
        $resource_id_sql = $resource_id !== null && $resource_id !== 'NULL' ? intval($resource_id) : "NULL";
        $org_id_sql = $org_id !== null ? intval($org_id) : "NULL";
        $resource_type_version_sql = $resource_type_version !== null ? intval($resource_type_version) : "NULL";

        foreach($actions as $action) {
            $insert_action_id = intval($action["id"]);

            // Check trùng lặp
            $duplicate_check_sql = "SELECT 1 
                                    FROM user_permission_snapshots 
                                    WHERE user_id = $user_id 
                                        AND action_id = $insert_action_id 
                                        AND resource_id " . ($resource_id_sql === "NULL" ? "IS NULL" : "= $resource_id_sql") . "
                                        AND resource_type_id = $resource_type_id
                                        AND org_id " . ($org_id_sql === "NULL" ? "IS NULL" : "= $org_id_sql") . "
                                        AND source = $safe_source
                                    LIMIT 1";
            $duplicate_check_result = query($duplicate_check_sql);
            if(!$duplicate_check_result) {
                $msg = "Lỗi khi kiểm tra trùng lặp: " . mysqli_error($conn);
                $msg .= "\n Query: " . $duplicate_check_sql;
                $msgType = "error";
                debug_log($msgType, $msg);
                continue; // Bỏ qua insert nếu lỗi
            }

            if (mysqli_num_rows($duplicate_check_result) > 0) {
                mysqli_free_result($duplicate_check_result);
                continue;
            }
            mysqli_free_result($duplicate_check_result);

            $sql = "INSERT INTO user_permission_snapshots 
                    (user_id, action_id, resource_id, resource_type_id, org_id, resource_type_version, source)
                    VALUES ($user_id, $insert_action_id, $resource_id_sql, $resource_type_id, $org_id_sql, $resource_type_version_sql, $safe_source)";
            
            if(!query($sql)){
                $msg = "Lỗi khi insert vào snapshot: " . mysqli_error($conn);
                $msg .= "\n Query: " . $sql;
                $msgType = "error";
                debug_log($msgType, $msg);
            }
        }
    }


    function buildSnapshot($user_id){
        deleteSnapshot($user_id);

        // Bước 1: Query quyền từ system_roles
        $systemRolePermissionResult = getSystemRolePermission($user_id);
        while($systemRoleSnapshot = mysqli_fetch_assoc($systemRolePermissionResult)) {
            // Insert vào snapshot
            insertIntoSnapshot(
                $systemRoleSnapshot["user_id"], 
                $systemRoleSnapshot["action_id"],
                $systemRoleSnapshot["resource_id"],
                $systemRoleSnapshot["resource_type_id"],
                $systemRoleSnapshot["org_id"],
                $systemRoleSnapshot["resource_type_version"], 
                $systemRoleSnapshot["source"]
            );
        }

        // Bước 2: Query quyền từ resource roles
        $resourceRolePermissionResult = getResourceRolePermission($user_id);
        debug_log("info", "Building snapshot for user $user_id from resource roles");
        debug_log("info", "Number of resource role permissions: " . mysqli_num_rows($resourceRolePermissionResult));
        while($resourceRoleSnapshot = mysqli_fetch_assoc($resourceRolePermissionResult)) {
            insertIntoSnapshot(
                $resourceRoleSnapshot["user_id"], 
                $resourceRoleSnapshot["action_id"],
                $resourceRoleSnapshot["resource_id"],
                $resourceRoleSnapshot["resource_type_id"],
                $resourceRoleSnapshot["org_id"],
                $resourceRoleSnapshot["resource_type_version"], 
                $resourceRoleSnapshot["source"]
            );
        }
        
        // TODO: continue
        // Bước 3: Check quyền kế thừa của resource (folder-file)
        // Bước 4: Check policy 
    }

    function hasPermission($user_id, $action_name, $resource_id = null, $resource_type_id = null) {
        // TODO: fix
        // Chưa có scope
        global $conn;
        // if (defined("IS_DEBUG") && IS_DEBUG) {
        //     return true;
        // }

        $action_name = strtolower($action_name);
        $action = mysqli_fetch_assoc(getActionByName($action_name));
        if (!$action) {
            debug_log("error", "Action '$action_name' không tồn tại");
            return false;
        }
        $action_id = (int)$action["id"];
        $action_need_specific_entity = (bool)$action["need_specific_entity"];
        if(!$action_need_specific_entity && $resource_id !== null){
            $msg = "Action " . $action["name"] . " không thể thực hiện trên resource cụ thể";
            $msgType = "warning";
            debug_log($msgType, $msg);
            return false;
        }

        if($resource_id == null && $resource_type_id == null){
            $msg = "Cần khai báo resource_id hoặc resource_type_id";
            $msgType = "warning";
            debug_log($msgType, $msg);
            return false;
        }
        
        if($resource_id != null && $resource_type_id == null){
            $resource_type_id = mysqli_fetch_assoc(getResourceByID($resource_id))["resource_type_id"];
        }
        $direct_allowed = permissionDirectCheck($user_id, $action_id, $resource_id);
        $global_allowed =  permissionGlobalCheck($user_id, $action_id, $resource_type_id);

        if($direct_allowed || $global_allowed){
            $msg = "hasPermission: check for user $user_id on resource_id $resource_id or type_id $resource_type_id = ALLOW";
            $msgType = "info";
            debug_log($msgType, $msg);
            return true;
        } else {
            $msg = "hasPermission: check for user $user_id on resource_id $resource_id or type_id $resource_type_id = DENY";
            $msgType = "info";
            debug_log($msgType, $msg);
            return false;
        }
    }

    function permissionDirectCheck($user_id, $action_id, $resource_id) {
        global $conn;
        // Check action có thể thực hiện trên resource cụ thể không
        $action = mysqli_fetch_assoc(getActionById($action_id));
        if(!$action) {
            $msg = "Action ID $action_id không tồn tại";
            $msgType = "error";
            debug_log($msgType, $msg);
            return false;
        }
        $need_specific_entity = (bool)$action["need_specific_entity"];
        if(!$need_specific_entity){
            $msg = "Action " . $action["name"] . " không thể thực hiện trên resource cụ thể";
            $msgType = "warning";
            debug_log($msgType, $msg);
            return false;
        }
        if($resource_id == null) {
            $msg = "Cần khai báo resource_id để kiểm tra quyền trực tiếp";
            $msgType = "warning";
            debug_log($msgType, $msg);
            return false;
        }
        $sql = "SELECT 1
                FROM user_permission_snapshots ups
                WHERE ups.user_id = $user_id
                    AND ups.action_id = $action_id
                    AND ups.resource_id = $resource_id
                LIMIT 1";
        $result = query($sql);
        if(!$result) {
            $msg = "Lỗi khi check quyền " . mysqli_error($conn);
            $msg .= "\n Query: " . $sql;
            $msgType = "error";
            debug_log($msgType, $msg);
            return false;
        }
        $allowed = (mysqli_num_rows($result) > 0);
        mysqli_free_result($result);
        return $allowed;
    }

    function permissionGlobalCheck($user_id, $action_id, $resource_type_id) {
        global $conn;
        $action = mysqli_fetch_assoc(getActionById($action_id));
        if(!$action) {
            $msg = "Action ID $action_id không tồn tại";
            $msgType = "error";
            debug_log($msgType, $msg);
            return false;
        }
        
        if($resource_type_id == null) {
            $msg = "Cần khai báo resource_type_id để kiểm tra quyền global";
            $msgType = "warning";
            debug_log($msgType, $msg);
            return false;
        }
        $sql = "SELECT 1
                FROM user_permission_snapshots ups
                WHERE ups.user_id = $user_id
                    AND ups.action_id = $action_id
                    AND ups.resource_type_id = $resource_type_id
                    AND ups.resource_id IS NULL
                LIMIT 1";
        $result = query($sql);
        if(!$result) {
            $msg = "Lỗi khi check quyền " . mysqli_error($conn);
            $msg .= "\n Query: " . $sql;
            $msgType = "error";
            debug_log($msgType, $msg);
            return false;
        }
        $allowed = (mysqli_num_rows($result) > 0);
        mysqli_free_result($result);
        return $allowed;
    }

    function getAccesibleResources($user_id, $action_id, $resource_type_id) {
        global $conn;
        if(!checkVersionMatch($user_id, $resource_type_id)){
            $msg = "Version mismatch, rebuild snapshot";
            $msgType = "INFO";
            debug_log($msgType, $msg);
            buildSnapshot($user_id);
        }

        $result_list = [];
        $seen_ids = []; // để lọc trùng

        // --- 1. Query các resource được cấp quyền trực tiếp ---
        $sql = "SELECT r.*
                FROM user_permission_snapshots ups
                JOIN resources r ON ups.resource_id = r.id
                WHERE ups.user_id = $user_id
                    AND ups.action_id = $action_id
                    AND ups.resource_type_id = $resource_type_id
                    AND ups.resource_id IS NOT NULL";

        debug_log("info", "Querying accessible resources for user $user_id, action_id $action_id, resource_type_id $resource_type_id");
        // debug_log("info", "SQL: $sql");
        $result = query($sql);
        if($result){
            while($row = mysqli_fetch_assoc($result)){
                if (!in_array($row['id'], $seen_ids, true)) {
                    $result_list[] = $row;
                    $seen_ids[] = $row['id'];
                }
            }
            mysqli_free_result($result);
        } else {
            $msg = "Lỗi khi lấy danh sách resource có resource_id: " . mysqli_error($conn);
            $msg .= "\n Query: " . $sql;
            debug_log("error", $msg);
        }

        // --- 2. Query quyền global (trên toàn bộ resource type) ---
        $sql = "SELECT r.*
                FROM user_permission_snapshots ups
                JOIN resources r ON ups.resource_type_id = r.resource_type_id
                WHERE ups.user_id = $user_id
                    AND ups.action_id = $action_id
                    AND ups.resource_type_id = $resource_type_id
                    AND ups.resource_id IS NULL";
        $result = query($sql);
        if($result && mysqli_num_rows($result) > 0){
            $table_name = mysqli_fetch_assoc(getResourceTypeById($resource_type_id))["name"];
            $entityQuery = "SELECT * FROM $table_name";
            $entityResult = query($entityQuery);
            if($entityResult){
                while($entityRow = mysqli_fetch_assoc($entityResult)){
                    $entity_id = $entityRow["id"];
                    $resource = mysqli_fetch_assoc(getResourceByTypeAndID($resource_type_id, $entity_id));
                    if($resource && !in_array($resource['id'], $seen_ids, true)){
                        $result_list[] = $resource;
                        $seen_ids[] = $resource['id'];
                    }
                }
                mysqli_free_result($entityResult);
            } else {
                $msg = "Lỗi khi lấy danh sách entity từ bảng $table_name: " . mysqli_error($conn);
                $msg .= "\n Query: " . $entityQuery;
                debug_log("error", $msg);
            }
        }
        if($result){
            mysqli_free_result($result);
        }


        return $result_list;
    }

?>