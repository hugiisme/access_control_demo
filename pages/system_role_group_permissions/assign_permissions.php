<link rel="stylesheet" href="../../assets/css/form.css">
<?php 
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

    $group_id = isset($_GET['row_id']) && is_numeric($_GET['row_id']) ? intval($_GET['row_id']) : null;
    if (!$group_id) {
        echo "<h2>Vui lòng chọn nhóm vai trò để thực hiện phân quyền.</h2>";
        exit;
    }

    $org_id = isset($_GET['org_id']) && is_numeric($_GET['org_id']) ? intval($_GET['org_id']) : null;
    if(!$org_id) {
        echo "<h2>Vui lòng chọn tổ chức để thực hiện phân quyền.</h2>";
        exit;
    }

    $group_row = mysqli_fetch_assoc(getSystemRoleGroupByID($group_id));
    if (!$group_row) {
        echo "<h2>Không tìm thấy nhóm vai trò.</h2>";
        exit;
    }

    $group_name = $group_row["name"];
    $parent_group_id = $group_row["parent_group_id"];

    // ✅ Query quyền cho nhóm vai trò
    if ($parent_group_id) {
        // Nếu có nhóm cha -> chỉ lấy quyền mà nhóm cha có
        $sql = "
            SELECT
                CASE WHEN srp.system_role_group_id IS NOT NULL THEN 1 ELSE 0 END AS is_checked,
                p.id AS id,
                a.name AS action_name,
                rt.name AS resource_type
            FROM permissions p
            JOIN actions a ON p.action_id = a.id
            JOIN resource_types rt ON p.resource_type_id = rt.id
            JOIN (
                SELECT permission_id 
                FROM system_role_group_permissions 
                WHERE system_role_group_id = {$parent_group_id}
            ) parent_perms ON p.id = parent_perms.permission_id
            LEFT JOIN system_role_group_permissions srp
                ON p.id = srp.permission_id
               AND srp.system_role_group_id = {$group_id}
            ORDER BY p.id
        ";
    } else {
        // Nếu là nhóm gốc -> chỉ lấy quyền mà tổ chức có
        $sql = "
            SELECT
                CASE WHEN srp.system_role_group_id IS NOT NULL THEN 1 ELSE 0 END AS is_checked,
                p.id AS id,
                a.name AS action_name,
                rt.name AS resource_type
            FROM permissions p
            JOIN actions a ON p.action_id = a.id
            JOIN resource_types rt ON p.resource_type_id = rt.id
            JOIN (
                SELECT permission_id 
                FROM org_permissions 
                WHERE org_id = {$org_id}
            ) org_perms ON p.id = org_perms.permission_id
            LEFT JOIN system_role_group_permissions srp
                ON p.id = srp.permission_id
               AND srp.system_role_group_id = {$group_id}
            ORDER BY p.id
        ";
    }

    $result = query($sql);
    $permissions = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $permissions[] = $row;
    }

    // ✅ Render form matrix
    $form = new FormBuilder($conn, "Quản lý quyền của nhóm vai trò $group_name", "system_role_group_permissions");

    $form->addField("matrix", "permission_id", "Quản lý quyền của nhóm vai trò $group_name", [
        "result" => $permissions,
        "idField" => "id",
        "checkField" => "is_checked",
        "updateUrl" => "/pages/system_role_group_permissions/update_permissions.php?group_id={$group_id}"   
    ]);

    $form->render();
?>
