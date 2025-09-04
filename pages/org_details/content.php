<?php 
    require_once __DIR__ . '/../../includes/access_control.php';
    require_once __DIR__ . '/../../config/database.php';
    require_once __DIR__ . '/../../includes/notifications.php';
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $orgId = $_GET['id'] ?? null;
    if(!$orgId) {
        add_notification("error", "ID tổ chức không hợp lệ", 4);
        exit;
    }
    $orgNameQuery = "SELECT name FROM organizations WHERE id = $orgId";
    $orgNameResult = query($conn, $orgNameQuery);
    if(mysqli_num_rows($orgNameResult) === 0) {
        add_notification("error", "Tổ chức không tồn tại", 4);
        exit;
    }
    $orgNameRow = mysqli_fetch_assoc($orgNameResult);
    $orgName = $orgNameRow['name'];

?>
<h1 class="page-title">Thông tin chi tiết của tổ chức <?php echo $orgName ?></h1>

<ul class="detail-links">
    <li><a href="index.php?pageName=system_role_groups&orgID=<?php echo $orgId ?>">Danh sách nhóm vai trò trong tổ chức</a></li>
    <li><a href="index.php?pageName=system_roles&orgID=<?php echo $orgId ?>">Danh sách vai trò trong tổ chức</a></li>
    <li><a href="index.php?pageName=user_orgs&orgID=<?php echo $orgId ?>">Danh sách thành viên tổ chức</a></li>
    <li><a href="index.php?pageName=child_orgs&orgID=<?php echo $orgId ?>">Danh sách tổ chức con của tổ chức</a></li>
    <li><a href="index.php?pageName=org_permissions&orgID=<?php echo $orgId ?>">Danh sách quyền của tổ chức</a></li>
</ul>


