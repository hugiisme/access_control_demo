<?php 
    require_once __DIR__ . '/../../includes/access_control.php';
    require_once __DIR__ . '/../../config/database.php';
    require_once __DIR__ . '/../../includes/notifications.php';
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $system_role_group_id = $_GET['id'] ?? null;
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
<h1 class="page-title">Thông tin chi tiết của nhóm vai trò <?php echo $systemRoleGroupName ?></h1>

    
<ul class="detail-links">
    <li><a href="index.php?pageName=system_role_group_permissions&group_id=<?php echo  $system_role_group_id?>">Danh sách quyền của nhóm vai trò</a></li>
    <li><a href="index.php?pageName=system_role_group_roles&group_id=<?php echo  $system_role_group_id?>">Danh sách vai trò thuộc nhóm vai trò</a></li>
</ul>


