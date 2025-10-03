<?php 
    require_once __DIR__ . "/../../includes/table_renderer.php";
    require_once __DIR__ . "/../../config/database.php";
    require_once __DIR__ . "/../../includes/access_control.php";
    require_once __DIR__ . "/../../includes/notifications/notify.php";
    require_once __DIR__ . "/../../includes/helper_function.php";
    require_once __DIR__ . "/../../includes/query_helper.php";


    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $table_name = "system_role_groups";
    $userId = $_SESSION['user']['id'] ?? null;

    if (!$userId) {
        echo "<h2>Vui lòng đăng nhập để xem dữ liệu.</h2>";
        exit;
    }

    $org_id = isset($_GET['org_id']) && is_numeric($_GET['org_id']) ? intval($_GET['org_id']) : null;
    if(!$org_id) {
        echo "<h2>Vui lòng chọn tổ chức để xem nhóm vai trò.</h2>";
        exit;
    }

    // Lấy danh sách resource mà user có quyền View (truyền tên, không truyền ID)
    $action_id = mysqli_fetch_assoc(getActionByName('View'))['id'] ?? null;
    $resource_type_id = mysqli_fetch_assoc(getResourceTypeByName($table_name))['id'] ?? null;
    $accessibleResources = getAccesibleResources($userId, $action_id, $resource_type_id);
    
    if (!empty($accessibleResources)) {
        $entityIds = array_column($accessibleResources, 'entity_id');
        if (!empty($entityIds)) {
            $entityIdList = implode(',', $entityIds);
            // TODO: fix
            // Việt hóa cấp vai trò
            $query = "SELECT 
                    srg.id AS id,
                    srg.name AS 'Tên nhóm vai trò',
                    srg.description AS 'Mô tả',
                    srgp.name AS 'Nhóm vai trò cha'
                    FROM system_role_groups srg
                    LEFT JOIN system_role_groups srgp ON srgp.id = srg.parent_group_id
                    JOIN organizations o ON srg.org_id = o.id
                    WHERE 1=1
                AND srg.id IN ($entityIdList)";
            
            if (isset($_GET['org_id']) && is_numeric($_GET['org_id'])) {
                $selected_org_id = intval($_GET['org_id']);
                $query .= " AND o.id = $selected_org_id";
            }
        }
    } else {
        $query = "SELECT 1 WHERE 1=0";
        debug_log("info", "Người dùng ID $userId không có quyền xem nhóm vai trò nào." );
    }

    $rows_per_page = 10;
    $current_page = $_GET['page'] ?? 1;
    $total_results = total_results($conn, $query);
    $result = query($query);
    $total_pages = ceil($total_results / $rows_per_page);
    $reload_link = $_SERVER['REQUEST_URI'];

    // Xác định trạng thái các nút dựa trên quyền
    if (defined("IS_DEBUG") && !IS_DEBUG) {
        $canCreate = hasPermission($userId, 'create', null, $resource_type_id);
        $canEdit = true;
        $canDelete = true;
        $canManageRoles = hasPermission($userId, 'Manage', null, "system_role_group_roles");
    } else {    
        $canCreate = true;
        $canEdit = true;
        $canDelete = true;
        $canManageRoles = true;
    }
    $button_list = [];

    if ($canCreate) {
        $button_list[] = [
            "btn_type" => "Create",
            "label"    => "Tạo Nhóm vai trò mới",
            "btn_url"  => "/pages/system_role_groups/form.php?org_id=". $org_id . "&redirect_link=" . urlencode($reload_link),
            "placement" => "top",
            "btn_class" => "create-btn"
        ];
    }

    if($canEdit) {
        $button_list[] = [
            "btn_type" => "Edit",
            "label"    => "Chỉnh sửa",
            "btn_url"  => "/pages/system_role_groups/form.php",
            "check_action" => 'Edit',
            "placement" => "table",
            "btn_class" => "edit-btn"
        ];
    }

    if($canDelete){
        $button_list[] = [
            "btn_type" => "Delete",
            "label"    => "Xóa",
            "btn_url"  => "/includes/delete.php",
            "confirm"  => "Bạn có chắc chắn muốn xóa",
            "check_action" => 'Delete',
            "placement" => "table",
            "btn_class" => "delete-btn"
        ];
    }
    if($canManageRoles) {
        $button_list[] = [
            "btn_type" => "Details",
            "label"    => "Quản lý vai trò",
            "btn_url"  => "/pages/system_role_groups/manage_roles.php?org_id=" . $org_id,
            // "check_action" => 'Manage', // TODO: fix tạm bỏ qua
            "placement" => "table",
            "btn_class" => "details-btn"
        ];
    }

?>
