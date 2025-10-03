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

    $table_name = "system_roles";
    $userId = $_SESSION['user']['id'] ?? null;

    if (!$userId) {
        echo "<h2>Vui lòng đăng nhập để xem dữ liệu.</h2>";
        exit;
    }

    $org_id = isset($_GET['org_id']) && is_numeric($_GET['org_id']) ? intval($_GET['org_id']) : null;
    if(!$org_id) {
        echo "<h2>Vui lòng chọn tổ chức để xem vai trò.</h2>";
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
                    sr.id AS id,
                    sr.name AS 'Tên vai trò',
                    sr.description AS 'Mô tả',
                    sr.level AS 'Cấp vai trò',
                    sr.available_slots AS 'Số lượng slot trống'
                    FROM system_roles sr
                    JOIN organizations o ON sr.org_id = o.id
                    WHERE 1=1
                AND sr.id IN ($entityIdList)
                AND o.id = $org_id";
            
            if (isset($_GET['org_id']) && is_numeric($_GET['org_id'])) {
                $selected_org_id = intval($_GET['org_id']);
                $query .= " AND o.id = $selected_org_id";
            }
        }
    } else {
        $query = "SELECT 1 WHERE 1=0";
        debug_log("info", "Người dùng ID $userId không có quyền xem vai trò nào." );
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
    } else {    
        $canCreate = true;
        $canEdit = true;
        $canDelete = true;
    }
    $button_list = [];

    if ($canCreate) {
        $button_list[] = [
            "btn_type" => "Create",
            "label"    => "Tạo Vai trò mới",
            "btn_url"  => "/pages/system_roles/form.php?org_id=". $org_id . "&redirect_link=" . urlencode($reload_link),
            "placement" => "top",
            "btn_class" => "create-btn"
        ];
    }

    if($canEdit) {
        $button_list[] = [
            "btn_type" => "Edit",
            "label"    => "Chỉnh sửa",
            "btn_url"  => "/pages/system_roles/form.php?org_id=". $org_id,
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

?>
