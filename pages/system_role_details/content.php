<?php 
    require_once __DIR__ . '/../../includes/access_control.php';
    require_once __DIR__ . '/../../config/database.php';
    require_once __DIR__ . '/../../includes/notifications.php';
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $system_role_id = $_GET['id'] ?? null;
    if(!$system_role_id) {
        add_notification("error", "ID vai trò không hợp lệ", 4);
        exit;
    }
    $systemRoleNameQuery = "SELECT name FROM system_roles WHERE id = $system_role_id";
    $systemRoleNameResult = query($conn, $systemRoleNameQuery);
    if(mysqli_num_rows($systemRoleNameResult) === 0) {
        add_notification("error", "Vai trò không tồn tại", 4);
        exit;
    }
    $systemRoleName = mysqli_fetch_assoc($systemRoleNameResult)['name'];


?>
<h1 class="page-title">Thông tin chi tiết của vai trò <?php echo $systemRoleName ?></h1>

    
<ul class="detail-links">
    <li><a href="index.php?pageName=system_role_permissions&role_id=<?php echo  $system_role_id?>">Danh sách quyền của vai trò</a></li>
    <li><a href="index.php?pageName=user_system_roles&role_id=<?php echo  $system_role_id?>">Danh sách thành viên có vai trò</a></li>
</ul>


