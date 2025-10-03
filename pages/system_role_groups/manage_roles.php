<link rel="stylesheet" href="../../assets/css/form.css">
<?php 
    // TODO: fix
    // Nếu đã nằm trong nhóm con nhưng lại nhảy lên nhóm cha chọn tiếp thì sao
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

    $org_id = isset($_GET['org_id']) && is_numeric($_GET['org_id']) ? intval($_GET['org_id']) : null;
    $group_id = isset($_GET['row_id']) && is_numeric($_GET['row_id']) ? intval($_GET['row_id']) : null;

    if(!$org_id || !$group_id) {
        echo "<h2>Vui lòng chọn tổ chức và nhóm vai trò để quản lý vai trò.</h2>";
        exit;
    }

    $group_name = mysqli_fetch_assoc(getRoleGroupNameById($group_id))['name'] ?? null;
    if(!$group_name) {
        echo "<h2>Nhóm vai trò không tồn tại hoặc bạn không có quyền truy cập.</h2>";
        exit;
    }

    // Lấy roles thuộc group hiện tại
    $sql = "SELECT 
                CASE WHEN sgr.system_role_id IS NOT NULL THEN 1 ELSE 0 END AS is_checked,
                sr.id,
                sr.name
            FROM system_roles sr
            LEFT JOIN system_role_group_roles sgr 
                ON sr.id = sgr.system_role_id 
                AND sgr.system_role_group_id = {$group_id}
            WHERE sr.org_id = {$org_id}";

    $result = query($sql);
    $inheritedRoles = getInheritedRoles($group_id);

    $roles = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $row['is_inherited'] = isset($inheritedRoles[$row['id']]) ? 1 : 0;
        $roles[] = $row;
    }

    $form = new FormBuilder($conn, "Quản lý Vai trò cho nhóm $group_name", "system_role_group_roles");

    $form->addField("matrix", "system_role_id", "Chọn vai trò cho nhóm vai trò $group_name", [
        "result" => $roles,
        "idField" => "id",
        "checkField" => "is_checked",
        "updateUrl" => "/pages/system_role_groups/update_group.php?group_id={$group_id}"  
    ]);

    $form->render();
?>
