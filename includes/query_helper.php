<?php 
    function getResourceTypeById($resource_type_id) {
        $query = "SELECT * 
                    FROM resource_types
                    WHERE id = $resource_type_id";
        return query($query);
    }
    
    function getResourceTypeByName($resource_type_name) {
        global $conn;
        $safe_resource_type_name = "'" . mysqli_real_escape_string($conn, $resource_type_name) . "'";
        $query = "SELECT * 
                    FROM resource_types 
                    WHERE name = $safe_resource_type_name";
        $result = query($query);
        return $result;
    }

    function getResourceTypeList() {
        $query = "SELECT * FROM resource_types";
        $result = query($query);
        return $result;
    }

    function getResourceByTypeAndID($resource_type_id, $entity_id){
        $query = "SELECT * 
                    FROM resources 
                    WHERE resource_type_id = $resource_type_id
                        AND entity_id = $entity_id";
        $result = query($query);
        return $result;
    }

    function getResourceByID($resource_id){
        $query = "SELECT * 
                    FROM resources 
                    WHERE id = $resource_id";
        $result = query($query);
        return $result;
    }

    function getActionByName($action_name) {
        global $conn;
        $safe_action_name = "'" . mysqli_real_escape_string($conn, $action_name) . "'";
        $query = "SELECT *
                    FROM actions
                    WHERE name = $safe_action_name";
        $result = query($query);
        return $result;
    }

    function getActionById($action_id){
        $query = "SELECT *
                    FROM actions
                    WHERE id = $action_id";
        $result = query($query);
        return $result;    
    }

    function getActionList() {
        $query = "SELECT * FROM actions";
        $result = query($query);
        return $result;
    }

    function getOrgList($userId) {
        $action_id = mysqli_fetch_assoc(getActionByName('View'))['id'] ?? null;
        $resource_type_id = mysqli_fetch_assoc(getResourceTypeByName('organizations'))['id'] ?? null;
        $accessibleResources = getAccesibleResources($userId, $action_id, $resource_type_id);

        if (!empty($accessibleResources)) {
            $entityIds = array_column($accessibleResources, 'entity_id');
            if (!empty($entityIds)) {
                $entityIdList = implode(',', $entityIds);

                $query = "SELECT o.*
                    FROM organizations o
                    LEFT JOIN organizations org ON o.parent_org_id = org.id
                    LEFT JOIN org_types ot ON ot.id = o.org_type_id
                    JOIN user_orgs uo ON uo.org_id = o.id AND uo.user_id = $userId
                    WHERE 1=1
                    AND o.id IN ($entityIdList)";
            }
        } else {
            $query = "SELECT 1 WHERE 1=0";
            debug_log("info", "Người dùng ID $userId không có quyền xem tổ chức nào." );
        }
        $result = query($query);
        return $result;
    }

    function getParentOrgList($userId) {
        // TODO: fix
        // ràng buộc tổ chức cha phải là tổ chức mà người dùng đã tham gia hoặc đang quản lý, hiện tại đang sai (chắc là sửa action)
        // Không được chọn chính nó
        $action_id = mysqli_fetch_assoc(getActionByName('View'))['id'] ?? null;
        $resource_type_id = mysqli_fetch_assoc(getResourceTypeByName('organizations'))['id'] ?? null;
        $accessibleResources = getAccesibleResources($userId, $action_id, $resource_type_id);
        
        if (!empty($accessibleResources)) {
            $entityIds = array_column($accessibleResources, 'entity_id');
            if (!empty($entityIds)) {
                $entityIdList = implode(',', $entityIds);

            // JOIN user_orgs uo ON uo.org_id = o.id AND uo.user_id = $userId
            $query = "SELECT o.*
                    FROM organizations o
                    LEFT JOIN organizations org ON o.parent_org_id = org.id
                    LEFT JOIN org_types ot ON ot.id = o.org_type_id
                    WHERE 1=1
                    AND o.id IN ($entityIdList)
                    AND o.org_level < 3";
            }
        } else {
            $query = "SELECT 1 WHERE 1=0";
            debug_log("info", "Người dùng ID $userId không có quyền xem tổ chức nào." );
        }
        $result = query($query);
        return $result;
    }

    function getOrgTypeList() {
        $query = "SELECT * FROM org_types";
        $result = query($query);
        return $result;
    }

    function getOrgLevelList() {
        $query = "SELECT * FROM org_levels ORDER BY level_index ASC";
        $result = query($query);
        return $result;
    }

    function getOrgByID($org_id){
        $query = "SELECT * 
                    FROM organizations 
                    WHERE id = $org_id";
        $result = query($query);
        return $result;
    }

    function getOrgByIdList($id_list){
        $query = "SELECT * FROM organizations WHERE id IN ($id_list)";
        $result = query($query);

        $orgs = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $orgs[] = $row;
        }
        return $orgs;
    }

    function getOrgTypeByID($org_type_id){
        $query = "SELECT * 
                    FROM org_types 
                    WHERE id = $org_type_id";
        $result = query($query);
        return $result;
    }

    function getUserByName($user_name){
        global $conn;
        $safe_user_name = "'" . mysqli_real_escape_string($conn, $user_name) . "'";
        $query = "SELECT * 
                    FROM users 
                    WHERE user_name = $safe_user_name";
        $result = query($query);
        return $result;
    }

    function getUserByID($user_id){
        $query = "SELECT * 
                    FROM users 
                    WHERE id = $user_id";
        $result = query($query);
        return $result;
    }

    function getUserOrg($user_id, $org_id){
        $query = "SELECT * 
                    FROM user_orgs 
                    WHERE user_id = $user_id
                        AND org_id = $org_id";
        $result = query($query);
        return $result;
    }

    function getRoleByName($role_name) {
        $query = "SELECT * 
                    FROM user_resource_roles 
                    WHERE name = '$role_name'";
        $result = query($query);
        return $result;
    }

    function getParentRoleGroupList() {
        // TODO fix
        // Với edit, không được chọn chính nó
        $query = "SELECT * FROM system_role_groups";
        $result = query($query);
        return $result;
    }

    function getRoleGroupNameById($role_group_id) {
        $query = "SELECT name 
                    FROM system_role_groups 
                    WHERE id = $role_group_id";
        $result = query($query);
        return $result;
    }

    function getInheritedRoles($groupId) {
        $inherited = [];

        while ($groupId !== null) {
            $res = query("SELECT parent_group_id FROM system_role_groups WHERE id = {$groupId}");
            $row = mysqli_fetch_assoc($res);
            $parent = $row ? $row['parent_group_id'] : null;

            if ($parent) {
                $res2 = query("SELECT system_role_id FROM system_role_group_roles WHERE system_role_group_id = {$parent}");
                while ($r = mysqli_fetch_assoc($res2)) {
                    $inherited[$r['system_role_id']] = true;
                }
            }

            $groupId = $parent;
        }

        return $inherited;
    }


?>