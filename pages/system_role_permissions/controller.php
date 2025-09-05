<?php 
    require_once "table_renderer.php";
    require_once "config/database.php";
    require_once "includes/access_control.php";

    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $tableName = "system_role_permissions";
    $userId = $_SESSION['user']['id'] ?? null;
    $role_id = $_GET['id'] ?? null;
    if(!$role_id) {
        echo "<h2>Vui lòng chọn vai trò để xem dữ liệu.</h2>";
        exit;
    }

    if(!checkVersionMatch($userId, getResourceTypeByName('system_role_permissions')['id'])){
        buildSnapshot($userId);
    }

    if (!$userId) {
        echo "<h2>Vui lòng đăng nhập để xem dữ liệu.</h2>";
        exit;
    }

    // Lấy danh sách resource mà user có quyền View (truyền tên, không truyền ID)
    $accessibleResources = getAccessibleResources($userId, 'View', 'system_role_permissions');
    if (!empty($accessibleResources)) {
        $entityIds = array_column($accessibleResources, 'entity_id');
        if (!empty($entityIds)) {
            $entityIdList = implode(',', $entityIds);

            $query = "SELECT 
                            srp.id AS 'id',
                            sr.name AS 'Tên vai trò',
                            a.name AS 'Tên hành động',
                            rt.name AS 'Tên loại tài nguyên'
                        FROM system_role_permissions srp
                        JOIN system_roles sr ON srp.system_role_id = sr.id
                        JOIN permissions p ON srp.permission_id = p.id 
                        JOIN actions a ON p.action_id = a.id 
                        JOIN resource_types rt ON p.resource_type_id = rt.id
                WHERE 1=1
                AND srp.id IN ($entityIdList) AND srp.system_role_id = $role_id";

            // Map alias tiếng Việt -> cột thật để filter/sort
            $columnMapping = [
                'id'                        => 'srp.id',
                'Tên vai trò'               => 'sr.name',
                'Tên hành động'             => "a.name",
                'Tên loại tài nguyên'       => "rt.name"
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
        $canCreate = hasPermission($userId, 'Create', null, 'system_role_permissions');
        $canAssign = hasPermission($userId, 'Assign', null, 'system_role_permissions');
        $canEdit = hasPermission($userId, 'Edit', null, 'system_role_permissions');
        $canDelete = hasPermission($userId, 'Delete', null, 'system_role_permissions');
        $canViewDetails = hasPermission($userId, 'View', null, 'system_role_permissions');
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
            "label"    => "Gán quyền cho nhóm vai trò",
            "btn_url"  => "/pages/system_role_permissions/form.php?role_id=" . $role_id .  "&redirectLink=" . urlencode($reloadLink)
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
