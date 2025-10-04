<link rel="stylesheet" href="../../assets/css/form.css">
<?php 
    // TODO: fix
    // Hiện tại đang để là theo tổ chức cha, nhưng nếu đúng thì phải là theo tổ chức của người đang thao tác (chắc thế)
    include_once __DIR__ . "/../../config/database.php";
    include_once __DIR__ . "/../../includes/access_control.php";
    include_once __DIR__ . "/../../includes/resource_manager.php";
    include_once __DIR__ . "/../../includes/query_helper.php";
    include_once __DIR__ . "/../../includes/form/FormBuilder.php";

    if (session_status() === PHP_SESSION_NONE) session_start();

    $userId = $_SESSION['user']['id'] ?? null;
    if (!$userId) {
        redirect_with_message("warning", "Vui lòng đăng nhập để xem dữ liệu.");
        exit;
    }

    $org_id = isset($_GET['row_id']) && is_numeric($_GET['row_id']) ? intval($_GET['row_id']) : null;

    if(!$org_id) {
        echo "<h2>Vui lòng chọn tổ chức để thực hiện phân quyền.</h2>";
        exit;
    }

    $org_name = mysqli_fetch_assoc(getOrgByID($org_id))["name"];
    
    $parent_row = mysqli_fetch_assoc(query("SELECT parent_org_id FROM organizations WHERE id = {$org_id}"));
    $parent_id = $parent_row ? $parent_row["parent_org_id"] : null;

    if ($parent_id) {
        // Có cha -> chỉ lấy quyền mà tổ chức cha đang có
        $sql = "SELECT
                    CASE WHEN op.org_id IS NOT NULL THEN 1 ELSE 0 END AS is_checked,
                    p.id AS id,
                    a.name AS action_name,
                    rt.name AS resource_type
                FROM permissions p
                JOIN actions a ON p.action_id = a.id
                JOIN resource_types rt ON p.resource_type_id = rt.id
                JOIN (
                    SELECT permission_id 
                    FROM org_permissions 
                    WHERE org_id = {$parent_id}
                ) parent_perms ON p.id = parent_perms.permission_id
                LEFT JOIN org_permissions op 
                    ON p.id = op.permission_id 
                AND op.org_id = {$org_id}
                ORDER BY p.id";
    } else {
        // Không có cha (tổ chức gốc) -> được chọn toàn bộ quyền
        $sql = "SELECT
                    CASE WHEN op.org_id IS NOT NULL THEN 1 ELSE 0 END AS is_checked,
                    p.id AS id,
                    a.name AS action_name,
                    rt.name AS resource_type
                FROM permissions p
                JOIN actions a ON p.action_id = a.id
                JOIN resource_types rt ON p.resource_type_id = rt.id
                LEFT JOIN org_permissions op 
                    ON p.id = op.permission_id 
                AND op.org_id = {$org_id}
                ORDER BY p.id";
    }

    $result = query($sql);
    $permissions = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $permissions[] = $row;
    }

    $form = new FormBuilder($conn, "Quản lý quyền của tổ chức $org_name", "org_permissions");

    $form->addField("matrix", "user_id", "Quản lý quyền của tổ chức $org_name", [
        "result" => $permissions,
        "idField" => "id",
        "checkField" => "is_checked",
        "updateUrl" => "/pages/org_permissions/update_permissions.php?org_id={$org_id}"   
    ]);

    $form->render();
?>
