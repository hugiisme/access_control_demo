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

    $table_name = "user_orgs";
    $userId = $_SESSION['user']['id'] ?? null;

    if (!$userId) {
        echo "<h2>Vui lòng đăng nhập để xem dữ liệu.</h2>";
        exit;
    }

    $org_id = isset($_GET['org_id']) && is_numeric($_GET['org_id']) ? intval($_GET['org_id']) : null;
    if(!$org_id) {
        echo "<h2>Vui lòng chọn tổ chức để xem thành viên tổ chức.</h2>";
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
            // Chưa xử lý với người dùng trên quyền mình và bản thân
            // Ví dụ: ko được xóa bản thân hoặc người dùng có cấp cao hơn bản thân
            $query = "SELECT 
                            u.id AS id,
                            u.name AS 'Tên người dùng'
                        FROM users u
                        JOIN user_orgs uo ON uo.user_id = u.id
                        JOIN organizations o ON o.id = uo.org_id
                        WHERE 1=1
                    AND uo.id IN ($entityIdList)";
            
            if (isset($_GET['org_id']) && is_numeric($_GET['org_id'])) {
                $selected_org_id = intval($_GET['org_id']);
                $query .= " AND o.id = $selected_org_id";
            }
        }
    } else {
        $query = "SELECT 1 WHERE 1=0";
        debug_log("info", "Người dùng ID $userId không có quyền xem quan hệ người dùng nào." );
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
        $canAssignToOrg = hasPermission($user_id, "assign", null, $resource_type_id);
        $canAssignRoles = hasPermission($user_id, "assign_roles", null, $resource_type_id);
    } else {    
        $canCreate = true;
        $canEdit = true;
        $canDelete = true;
        $canAssignToOrg = true;
        $canAssignRoles = true;
    }
    $button_list = [];

    if ($canCreate) {
        $button_list[] = [
            "btn_type" => "Create",
            "label"    => "Tạo người dùng mới",
            "btn_url"  => "/pages/user_orgs/form.php?org_id=". $org_id . "&redirect_link=" . urlencode($reload_link),
            "placement" => "top",
            "btn_class" => "create-btn"
        ];
    }

    if($canEdit) {
        $button_list[] = [
            "btn_type" => "Edit",
            "label"    => "Chỉnh sửa",
            "btn_url"  => "/pages/user_orgs/form.php?org_id=". $org_id ,
            "check_action" => 'Edit',
            "placement" => "table",
            "btn_class" => "edit-btn"
        ];
    }

    if($canDelete){
        $button_list[] = [
            "btn_type" => "Delete",
            "label"    => "Xóa người dùng khỏi tổ chức",
            "btn_url"  => "/includes/delete.php",
            "confirm"  => "Bạn có chắc chắn muốn xóa",
            "check_action" => 'Delete',
            "placement" => "table",
            "btn_class" => "delete-btn"
        ];
    }

    if($canAssignToOrg){
        $button_list[] = [
            "btn_type" => "Assign",
            "label"    => "Thêm người dùng vào tổ chức",
            "btn_url"  => "/pages/user_orgs/assign_to_org.php?org_id=". $org_id . "&redirect_link=" . urlencode($reload_link),
            "placement" => "top",
            "btn_class" => "assign-btn"
        ];
    }
    if($canAssignRoles){
        $button_list[] = [
            "btn_type" => "AssignRoles",
            "label"    => "Phân vai trò",
            "btn_url"  => "/pages/user_orgs/assign_roles.php?org_id=". $org_id,
            "placement" => "table",
            "btn_class" => "details-btn"
        ];
    }
    

?>
