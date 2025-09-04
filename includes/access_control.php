<?php
    function getActionID($actionName) {
        global $conn;
        $query = "SELECT id FROM actions WHERE name = '$actionName' LIMIT 1";
        $result = query($conn, $query);
        if ($result && mysqli_num_rows($result) > 0) {
            $row = mysqli_fetch_assoc($result);
            return (int)$row['id'];
        }
        return null;
    }

    function getResourceType($resourceTypeId) {
        global $conn;
        $query = "SELECT id, name, version FROM resource_types WHERE id = $resourceTypeId LIMIT 1";
        $result = query($conn, $query);
        if ($result && mysqli_num_rows($result) > 0) {
            return mysqli_fetch_assoc($result);
        }
        return null;
    }

    function getResourceTypeByName($resourceTypeName) {
        global $conn;
        $query = "SELECT id, name, version FROM resource_types WHERE name = '$resourceTypeName' LIMIT 1";
        $result = query($conn, $query);
        if ($result && mysqli_num_rows($result) > 0) {
            return mysqli_fetch_assoc($result);
        }
        return null;
    }

    function getResource($resourceId) {
        global $conn;
        $query = "SELECT id, name, org_id, resource_type_id, entity_id FROM resources WHERE id = $resourceId LIMIT 1";
        $result = query($conn, $query);
        if ($result && mysqli_num_rows($result) > 0) {
            return mysqli_fetch_assoc($result);
        }
        return null;
    }

    /* ===============================
       Quản lý snapshot
       =============================== */

    function deleteSnapshot($userID) {
        global $conn;
        $sql = "DELETE FROM user_permission_snapshots WHERE user_id = $userID";
        if (!query($conn, $sql)) {
            error_log("MySQL error on delete snapshot: " . mysqli_error($conn));
        }
    }

    function checkVersionMatch($userID, $resourceTypeId) {
        global $conn;
        // Lấy version hiện tại từ resource_types
        $resourceType = getResourceType($resourceTypeId);
        if (!$resourceType) {
            return false;
        }
        $currentVersion = (int)$resourceType['version'];

        // Lấy version trong snapshot
        $query = "
            SELECT resource_type_version 
            FROM user_permission_snapshots
            WHERE user_id = $userID AND resource_type_id = $resourceTypeId
            LIMIT 1
        ";
        $result = query($conn, $query);
        if ($result && mysqli_num_rows($result) > 0) {
            $row = mysqli_fetch_assoc($result);
            return ((int)$row['resource_type_version'] === $currentVersion);
        }
        return false;
    }

    function getAllChildActions($actionId) {
        global $conn;
        $allChildren = array();
        $visited = array();
        $queue = array($actionId);

        while (!empty($queue)) {
            $current = array_shift($queue);
            if (isset($visited[$current])) {
                continue;
            }
            $visited[$current] = true;

            $query = "SELECT child_action_id FROM action_relations WHERE parent_action_id = $current";
            $result = query($conn, $query);
            while ($row = mysqli_fetch_assoc($result)) {
                $childId = (int)$row['child_action_id'];
                if (!isset($visited[$childId])) {
                    $allChildren[] = $childId;
                    $queue[] = $childId;
                }
            }
            if ($result) {
                mysqli_free_result($result);
            }
        }
        return array_unique($allChildren);
    }

    function buildSnapshot($userID) {
        global $conn;
        if(defined("IS_DEBUG") && IS_DEBUG) {
            
            // Xóa snapshot cũ
            deleteSnapshot($userID);

            $mapAllActionAndResourcesQuery = "
            INSERT INTO user_permission_snapshots (user_id, action_id, resource_id, resource_type_id, org_id, resource_type_version)
                SELECT 
                    $userID AS user_id,
                    a.id AS action_id,
                    r.id AS resource_id,
                    r.resource_type_id,
                    r.org_id,
                    (SELECT version FROM resource_types WHERE id = r.resource_type_id) AS resource_type_version
                FROM actions a
                CROSS JOIN resources r;
            ";

            query($conn, $mapAllActionAndResourcesQuery);

            return;
        }

        // Xóa snapshot cũ
        deleteSnapshot($userID);

        $allPermissions = [];
        $seen = [];

        // ===== B1: Quyền từ system role =====
        $querySystemRole = "
            SELECT 
                p.action_id,
                p.resource_type_id,
                rt.version AS resource_type_version
            FROM user_system_roles usr
            JOIN system_roles sr ON sr.id = usr.system_role_id
            JOIN system_role_permissions srp ON srp.system_role_id = sr.id
            JOIN permissions p ON p.id = srp.permission_id
            JOIN resource_types rt ON rt.id = p.resource_type_id
            WHERE usr.user_id = $userID
        ";
        $resultSystemRole = query($conn, $querySystemRole);

        while ($row = mysqli_fetch_assoc($resultSystemRole)) {
            $resourceTypeId = (int)$row['resource_type_id'];
            $version        = (int)$row['resource_type_version'];
            $baseActionId   = (int)$row['action_id'];

            $actionNameRes = query($conn, "SELECT name FROM actions WHERE id = $baseActionId LIMIT 1");
            $actionName = ($actionNameRes && mysqli_num_rows($actionNameRes) > 0) 
                ? mysqli_fetch_assoc($actionNameRes)['name'] 
                : '';
            if ($actionNameRes) mysqli_free_result($actionNameRes);

            if (strtolower($actionName) === 'create') {
                // Với Create: chỉ insert một dòng (resource_id NULL, org_id NULL)
                $allPermissions[] = [
                    'org_id' => NULL,
                    'action_id' => $baseActionId,
                    'resource_id' => 'NULL',
                    'resource_type_id' => $resourceTypeId,
                    'resource_type_version' => $version
                ];
                continue;
            }

            // Các action khác → kế thừa
            $childActions = getAllChildActions($baseActionId);
            $allActions   = array_merge([$baseActionId], $childActions);

            // Map với tất cả resources thuộc resource_type này
            $resQuery = "SELECT id, org_id FROM resources WHERE resource_type_id = $resourceTypeId";
            $resResult = query($conn, $resQuery);

            while ($resRow = mysqli_fetch_assoc($resResult)) {
                $resourceId = (int)$resRow['id'];
                $orgId      = (int)$resRow['org_id'];
                foreach ($allActions as $actionId) {
                    $allPermissions[] = [
                        'org_id' => $orgId,
                        'action_id' => $actionId,
                        'resource_id' => $resourceId,
                        'resource_type_id' => $resourceTypeId,
                        'resource_type_version' => $version
                    ];
                }
            }
            mysqli_free_result($resResult);
        }
        mysqli_free_result($resultSystemRole);

        // ===== B2: Quyền từ resource role =====
        $queryResourceRole = "
            SELECT 
                r.org_id,
                ra.action_id,
                r.id AS resource_id,
                r.resource_type_id,
                rt.version AS resource_type_version
            FROM user_resources ur
            JOIN resources r ON r.id = ur.resource_id
            JOIN resource_role_actions ra ON ra.resource_role_id = ur.resource_role_id
            JOIN resource_types rt ON rt.id = r.resource_type_id
            WHERE ur.user_id = $userID
        ";
        $resultResourceRole = query($conn, $queryResourceRole);

        while ($row = mysqli_fetch_assoc($resultResourceRole)) {
            $orgId          = (int)$row['org_id'];
            $resourceId     = (int)$row['resource_id'];
            $resourceTypeId = (int)$row['resource_type_id'];
            $version        = (int)$row['resource_type_version'];
            $baseActionId   = (int)$row['action_id'];

            $actionNameRes = query($conn, "SELECT name FROM actions WHERE id = $baseActionId LIMIT 1");
            $actionName = ($actionNameRes && mysqli_num_rows($actionNameRes) > 0) 
                ? mysqli_fetch_assoc($actionNameRes)['name'] 
                : '';
            if ($actionNameRes) mysqli_free_result($actionNameRes);

            if (strtolower($actionName) === 'create') {
                $allPermissions[] = [
                    'org_id' => 'NULL',
                    'action_id' => $baseActionId,
                    'resource_id' => 'NULL',
                    'resource_type_id' => $resourceTypeId,
                    'resource_type_version' => $version
                ];
                continue;
            }

            $childActions = getAllChildActions($baseActionId);
            $allActions   = array_merge([$baseActionId], $childActions);

            foreach ($allActions as $actionId) {
                $allPermissions[] = [
                    'org_id' => $orgId,
                    'action_id' => $actionId,
                    'resource_id' => $resourceId,
                    'resource_type_id' => $resourceTypeId,
                    'resource_type_version' => $version
                ];
            }
        }
        mysqli_free_result($resultResourceRole);

        // ===== Lọc trùng và insert =====
        $rowsToInsert = [];
        foreach ($allPermissions as $perm) {
            $actionId        = $perm['action_id'];
            $resourceId      = ($perm['resource_id'] !== null && $perm['resource_id'] !== 'NULL') ? $perm['resource_id'] : 'NULL';
            $resourceTypeId  = $perm['resource_type_id'];
            $orgId           = ($perm['org_id'] !== null && $perm['org_id'] !== 'NULL') ? $perm['org_id'] : 'NULL';
            $version         = $perm['resource_type_version'];

            $key = $actionId . "|" . $resourceId . "|" . $resourceTypeId . "|" . $orgId;
            if (!isset($seen[$key])) {
                $seen[$key] = true;
                $rowsToInsert[] = "($userID, $actionId, $resourceId, $resourceTypeId, $orgId, $version)";
            }
        }

        if (!empty($rowsToInsert)) {
            $values = implode(",\n", $rowsToInsert);
            $insertSQL = "
                INSERT INTO user_permission_snapshots 
                    (user_id, action_id, resource_id, resource_type_id, org_id, resource_type_version)
                VALUES
                    $values
            ";
            query($conn, $insertSQL);
        }
    }


    function hasPermission($userID, $actionName, $resourceId = null, $resourceTypeName = null) {
        global $conn;
        if(defined("IS_DEBUG") && IS_DEBUG) {
            return true;
        }

        $actionId = getActionID($actionName);
        if ($actionId === null) {
            return false;
        }

        // Nếu là action Create
        if (strtolower($actionName) === 'create') {
            if (empty($resourceTypeName)) {
                return false; // Không biết đang tạo loại resource nào
            }

            $typeData = getResourceTypeByName($resourceTypeName);
            if (!$typeData) return false;
            $resourceTypeId = (int)$typeData['id'];

            if (!checkVersionMatch($userID, $resourceTypeId)) {
                buildSnapshot($userID);
            }

            $query = "
                SELECT 1
                FROM user_permission_snapshots
                WHERE user_id = $userID
                AND action_id = $actionId
                AND resource_type_id = $resourceTypeId
                LIMIT 1
            ";
            $result = query($conn, $query);
            $allowed = ($result && mysqli_num_rows($result) > 0);
            if ($result) {
                mysqli_free_result($result);
            }
            return $allowed;
        }

        // Các action khác (View, Edit, Delete...) -> cần resourceId cụ thể
        if ($resourceId === null) {
            return false;
        }

        $resource = getResource($resourceId);
        if (!$resource) {
            return false;
        }

        $resourceTypeId = $resource['resource_type_id'];
        $orgId = $resource['org_id'];

        if (!checkVersionMatch($userID, $resourceTypeId)) {
            buildSnapshot($userID);
        }

        $query = "
            SELECT 1 
            FROM user_permission_snapshots
            WHERE user_id = $userID
            AND action_id = $actionId
            AND (
                    resource_id = $resourceId
                    OR (resource_type_id = $resourceTypeId AND org_id = $orgId AND resource_id IS NULL)
                )
            LIMIT 1
        ";
        $result = query($conn, $query);
        $allowed = ($result && mysqli_num_rows($result) > 0);
        if ($result) {
            mysqli_free_result($result);
        }
        return $allowed;
    }



    function getAccessibleResources($userID, $actionName, $resourceTypeName): array {
        global $conn;

        $resultList = [];

        $actionId = getActionID($actionName);
        if ($actionId === null) return $resultList;

        $rt = getResourceTypeByName($resourceTypeName);
        if (!$rt) return $resultList;
        $resourceTypeId = (int)$rt['id'];

        if (defined("IS_DEBUG") && IS_DEBUG) {
            if (!checkVersionMatch($userID, $resourceTypeId)) {
                buildSnapshot($userID);
            }

            // TRẢ VỀ BẢN GHI TỪ RESOURCES (giống nhánh thường)
            $sql = "
                SELECT DISTINCT r.*
                FROM user_permission_snapshots ups
                JOIN resources r ON r.id = ups.resource_id
                WHERE ups.user_id = $userID
                AND ups.action_id = $actionId
                AND ups.resource_type_id = $resourceTypeId
            ";
            $res = query($conn, $sql);
            if ($res) {
                while ($row = mysqli_fetch_assoc($res)) {
                    $resultList[$row['id']] = $row;
                }
                mysqli_free_result($res);
            }
            return array_values($resultList);
        }
        $resultList = array();

        $actionId = getActionID($actionName);
        if ($actionId === null) {
            return $resultList;
        }

        $resourceType = getResourceTypeByName($resourceTypeName);
        if (!$resourceType) {
            return $resultList;
        }
        $resourceTypeId = $resourceType['id'];

        if (!checkVersionMatch($userID, $resourceTypeId)) {
            buildSnapshot($userID);
        }

        $query = "
            SELECT DISTINCT resource_id, org_id
            FROM user_permission_snapshots
            WHERE user_id = $userID
            AND action_id = $actionId
            AND resource_type_id = $resourceTypeId
        ";
        $result = query($conn, $query);
        if (!$result) {
            return $resultList;
        }

        $directResourceIds = array();
        $orgScopeIds = array();
        while ($row = mysqli_fetch_assoc($result)) {
            if ($row['resource_id'] === null) {
                $orgScopeIds[] = (int)$row['org_id'];
            } else {
                $directResourceIds[] = (int)$row['resource_id'];
            }
        }
        mysqli_free_result($result);

        if (!empty($directResourceIds)) {
            $ids = implode(',', array_unique($directResourceIds));
            $queryDirect = "SELECT * FROM resources WHERE id IN ($ids)";
            $resDirect = query($conn, $queryDirect);
            while ($row = mysqli_fetch_assoc($resDirect)) {
                $resultList[$row['id']] = $row;
            }
            if ($resDirect) {
                mysqli_free_result($resDirect);
            }
        }

        if (!empty($orgScopeIds)) {
            $orgIds = implode(',', array_unique($orgScopeIds));
            $queryOrgScope = "
                SELECT * FROM resources 
                WHERE resource_type_id = $resourceTypeId AND org_id IN ($orgIds)
            ";
            $resOrgScope = query($conn, $queryOrgScope);
            while ($row = mysqli_fetch_assoc($resOrgScope)) {
                $resultList[$row['id']] = $row;
            }
            if ($resOrgScope) {
                mysqli_free_result($resOrgScope);
            }
        }

        return array_values($resultList);
    }

?>
