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

    $role_id = isset($_GET['row_id']) && is_numeric($_GET['row_id']) ? intval($_GET['row_id']) : null;
    if (!$role_id) {
        echo "<h2>Vui lòng chọn vai trò để thực hiện phân quyền.</h2>";
        exit;
    }

    $role_row = mysqli_fetch_assoc(getSystemRoleByID($role_id));
    if (!$role_row) {
        echo "<h2>Không tìm thấy vai trò.</h2>";
        exit;
    }

    $role_name = $role_row["name"];

    // ✅ tìm nhóm vai trò mà role này thuộc về
    $group_row = mysqli_fetch_assoc(query("
        SELECT g.* 
        FROM system_role_group_roles gr
        JOIN system_role_groups g ON gr.system_role_group_id = g.id
        WHERE gr.system_role_id = {$role_id}
        LIMIT 1
    "));

    if (!$group_row) {
        echo "<h2>Vai trò <b>{$role_name}</b> chưa thuộc nhóm vai trò nào, nên không thể phân quyền.</h2>";
        exit;
    }

    $group_id = $group_row['id'];
    $group_name = $group_row['name'];

    // ✅ chỉ lấy quyền từ nhóm vai trò
    $sql = "
        SELECT
            CASE WHEN srp.system_role_id IS NOT NULL THEN 1 ELSE 0 END AS is_checked,
            p.id AS id,
            a.name AS action_name,
            rt.name AS resource_type
        FROM permissions p
        JOIN actions a ON p.action_id = a.id
        JOIN resource_types rt ON p.resource_type_id = rt.id
        JOIN (
            SELECT permission_id 
            FROM system_role_group_permissions 
            WHERE system_role_group_id = {$group_id}
        ) group_perms ON p.id = group_perms.permission_id
        LEFT JOIN system_role_permissions srp
            ON p.id = srp.permission_id
           AND srp.system_role_id = {$role_id}
        ORDER BY p.id
    ";

    $result = query($sql);
    $permissions = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $permissions[] = $row;
    }

    // ✅ Render form
    $form = new FormBuilder($conn, "Quản lý quyền của vai trò $role_name (theo nhóm $group_name)", "system_role_permissions");

    $form->addField("matrix", "permission_id", "Quản lý quyền của vai trò $role_name (theo nhóm $group_name)", [
        "result" => $permissions,
        "idField" => "id",
        "checkField" => "is_checked",
        "updateUrl" => "/pages/system_role_permissions/update_permissions.php?role_id={$role_id}"   
    ]);

    $form->render();
?>
