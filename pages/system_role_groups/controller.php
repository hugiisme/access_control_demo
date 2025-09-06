<?php 
    require_once "table_renderer.php";
    require_once "config/database.php";
    require_once "includes/access_control.php";

    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $tableName = "system_role_groups";
    $userId = $_SESSION['user']['id'] ?? null;
    $org_id = $_GET['orgID'] ?? null;
    if(!$org_id) {
        echo "<h2>Vui lòng chọn tổ chức để xem dữ liệu.</h2>";
        exit;
    }

    if(!checkVersionMatch($userId, getResourceTypeByName('system_role_groups')['id'])){
        buildSnapshot($userId);
    }

    if (!$userId) {
        echo "<h2>Vui lòng đăng nhập để xem dữ liệu.</h2>";
        exit;
    }

    // Lấy danh sách resource mà user có quyền View (truyền tên, không truyền ID)
    $accessibleResources = getAccessibleResources($userId, 'View', 'system_role_groups');
    if (!empty($accessibleResources)) {
        $entityIds = array_column($accessibleResources, 'entity_id');
        if (!empty($entityIds)) {
            $entityIdList = implode(',', $entityIds);

            $query = "SELECT 
                srg.id AS id,
                srg.name AS 'Tên nhóm vai trò',
                srg.description AS 'Mô tả',
                o.name AS 'Tổ chức',
                srgp.name AS 'Nhóm vai trò cha'
            FROM system_role_groups srg
            LEFT JOIN system_role_groups srgp ON srg.parent_group_id = srgp.id
            JOIN organizations o ON srg.org_id = o.id
            WHERE 1=1
            AND srg.id IN ($entityIdList) AND srg.org_id = $org_id";

            // Map alias tiếng Việt -> cột thật để filter/sort
            $columnMapping = [
                'id'                => 'srg.id',
                'Tên nhóm vai trò'  => 'srg.name',
                'Tên tổ chức cha'   => 'srg.description',
                'Tổ chức'           => 'o.name',
                'Nhóm vai trò cha'  => 'srgp.name'
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
        $canCreate = true;
        $canAssign = false;
        $canEdit = true;
        $canDelete = true;
        $canViewDetails = true;
        // $canCreate = hasPermission($userId, 'Create', null, 'system_role_groups');
        // $canAssign = hasPermission($userId, 'Assign', null, 'system_role_groups');
        // $canEdit = hasPermission($userId, 'Edit', null, 'system_role_groups');
        // $canDelete = hasPermission($userId, 'Delete', null, 'system_role_groups');
        // $canViewDetails = hasPermission($userId, 'View', null, 'system_role_groups');
    } else {
        $canCreate = true;
        $canAssign = false;
        $canEdit = true;
        $canDelete = true;
        $canViewDetails = true;
    }

     // Lấy danh sách nhóm vai trò cha để hiển thị trong form
    $buttonList = [];

    if ($canCreate) {
        $buttonList[] = [
            "btn_type" => "Create",
            "label"    => "Tạo nhóm vai trò mới",
            "btn_url"  => "/pages/system_role_groups/form.php?org_id=" . $org_id .  "&redirectLink=" . urlencode($reloadLink)
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
            "btn_url"  => "index.php?pageName=system_role_group_details",
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
