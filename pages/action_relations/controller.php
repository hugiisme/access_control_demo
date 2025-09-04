<?php 
    require_once "table_renderer.php";
    require_once "config/database.php";
    require_once "includes/access_control.php";

    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $tableName = "action_relations";
    $userId = $_SESSION['user']['id'] ?? null;

    if(!checkVersionMatch($userId, getResourceTypeByName('action_relations')['id'])){
        buildSnapshot($userId);
    }

    if (!$userId) {
        echo "<h2>Vui lòng đăng nhập để xem dữ liệu.</h2>";
        exit;
    }

    $accessibleResources = getAccessibleResources($userId, 'View', 'action_relations');

    if (!empty($accessibleResources)) {
        $entityIds = array_column($accessibleResources, 'entity_id');
        if (!empty($entityIds)) {
            $entityIdList = implode(',', $entityIds);

            $query = "
                    SELECT 
                        ar.id,
                        parent.name AS parent_action_name,
                        child.name AS child_action_name
                    FROM action_relations ar
                    JOIN actions parent ON ar.parent_action_id = parent.id
                    JOIN actions child ON ar.child_action_id = child.id
                    WHERE parent.id IN ($entityIdList) OR child.id IN ($entityIdList)
                    ORDER BY ar.id
                    ";


            // Map alias tiếng Việt -> cột thật để filter/sort
            $columnMapping = [
                
            ];
        }
    } else {
        $query = "SELECT 1 WHERE 1=0";
    }

    $rowsPerPage = 10;
    $currentPage = $_GET['page'] ?? 1;
    $totalResults = totalResults($conn, $query);
    $result = query($conn, $query);
    $totalPages = ceil($totalResults / $rowsPerPage);
    $reloadLink = $_SERVER['REQUEST_URI'];

    // Xác định trạng thái các nút dựa trên quyền
    if (defined("IS_DEBUG") && !IS_DEBUG) {
        $canCreate = hasPermission($userId, 'Create', null, 'action_relations');
        $canAssign = hasPermission($userId, 'Assign', null, 'action_relations');
        $canEdit = hasPermission($userId, 'Edit', null, 'action_relations');
        $canDelete = hasPermission($userId, 'Delete', null, 'action_relations');
        $canViewDetails = hasPermission($userId, 'Manage', null, 'action_relations');
    } else {
        $canCreate = true;
        $canAssign = true;
        $canEdit = true;
        $canDelete = true;
        $canViewDetails = true;
    }

    $buttonList = [];

    if ($canCreate) {
        $buttonList[] = [
            "btn_type" => "Create",
            "label"    => "Tạo quan hệ hành động mới",
            "btn_url"  => "/pages/action_relations/form.php?redirectLink=" . urlencode($reloadLink)
        ];
    }

    if ($canAssign) {
        $buttonList[] = [
            "btn_type" => "Assign",
            "label"    => "Gán quan hệ cho quan hệ hành động",
            "btn_url"  => ""
        ];
    }

    if($canEdit) {
        $buttonList[] = [
            "btn_type" => "Edit",
            "label"    => "Chỉnh sửa",
            "btn_url"  => "/pages/action_relations/form.php?redirectLink=" . urlencode($reloadLink),
            "check_action" => 'Edit'
        ];
    }

    if($canDelete){
        $buttonList[] = [
            "btn_type" => "Delete",
            "label"    => "Xóa",
            "btn_url"  => "/includes/delete.php",
            "confirm"  => true,
            "check_action" => 'Delete'
        ];
    }
    
    if($canViewDetails) {
        $buttonList[] = [
            "btn_type" => "Details",
            "label"    => "Chi tiết",
            "btn_url"  => "",
            "check_action" => 'View'
        ];
    }

    foreach ($buttonList as $button) {
        if ($button['btn_type'] === 'Create') {
            $createButton = $button;
        }
        if ($button['btn_type'] === 'Assign') {
            $assignButton = $button;
        }
    }


?>
