<?php 
    require_once "table_renderer.php";
    require_once "config/database.php";
    require_once "includes/access_control.php";

    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $tableName = "system_role_group_roles";
    $userId = $_SESSION['user']['id'] ?? null;
    $group_id = $_GET['group_id'] ?? null;
    if(!$group_id) {
        echo "<h2>Vui lòng chọn nhóm vai trò để xem dữ liệu.</h2>";
        exit;
    }

    if(!checkVersionMatch($userId, getResourceTypeByName('system_role_group_roles')['id'])){
        buildSnapshot($userId);
    }

    if (!$userId) {
        echo "<h2>Vui lòng đăng nhập để xem dữ liệu.</h2>";
        exit;
    }

    // Lấy danh sách resource mà user có quyền View (truyền tên, không truyền ID)
    $accessibleResources = getAccessibleResources($userId, 'View', 'system_role_group_roles');
    if (!empty($accessibleResources)) {
        $entityIds = array_column($accessibleResources, 'entity_id');
        if (!empty($entityIds)) {
            $entityIdList = implode(',', $entityIds);

            $query = "SELECT 
                        srgr.id AS id,
                        srg.name AS 'Tên nhóm vai trò',
                        sr.name AS 'Tên vai trò'
                        FROM system_role_group_roles srgr
                        JOIN system_role_groups srg ON srgr.system_role_group_id = srg.id
                        JOIN system_roles sr ON srgr.system_role_id = sr.id
                WHERE 1=1
                AND srgr.id IN ($entityIdList) AND srgr.system_role_group_id = $group_id";

            // Map alias tiếng Việt -> cột thật để filter/sort
            $columnMapping = [
                'id'                         => 'srgr.id',
                'Tên nhóm vai trò'          => 'srg.name',
                'Tên hành động'             => "sr.name"
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
        $canCreate = hasPermission($userId, 'Create', null, 'system_role_group_roles');
        $canAssign = hasPermission($userId, 'Assign', null, 'system_role_group_roles');
        $canEdit = hasPermission($userId, 'Edit', null, 'system_role_group_roles');
        $canDelete = hasPermission($userId, 'Delete', null, 'system_role_group_roles');
        $canViewDetails = hasPermission($userId, 'View', null, 'system_role_group_roles');
    } else {
        $canCreate = true;
        $canAssign = true;
        $canEdit = true;
        $canDelete = true;
        $canViewDetails = true;
    }

     // Lấy resourceTypeId để dùng trong form
    $buttonList = [];

    if ($canCreate) {
        $buttonList[] = [
            "btn_type" => "Create",
            "label"    => "Gán vai trò cho nhóm vai trò",
            "btn_url"  => "/pages/system_role_group_roles/form.php?group_id=" . $group_id .  "&redirectLink=" . urlencode($reloadLink)
        ];
    }

    if ($canAssign) {
        $buttonList[] = [
            "btn_type" => "Assign",
            "label"    => "Gán quan hệ nhóm vai trò",
            "btn_url"  => ""
        ];
    }

    if($canEdit) {
        $buttonList[] = [
            "btn_type" => "Edit",
            "label"    => "Chỉnh sửa",
            "btn_url"  => "",
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
