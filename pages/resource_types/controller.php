<?php 
    require_once "table_renderer.php";
    require_once "config/database.php";
    require_once "includes/access_control.php";

    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $tableName = "resource_types";
    $userId = $_SESSION['user']['id'] ?? null;

    if(!checkVersionMatch($userId, getResourceTypeByName('resource_types')['id'])){
        buildSnapshot($userId);
    }

    if (!$userId) {
        echo "<h2>Vui lòng đăng nhập để xem dữ liệu.</h2>";
        exit;
    }

    $accessibleResources = getAccessibleResources($userId, 'View', 'resource_types');

    if (!empty($accessibleResources)) {
        $entityIds = array_column($accessibleResources, 'entity_id');
        if (!empty($entityIds)) {
            $entityIdList = implode(',', $entityIds);

            $query = "SELECT 
                    id,
                    name AS 'Tên loại tài nguyên',
                    version AS 'Phiên bản'
                    FROM resource_types
                WHERE 1=1
            ";
            // AND id IN ($entityIdList) -- Debug nên ko cần

            // Map alias tiếng Việt -> cột thật để filter/sort
            $columnMapping = [
                'id'                    => 'id',
                'Tên loại tài nguyên'   => 'name',
                'Phiên bản'             => 'version'
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
        $canCreate = hasPermission($userId, 'Create', null, 'resource_types');
        $canAssign = hasPermission($userId, 'Assign', null, 'resource_types');
        $canEdit = hasPermission($userId, 'Edit', null, 'resource_types');
        $canDelete = hasPermission($userId, 'Delete', null, 'resource_types');
        $canViewDetails = hasPermission($userId, 'Manage', null, 'resource_types');
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
            "label"    => "Tạo loại tài nguyên mới",
            "btn_url"  => "/pages/resource_types/form.php?redirectLink=" . urlencode($reloadLink)
        ];
    }

    if ($canAssign) {
        $buttonList[] = [
            "btn_type" => "Assign",
            "label"    => "Gán quan hệ loại tài nguyên",
            "btn_url"  => ""
        ];
    }

    if($canEdit) {
        $buttonList[] = [
            "btn_type" => "Edit",
            "label"    => "Chỉnh sửa",
            "btn_url"  => "/pages/resource_types/form.php?redirectLink=" . urlencode($reloadLink),
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
