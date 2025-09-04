<?php 
    require_once __DIR__ . '/../../includes/access_control.php';
    require_once __DIR__ . '/../../config/database.php';
    require_once __DIR__ . '/../../includes/notifications.php';
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $system_role_group_id = $_GET['group_id'] ?? null;
    if(!$system_role_group_id) {
        add_notification("error", "ID nhóm vai trò không hợp lệ", 4);
        exit;
    }
    $systemRoleGroupNameQuery = "SELECT name FROM system_role_groups WHERE id = $system_role_group_id";
    $systemRoleGroupNameResult = query($conn, $systemRoleGroupNameQuery);
    if(mysqli_num_rows($systemRoleGroupNameResult) === 0) {
        add_notification("error", "Nhóm vai trò không tồn tại", 4);
        exit;
    }
    $systemRoleGroupName = mysqli_fetch_assoc($systemRoleGroupNameResult)['name'];


?>
<h1 class="page-title">Danh sách quyền của nhóm vai trò <?php echo $systemRoleGroupName ?></h1>

<?php
    include "includes/table_content.php";
?>