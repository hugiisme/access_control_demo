<?php 
    require_once "table_renderer.php";
    require_once "config/database.php";
    require_once "includes/access_control.php";

    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $tableName = "org_permissions";
    $userId = $_SESSION['user']['id'] ?? null;
    $org_id = $_GET['orgID'] ?? null;
    if(!$org_id) {
        echo "<h2>Vui lòng chọn tổ chức để xem dữ liệu.</h2>";
        exit;
    }

    if(!checkVersionMatch($userId, getResourceTypeByName('org_permissions')['id'])){
        buildSnapshot($userId);
    }

    if (!$userId) {
        echo "<h2>Vui lòng đăng nhập để xem dữ liệu.</h2>";
        exit;
    }

    // Lấy danh sách resource mà user có quyền View (truyền tên, không truyền ID)
    $accessibleResources = getAccessibleResources($userId, 'View', 'org_permissions');
    if (!empty($accessibleResources)) {
        $entityIds = array_column($accessibleResources, 'entity_id');
        if (!empty($entityIds)) {
            $entityIdList = implode(',', $entityIds);

            $query = "SELECT
                    op.id AS 'id', 
                    a.name AS 'Tên hành động',
                    rt.name AS 'Tên loại tài nguyên',
                    o.name AS 'Tên tổ chức'
                FROM org_permissions op
                JOIN organizations o ON op.org_id = o.id
                JOIN permissions p ON OP.permission_id = p.id 
                JOIN actions a ON p.action_id = a.id 
                JOIN resource_types rt ON p.resource_type_id = rt.id
                WHERE 1=1
                AND op.id IN ($entityIdList) AND op.org_id = $org_id";

            // Map alias tiếng Việt -> cột thật để filter/sort
            $columnMapping = [
                'id'                => 'op.id',
                'Tên hành động'      => 'a.name',
                'Tên loại tài nguyên'            => 'rt.name',
                'Tên tổ chức'          => 'o.name'
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
        $canCreate = hasPermission($userId, 'Create', null, 'org_permissions');
        $canAssign = hasPermission($userId, 'Assign', null, 'org_permissions');
        $canEdit = hasPermission($userId, 'Edit', null, 'org_permissions');
        $canDelete = hasPermission($userId, 'Delete', null, 'org_permissions');
        $canViewDetails = hasPermission($userId, 'View', null, 'org_permissions');
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
            "label"    => "Thêm quyền mới cho tổ chức này",
            "btn_url"  => "/pages/org_permissions/form.php?org_id=" . $org_id .  "&redirectLink=" . urlencode($reloadLink)
        ];
    }

    if ($canAssign) {
        $buttonList[] = [
            "btn_type" => "Assign",
            "label"    => "Gán quan hệ quyền",
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
