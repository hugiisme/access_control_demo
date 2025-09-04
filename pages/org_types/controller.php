<?php 
    require_once "table_renderer.php";
    require_once "config/database.php";
    require_once "includes/access_control.php";

    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $tableName = "org_types";
    $userId = $_SESSION['user']['id'] ?? null;

    if (!$userId) {
        echo "<h2>Vui lòng đăng nhập để xem dữ liệu.</h2>";
        exit;
    }

    // Lấy danh sách resource mà user có quyền View (truyền tên, không truyền ID)
    $accessibleResources = getAccessibleResources($userId, 'View', 'org_types');

    if (!empty($accessibleResources)) {
        $entityIds = array_column($accessibleResources, 'entity_id');
        if (!empty($entityIds)) {
            $entityIdList = implode(',', $entityIds);

            $query = "SELECT 
                    ot.id AS id, 
                    ot.name AS 'Tên loại tổ chức'
                FROM org_types ot
                WHERE 1=1
                AND ot.id IN ($entityIdList)";
            $columnMapping = [
                'id' => 'ot.id',
                'Tên loại tổ chức' => 'ot.name'
            ];
        } else {
            // Không có entity_id nào → query rỗng để tránh IN ()
            $query = "SELECT 1 WHERE 1=0";
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
    if(defined("IS_DEBUG") && !IS_DEBUG) {
        $canCreate = hasPermission($userId, 'Create', null, 'org_types');
        $canAssign = hasPermission($userId, 'Assign', null, 'org_types');
        $canEdit = hasPermission($userId, 'Edit', null, 'org_types');
        $canDelete = hasPermission($userId, 'Delete', null, 'org_types');
        $canViewDetails = hasPermission($userId, 'View', null, 'org_types');
    } else {
        $canCreate = true;
        $canAssign = false;
        $canEdit = true;
        $canDelete = true;
        $canViewDetails = false;
    }
    

    $buttonList = [];

    if ($canCreate) {
        $buttonList[] = [
            "btn_type" => "Create",
            "label"    => "Tạo Loại tổ chức mới",
            "btn_url"  => "/pages/org_types/form.php?redirectLink=" . urlencode($reloadLink)
        ];
    }

    if ($canAssign) {
        $buttonList[] = [
            "btn_type" => "Assign",
            "label"    => "Gán quan hệ Loại tổ chức",
            "btn_url"  => "/pages/org_types/assign.php?redirectLink=" . urlencode($reloadLink)
        ];
    }

    if($canEdit) {
        $buttonList[] = [
            "btn_type" => "Edit",
            "label"    => "Chỉnh sửa",
            "btn_url"  => "/pages/org_types/form.php",
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
