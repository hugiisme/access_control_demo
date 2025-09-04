<?php 
    require_once "table_renderer.php";
    require_once "config/database.php";
    require_once "includes/access_control.php";

    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $tableName = "organizations";
    $userId = $_SESSION['user']['id'] ?? null;

    if (!$userId) {
        echo "<h2>Vui lòng đăng nhập để xem dữ liệu.</h2>";
        exit;
    }

    // Lấy danh sách resource mà user có quyền View (truyền tên, không truyền ID)
    $accessibleResources = getAccessibleResources($userId, 'View', 'organizations');

    if (!empty($accessibleResources)) {
        $entityIds = array_column($accessibleResources, 'entity_id');
        if (!empty($entityIds)) {
            $entityIdList = implode(',', $entityIds);

            $query = "SELECT 
                    o.id AS id, 
                    o.name AS 'Tên tổ chức',
                    org.name AS 'Tên tổ chức cha',
                    o.org_level AS 'Cấp độ tổ chức',
                    ot.name AS 'Loại tổ chức'
                FROM organizations o
                LEFT JOIN organizations org ON o.parent_org_id = org.id
                LEFT JOIN org_types ot ON ot.id = o.org_type_id
                JOIN user_orgs uo ON uo.org_id = o.id AND uo.user_id = $userId
                WHERE 1=1
                AND o.id IN ($entityIdList)";

            // Map alias tiếng Việt -> cột thật để filter/sort
            $columnMapping = [
                'id'                => 'o.id',
                'Tên tổ chức'       => 'o.name',
                'Tên tổ chức cha'   => 'org.name',
                'Cấp độ tổ chức'    => 'o.org_level',
                'Loại tổ chức'      => 'ot.name'
            ];
        }
    } else {
        $query = "SELECT 1 WHERE 1=0";
    }

    $rowsPerPage = 10;
    $currentPage = $_GET['page'] ?? 1;
    $totalResults = totalResults($conn, $query);
    $result = query($conn, $query); // chạy query gốc để lấy fieldNames
    $totalPages = ceil($totalResults / $rowsPerPage);
    $reloadLink = $_SERVER['REQUEST_URI'];

    // Xác định trạng thái các nút dựa trên quyền
    if (defined("IS_DEBUG") && !IS_DEBUG) {
        $canCreate = hasPermission($userId, 'Create', null, 'organizations');
        $canAssign = hasPermission($userId, 'Assign', null, 'organizations');
        $canEdit = hasPermission($userId, 'Edit', null, 'organizations');
        $canDelete = hasPermission($userId, 'Delete', null, 'organizations');
        $canViewDetails = hasPermission($userId, 'View', null, 'organizations');
    } else {
        $canCreate = false;
        $canAssign = true;
        $canEdit = true;
        $canDelete = true;
        $canViewDetails = true;
    }
    $buttonList = [];

    if ($canCreate) {
        $buttonList[] = [
            "btn_type" => "Create",
            "label"    => "Tạo Tổ chức mới",
            "btn_url"  => "/pages/organizations/form.php?redirectLink=" . urlencode($reloadLink)
        ];
    }

    if ($canAssign) {
        $buttonList[] = [
            "btn_type" => "Assign",
            "label"    => "Gán quan hệ Tổ chức",
            "btn_url"  => "/pages/organizations/assign.php?redirectLink=" . urlencode($reloadLink)
        ];
    }

    if($canEdit) {
        $buttonList[] = [
            "btn_type" => "Edit",
            "label"    => "Chỉnh sửa",
            "btn_url"  => "/pages/organizations/form.php",
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
            "btn_url"  => "index.php?pageName=org_details",
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
