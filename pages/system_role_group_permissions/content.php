<?php
    $label = "Danh sách nhóm vai trò";
    if(isset($_GET['org_id'])) {
        $org_id = intval($_GET['org_id']);
        $org_name = mysqli_fetch_assoc(getOrgById($org_id))['name'] ?? "Không xác định";
        $label .= " của tổ chức $org_name";
    }
    echo "<h2>$label</h2>";
    include_once dirname(__DIR__) . "/../includes/table_content.php";
?>
