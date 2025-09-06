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

    // 1) Xóa snapshot cũ
    deleteSnapshot($userID);

    // Nếu bật debug: nhét full actions x resources để dễ kiểm
    if (defined("IS_DEBUG") && IS_DEBUG) {
        $sql = "
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
        $ok = query($conn, $sql);
        if (!$ok) {
            error_log("buildSnapshot DEBUG insert error: " . mysqli_error($conn));
            error_log("SQL was: $sql");
        } else {
            error_log("buildSnapshot DEBUG inserted by CROSS JOIN");
        }
        return;
    }

    $allPermissions = [];
    $seen = [];

    // =========================
    // B1) Quyền từ SYSTEM ROLE
    // =========================
    // NOTE: join thêm actions để lấy action_name, khỏi query lẻ tẻ
    $querySystemRole = "
        SELECT 
            p.action_id,
            p.resource_type_id,
            rt.version AS resource_type_version,
            a.name AS action_name
        FROM user_system_roles usr
        JOIN system_roles sr              ON sr.id = usr.system_role_id
        JOIN system_role_permissions srp  ON srp.system_role_id = sr.id
        JOIN permissions p                ON p.id = srp.permission_id
        JOIN resource_types rt            ON rt.id = p.resource_type_id
        JOIN actions a                    ON a.id = p.action_id
        WHERE usr.user_id = $userID
    ";
    $rsSys = query($conn, $querySystemRole);
    if (!$rsSys) {
        error_log("buildSnapshot systemRole query fail: " . mysqli_error($conn));
    } else {
        error_log("buildSnapshot systemRole rows: " . mysqli_num_rows($rsSys));
    }

    while ($row = mysqli_fetch_assoc($rsSys)) {
        $baseActionId   = (int)$row['action_id'];
        $resourceTypeId = (int)$row['resource_type_id'];
        $version        = (int)$row['resource_type_version'];
        $actionName     = strtolower(trim($row['action_name']));

        if ($actionName === 'create') {
            // Create: global theo type (resource_id NULL, org_id NULL)
            $allPermissions[] = [
                'action_id' => $baseActionId,
                'resource_id' => null,
                'resource_type_id' => $resourceTypeId,
                'org_id' => null,
                'resource_type_version' => $version
            ];
            continue;
        }

        // Kế thừa hành động con
        $childActions = getAllChildActions($baseActionId);
        $allActions   = array_unique(array_merge([$baseActionId], $childActions));

        // Map xuống tất cả resources thuộc resource_type này
        $resQuery = "SELECT id, org_id FROM resources WHERE resource_type_id = $resourceTypeId";
        $resResult = query($conn, $resQuery);
        if (!$resResult) {
            error_log("buildSnapshot systemRole resources query fail (type=$resourceTypeId): " . mysqli_error($conn));
            continue;
        }
        $resCount = mysqli_num_rows($resResult);
        error_log("buildSnapshot systemRole map: type=$resourceTypeId resources=$resCount actions=" . count($allActions));

        while ($resRow = mysqli_fetch_assoc($resResult)) {
            $resourceId = (int)$resRow['id'];
            $orgId      = isset($resRow['org_id']) ? (int)$resRow['org_id'] : null;

            foreach ($allActions as $actionId) {
                $allPermissions[] = [
                    'action_id' => (int)$actionId,
                    'resource_id' => $resourceId,
                    'resource_type_id' => $resourceTypeId,
                    'org_id' => $orgId,
                    'resource_type_version' => $version
                ];
            }
        }
        mysqli_free_result($resResult);
    }
    if ($rsSys) mysqli_free_result($rsSys);

    // ===========================
    // B2) Quyền từ RESOURCE ROLE
    // ===========================
    $queryResourceRole = "
        SELECT 
            r.org_id,
            ra.action_id,
            r.id AS resource_id,
            r.resource_type_id,
            rt.version AS resource_type_version
        FROM user_resources ur
        JOIN resources r             ON r.id = ur.resource_id
        JOIN resource_role_actions ra ON ra.resource_role_id = ur.resource_role_id
        JOIN resource_types rt        ON rt.id = r.resource_type_id
        WHERE ur.user_id = $userID
    ";
    $rsRes = query($conn, $queryResourceRole);
    if (!$rsRes) {
        error_log("buildSnapshot resourceRole query fail: " . mysqli_error($conn));
    } else {
        error_log("buildSnapshot resourceRole rows: " . mysqli_num_rows($rsRes));
    }

    while ($row = mysqli_fetch_assoc($rsRes)) {
        $baseActionId   = (int)$row['action_id'];
        $resourceId     = (int)$row['resource_id'];
        $resourceTypeId = (int)$row['resource_type_id'];
        $orgId          = isset($row['org_id']) ? (int)$row['org_id'] : null;
        $version        = (int)$row['resource_type_version'];

        // Hành động gốc
        $allPermissions[] = [
            'action_id' => $baseActionId,
            'resource_id' => $resourceId,
            'resource_type_id' => $resourceTypeId,
            'org_id' => $orgId,
            'resource_type_version' => $version
        ];

        // Hành động con
        $childActions = getAllChildActions($baseActionId);
        foreach ($childActions as $childId) {
            $allPermissions[] = [
                'action_id' => (int)$childId,
                'resource_id' => $resourceId,
                'resource_type_id' => $resourceTypeId,
                'org_id' => $orgId,
                'resource_type_version' => $version
            ];
        }
    }
    if ($rsRes) mysqli_free_result($rsRes);

    // ===========================
    // Lọc trùng & INSERT
    // ===========================
    $rowsToInsert = [];
    $added = 0;
    foreach ($allPermissions as $perm) {
        $actionId        = (int)$perm['action_id'];
        $resourceTypeId  = (int)$perm['resource_type_id'];
        $version         = (int)$perm['resource_type_version'];

        // NULL handling cho SQL
        $resourceIdSql = ($perm['resource_id'] === null) ? "NULL" : (int)$perm['resource_id'];
        $orgIdSql      = ($perm['org_id'] === null) ? "NULL" : (int)$perm['org_id'];

        // Key để chống trùng
        $key = $actionId . "|" . $resourceIdSql . "|" . $resourceTypeId . "|" . $orgIdSql;
        if (!isset($seen[$key])) {
            $seen[$key] = true;
            $rowsToInsert[] = "($userID, $actionId, $resourceIdSql, $resourceTypeId, $orgIdSql, $version)";
            $added++;
        }
    }

    error_log("buildSnapshot collected permissions (deduped): $added");

    if (!empty($rowsToInsert)) {
        $values = implode(",\n", $rowsToInsert);
        $insertSQL = "
            INSERT INTO user_permission_snapshots 
                (user_id, action_id, resource_id, resource_type_id, org_id, resource_type_version)
            VALUES
                $values
        ";
        $ok = query($conn, $insertSQL);
        if (!$ok) {
            error_log("buildSnapshot insert error: " . mysqli_error($conn));
            // Cảnh báo: câu SQL có thể rất dài
            error_log("Failing SQL (truncated 2k chars): " . substr($insertSQL, 0, 2048));
        } else {
            error_log("buildSnapshot inserted " . count($rowsToInsert) . " rows for user $userID");
        }
    } else {
        error_log("buildSnapshot nothing to insert for user $userID");
    }
}



    function hasPermission($userID, $actionName, $resourceId = null, $resourceTypeName = null) {
    global $conn;

    if (defined("IS_DEBUG") && IS_DEBUG) {
        return true;
    }

    $actionId = getActionID($actionName);
    if ($actionId === null) {
        error_log("hasPermission: action '$actionName' not found.");
        return false;
    }

    // ===== CASE 1: Create =====
    if (strtolower($actionName) === 'create') {
        if (empty($resourceTypeName)) {
            error_log("hasPermission: create action but no resourceTypeName given.");
            return false;
        }

        $typeData = getResourceTypeByName($resourceTypeName);
        if (!$typeData) {
            error_log("hasPermission: resourceType '$resourceTypeName' not found.");
            return false;
        }
        $resourceTypeId = (int)$typeData['id'];

        if (!checkVersionMatch($userID, $resourceTypeId)) {
            error_log("hasPermission: version mismatch, rebuilding snapshot for user $userID.");
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
        if (!$result) {
            error_log("hasPermission: SQL error: " . mysqli_error($conn));
            error_log("SQL was: $query");
            return false;
        }
        $allowed = (mysqli_num_rows($result) > 0);
        mysqli_free_result($result);

        error_log("hasPermission: create check for user $userID on type $resourceTypeName = " . ($allowed ? "ALLOW" : "DENY"));
        return $allowed;
    }

    // ===== CASE 2: Other actions (View/Edit/Delete) =====
    if ($resourceId === null) {
        error_log("hasPermission: action '$actionName' requires resourceId but none given.");
        return false;
    }

    $resource = getResource($resourceId);
    if (!$resource) {
        error_log("hasPermission: resource $resourceId not found.");
        return false;
    }

    $resourceTypeId = (int)$resource['resource_type_id'];
    $orgId = $resource['org_id'] !== null ? (int)$resource['org_id'] : "NULL";

    if (!checkVersionMatch($userID, $resourceTypeId)) {
        error_log("hasPermission: version mismatch, rebuilding snapshot for user $userID.");
        buildSnapshot($userID);
    }

    $query = "
        SELECT 1 
        FROM user_permission_snapshots
        WHERE user_id = $userID
          AND action_id = $actionId
          AND (
                resource_id = $resourceId
                OR (resource_type_id = $resourceTypeId 
                    AND (org_id = $orgId OR org_id IS NULL) 
                    AND resource_id IS NULL)
              )
        LIMIT 1
    ";
    $result = query($conn, $query);
    if (!$result) {
        error_log("hasPermission: SQL error: " . mysqli_error($conn));
        error_log("SQL was: $query");
        return false;
    }
    $allowed = (mysqli_num_rows($result) > 0);
    mysqli_free_result($result);

    error_log("hasPermission: action '$actionName' on resource $resourceId (type $resourceTypeId, org $orgId) = " . ($allowed ? "ALLOW" : "DENY"));
    return $allowed;
}


function getAccessibleResources($userID, $actionName, $resourceTypeName): array {
    global $conn;
    $resultList = [];

    $actionId = getActionID($actionName);
    if ($actionId === null) {
        error_log("getAccessibleResources: action '$actionName' not found.");
        return $resultList;
    }

    $rt = getResourceTypeByName($resourceTypeName);
    if (!$rt) {
        error_log("getAccessibleResources: resourceType '$resourceTypeName' not found.");
        return $resultList;
    }
    $resourceTypeId = (int)$rt['id'];

    if (!checkVersionMatch($userID, $resourceTypeId)) {
        error_log("getAccessibleResources: version mismatch, rebuilding snapshot for user $userID.");
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
        error_log("getAccessibleResources: SQL error: " . mysqli_error($conn));
        error_log("SQL was: $query");
        return $resultList;
    }

    $directResourceIds = [];
    $orgScopeIds = [];
    $hasGlobalNull = false;

    while ($row = mysqli_fetch_assoc($result)) {
        if ($row['resource_id'] === null) {
            if ($row['org_id'] === null) {
                $hasGlobalNull = true; // toàn bộ resource_type
            } else {
                $orgScopeIds[] = (int)$row['org_id'];
            }
        } else {
            $directResourceIds[] = (int)$row['resource_id'];
        }
    }
    mysqli_free_result($result);

    // Direct resource permissions
    if (!empty($directResourceIds)) {
        $ids = implode(',', array_unique($directResourceIds));
        $queryDirect = "SELECT * FROM resources WHERE id IN ($ids)";
        $resDirect = query($conn, $queryDirect);
        if ($resDirect) {
            while ($row = mysqli_fetch_assoc($resDirect)) {
                $resultList[$row['id']] = $row;
            }
            mysqli_free_result($resDirect);
        } else {
            error_log("getAccessibleResources: SQL error direct fetch: " . mysqli_error($conn));
        }
    }

    // Org-wide scope
    if (!empty($orgScopeIds)) {
        $orgIds = implode(',', array_unique($orgScopeIds));
        $queryOrgScope = "
            SELECT * FROM resources 
            WHERE resource_type_id = $resourceTypeId AND org_id IN ($orgIds)
        ";
        $resOrgScope = query($conn, $queryOrgScope);
        if ($resOrgScope) {
            while ($row = mysqli_fetch_assoc($resOrgScope)) {
                $resultList[$row['id']] = $row;
            }
            mysqli_free_result($resOrgScope);
        } else {
            error_log("getAccessibleResources: SQL error org scope fetch: " . mysqli_error($conn));
        }
    }

    // Global (org_id IS NULL → nghĩa là tất cả resource trong type này)
    if ($hasGlobalNull) {
        $queryGlobal = "SELECT * FROM resources WHERE resource_type_id = $resourceTypeId";
        $resGlobal = query($conn, $queryGlobal);
        if ($resGlobal) {
            while ($row = mysqli_fetch_assoc($resGlobal)) {
                $resultList[$row['id']] = $row;
            }
            mysqli_free_result($resGlobal);
        } else {
            error_log("getAccessibleResources: SQL error global fetch: " . mysqli_error($conn));
        }
    }

    error_log("getAccessibleResources: user $userID, action '$actionName', type '$resourceTypeName' => " . count($resultList) . " resources");

    return array_values($resultList);
}

?>
