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

    $org_name = mysqli_fetch_assoc(getOrgByID($org_id))["name"];


    // Lấy roles thuộc group hiện tại
    $sql = "SELECT 
                CASE WHEN uo.user_id IS NOT NULL THEN 1 ELSE 0 END AS is_checked,
                u.id AS id,
                u.name AS name
            FROM users u
            LEFT JOIN user_orgs uo 
                ON u.id = uo.user_id AND uo.org_id = {$org_id}";

    $result = query($sql);
    $users = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $users[] = $row;
    }

    $form = new FormBuilder($conn, "Quản lý người dùng của tổ chức $org_name", "user_orgs");

    $form->addField("matrix", "user_id", "Chọn người dùng cho tổ chức $org_name", [
        "result" => $users,
        "idField" => "id",
        "checkField" => "is_checked",
        "updateUrl" => "/pages/user_orgs/update_user_orgs.php?org_id={$org_id}"   
    ]);

    $form->render();
?>
