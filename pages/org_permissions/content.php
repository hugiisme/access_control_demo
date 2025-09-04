<?php 
    require_once "table_renderer.php";
    require_once "config/database.php";
    require_once "includes/access_control.php";
    require_once "includes/notifications.php";

    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $orgId = $_GET['orgID'] ?? null;
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
<h1>Danh sách Quyền của tổ chức <?php echo $orgName ?></h1>

<?php
    include "includes/table_content.php";
?>