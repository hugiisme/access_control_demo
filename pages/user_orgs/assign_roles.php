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

    if(!$org_id) {
        echo "<h2>Vui lòng chọn tổ chức để quản lý thành viên.</h2>";
        exit;
    }

    $selected_user_id = isset($_GET['row_id']) && is_numeric($_GET['row_id']) ? intval($_GET['row_id']) : null;
    if(!$selected_user_id) {
        echo "<h2>Vui lòng chọn người dùng để gán vai trò.</h2>";
        exit;
    }
    $user_name = mysqli_fetch_assoc(getUserByID($selected_user_id))["name"];
    
    $sql = "SELECT
                CASE WHEN usr.user_id IS NOT NULL THEN 1 ELSE 0 END AS is_checked,
                sr.id AS id,
                sr.name AS name
            FROM system_roles sr
            LEFT JOIN user_system_roles usr 
                ON sr.id = usr.system_role_id 
                AND usr.user_id = {$selected_user_id} 
                AND sr.org_id = {$org_id}";

    $result = query($sql);
    $roles = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $roles[] = $row;
    }

    $form = new FormBuilder($conn, "Quản lý vai trò của người dùng $user_name", "user_system_roles");

    $form->addField("matrix", "user_id", "Quản lý vai trò của người dùng $user_name", [
        "result" => $roles,
        "idField" => "id",
        "checkField" => "is_checked",
        "updateUrl" => "/pages/user_orgs/update_user_system_roles.php?org_id={$org_id}&user_id={$selected_user_id}"   
    ]);

    $form->render();
?>
