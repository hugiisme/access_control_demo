<?php 
    require_once __DIR__ . '/../../includes/access_control.php';
    require_once __DIR__ . '/../../config/database.php';
    require_once __DIR__ . '/../../includes/notifications.php';
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $system_role_id = $_GET['role_id'] ?? null;
    if(!$system_role_id) {
        add_notification("error", "ID vai trò không hợp lệ", 4);
        exit;
    }
    $systemRoleNameQuery = "SELECT name FROM system_roles WHERE id = $system_role_id";
    $systemRoleNameResult = query($conn, $systemRoleNameQuery);
    if(mysqli_num_rows($systemRoleNameResult) === 0) {
        add_notification("error", "Nhóm vai trò không tồn tại", 4);
        exit;
    }
    $systemRoleName = mysqli_fetch_assoc($systemRoleNameResult)['name'];


?>
<h1 class="page-title">Danh sách thành viên sở hữu vai trò <?php echo $systemRoleName ?></h1>

<?php
    include "includes/table_content.php";
?>