<?php 
    require_once "table_renderer.php";
    require_once "config/database.php";
    require_once "includes/access_control.php";

    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $tableName = "user_orgs";
    $userId = $_SESSION['user']['id'] ?? null;
    $org_id = $_GET['orgID'] ?? null;
    if(!$org_id) {
        echo "<h2>Vui lòng chọn tổ chức để xem dữ liệu.</h2>";
        exit;
    }

    if(!checkVersionMatch($userId, getResourceTypeByName('user_orgs')['id'])){
        buildSnapshot($userId);
    }

    if (!$userId) {
        echo "<h2>Vui lòng đăng nhập để xem dữ liệu.</h2>";
        exit;
    }

    // Lấy danh sách resource mà user có quyền View (truyền tên, không truyền ID)
    $accessibleResources = getAccessibleResources($userId, 'View', 'user_orgs');
    if (!empty($accessibleResources)) {
        $entityIds = array_column($accessibleResources, 'entity_id');
        if (!empty($entityIds)) {
            $entityIdList = implode(',', $entityIds);
            $query = "SELECT 
                        u.id AS id,
                        u.name AS 'Tên người dùng',
                        o.name AS 'Tổ chức'
                    FROM user_orgs uo
                    LEFT JOIN users u ON u.id = uo.user_id
                    LEFT JOIN organizations o ON uo.org_id = o.id
                WHERE 1=1
                AND uo.id IN ($entityIdList) AND uo.org_id = $org_id";

            // Map alias tiếng Việt -> cột thật để filter/sort
            $columnMapping = [
                'id'                => 'u.id',
                'Tên người dùng'    => 'u.name',
                'Tổ chức'           => 'o.name'
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
        $canCreate = hasPermission($userId, 'Create', null, 'user_orgs');
        $canAssign = hasPermission($userId, 'Assign', null, 'user_orgs');
        $canEdit = hasPermission($userId, 'Edit', null, 'user_orgs');
        $canDelete = hasPermission($userId, 'Delete', null, 'user_orgs');
        $canViewDetails = hasPermission($userId, 'View', null, 'user_orgs');
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
            "label"    => "Tạo người dùng mới",
            "btn_url"  => "/pages/user_orgs/form.php?org_id=" . $org_id .  "&redirectLink=" . urlencode($reloadLink)
        ];
    }

    if ($canAssign) {
        $buttonList[] = [
            "btn_type" => "Assign",
            "label"    => "Gán quan hệ người dùng",
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
