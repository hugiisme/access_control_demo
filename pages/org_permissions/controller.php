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

    $table_name = "organizations";
    $userId = $_SESSION['user']['id'] ?? null;

    if (!$userId) {
        echo "<h2>Vui lòng đăng nhập để xem dữ liệu.</h2>";
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
            // LEFT JOIN user_orgs uo ON uo.org_id = o.id AND uo.user_id = $userId để xác định người dùng có phải thành viên tổ chức không
            // Nhưng khi tạo tổ chức mới thì người dùng là creator chưa chắc đã là thành viên tổ chức đó
            // Tạm đang để LEFT JOIN 
            $query = "SELECT 
                    o.id AS id, 
                    o.name AS 'Tên tổ chức',
                    org.name AS 'Tên tổ chức cha',
                    ol.name AS 'Cấp độ tổ chức',
                    ot.name AS 'Loại tổ chức'
                FROM organizations o
                LEFT JOIN organizations org ON o.parent_org_id = org.id
                LEFT JOIN org_types ot ON ot.id = o.org_type_id
                LEFT JOIN user_orgs uo ON uo.org_id = o.id AND uo.user_id = $userId
                JOIN org_levels ol ON o.org_level = ol.id
                WHERE 1=1
                AND o.id IN ($entityIdList)";
            
            if (isset($_GET['org_id']) && is_numeric($_GET['org_id'])) {
                $selected_org_id = intval($_GET['org_id']);
                $query .= " AND o.parent_org_id = $selected_org_id";
            }
        }
    } else {
        $query = "SELECT 1 WHERE 1=0";
        debug_log("info", "Người dùng ID $userId không có quyền xem tổ chức nào." );
    }

    $rows_per_page = 10;
    $current_page = $_GET['page'] ?? 1;
    $total_results = total_results($conn, $query);
    $result = query($query);
    $total_pages = ceil($total_results / $rows_per_page);
    $reload_link = $_SERVER['REQUEST_URI'];

    // Xác định trạng thái các nút dựa trên quyền
    if (defined("IS_DEBUG") && !IS_DEBUG) {
        $can_assign_permissions = hasPermission($userId, "assign_permissions", null, $resource_type_id);
    } else {    
        $can_assign_permissions = true;
    }
    $button_list = [];

    if ($can_assign_permissions) {
        $button_list[] = [
            "btn_type" => "Assign Permissions",
            "label"    => "Phân quyền",
            "btn_url"  => "/pages/org_permissions/assign_permissions.php",
            "placement" => "table",
            "btn_class" => "details-btn"
        ];
    }

    
?>
