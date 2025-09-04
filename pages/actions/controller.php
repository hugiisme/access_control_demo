<?php 
    require_once "table_renderer.php";
    require_once "config/database.php";
    require_once "includes/access_control.php";

    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $tableName = "actions";
    $userId = $_SESSION['user']['id'] ?? null;

    if(!checkVersionMatch($userId, getResourceTypeByName('actions')['id'])){
        buildSnapshot($userId);
    }

    if (!$userId) {
        echo "<h2>Vui lòng đăng nhập để xem dữ liệu.</h2>";
        exit;
    }

    $accessibleResources = getAccessibleResources($userId, 'View', 'actions');

    if (!empty($accessibleResources)) {
        $entityIds = array_column($accessibleResources, 'entity_id');
        if (!empty($entityIds)) {
            $entityIdList = implode(',', $entityIds);

            $query = "WITH RECURSIVE action_tree AS (
                            SELECT a.id AS parent_id, a.id AS child_id
                            FROM actions a
                            UNION ALL
                            SELECT at.parent_id, ar.child_action_id
                            FROM action_tree at
                            JOIN action_relations ar ON at.child_id = ar.parent_action_id
                        )
                        SELECT 
                            p.id AS id,
                            p.name AS 'Tên hành động',
                            p.description AS 'Mô tả',
                            GROUP_CONCAT(DISTINCT c.name ORDER BY c.id SEPARATOR ', ') AS 'Các hành dộng con'
                        FROM actions p
                        LEFT JOIN action_tree t 
                            ON p.id = t.parent_id AND p.id <> t.child_id
                        LEFT JOIN actions c 
                            ON t.child_id = c.id
                        WHERE p.id IN ($entityIdList)
                        GROUP BY p.id, p.name, p.description
                        ORDER BY p.id
                    ";


            // Map alias tiếng Việt -> cột thật để filter/sort
            $columnMapping = [
                'id'                 => 'p.id',
                'Tên hành động'      => 'p.name',
                'Mô tả'              => 'p.description',
                'Các hành dộng con'  => 'children_names' // TODO: Lỗi nè
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
        $canCreate = hasPermission($userId, 'Create', null, 'actions');
        $canAssign = hasPermission($userId, 'Assign', null, 'actions');
        $canEdit = hasPermission($userId, 'Edit', null, 'actions');
        $canDelete = hasPermission($userId, 'Delete', null, 'actions');
        $canViewDetails = hasPermission($userId, 'Manage', null, 'actions');
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
            "label"    => "Tạo hành động mới",
            "btn_url"  => "/pages/actions/form.php?redirectLink=" . urlencode($reloadLink)
        ];
    }

    if ($canAssign) {
        $buttonList[] = [
            "btn_type" => "Assign",
            "label"    => "Gán quan hệ hành động",
            "btn_url"  => ""
        ];
    }

    if($canEdit) {
        $buttonList[] = [
            "btn_type" => "Edit",
            "label"    => "Chỉnh sửa",
            "btn_url"  => "/pages/actions/form.php?redirectLink=" . urlencode($reloadLink),
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
            "btn_url"  => "index.php?pageName=action_relations",
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
