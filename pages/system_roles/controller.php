<?php 
    require_once "table_renderer.php";
    require_once "config/database.php";
    require_once "includes/access_control.php";

    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $tableName = "system_roles";
    $userId = $_SESSION['user']['id'] ?? null;
    $org_id = $_GET['orgID'] ?? null;
    if(!$org_id) {
        echo "<h2>Vui lòng chọn tổ chức để xem dữ liệu.</h2>";
        exit;
    }

    if(!checkVersionMatch($userId, getResourceTypeByName('system_roles')['id'])){
        buildSnapshot($userId);
    }

    if (!$userId) {
        echo "<h2>Vui lòng đăng nhập để xem dữ liệu.</h2>";
        exit;
    }

    // Lấy danh sách resource mà user có quyền View (truyền tên, không truyền ID)
    $accessibleResources = getAccessibleResources($userId, 'View', 'system_roles');
    if (!empty($accessibleResources)) {
        $entityIds = array_column($accessibleResources, 'entity_id');
        if (!empty($entityIds)) {
            $entityIdList = implode(',', $entityIds);

            $query = "SELECT 
                        sr.id AS id,
                        sr.name AS 'Tên vai trò',
                        sr.description AS 'Mô tả',
                        o.name AS 'Tổ chức',
                        sr.level AS 'Cấp độ vai trò',
                        sr.available_slots AS 'Số lượng slot còn trống'
                    FROM system_roles sr
                    JOIN organizations o ON sr.org_id = o.id
                WHERE 1=1
                AND sr.id IN ($entityIdList) AND sr.org_id = $org_id";

            // Map alias tiếng Việt -> cột thật để filter/sort
            $columnMapping = [
                'id'                => 'sr.id',
                'Tên vai trò'      => 'sr.name',
                'Mô tả'            => 'sr.description',
                'Tổ chức'          => 'o.name',
                'Cấp độ vai trò'   => 'sr.level',
                'Số lượng slot còn trống' => 'sr.available_slots'
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
        $canCreate = hasPermission($userId, 'Create', null, 'system_roles');
        $canAssign = hasPermission($userId, 'Assign', null, 'system_roles');
        $canEdit = hasPermission($userId, 'Edit', null, 'system_roles');
        $canDelete = hasPermission($userId, 'Delete', null, 'system_roles');
        $canViewDetails = hasPermission($userId, 'View', null, 'system_roles');
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
            "label"    => "Tạo Vai trò mới",
            "btn_url"  => "/pages/system_roles/form.php?org_id=" . $org_id .  "&redirectLink=" . urlencode($reloadLink)
        ];
    }

    if ($canAssign) {
        $buttonList[] = [
            "btn_type" => "Assign",
            "label"    => "Gán quan hệ vai trò",
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
