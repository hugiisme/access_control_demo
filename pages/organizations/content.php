<?php
    $label = "Danh sách tổ chức";
    if(isset($_GET['org_id'])) {
        $org_id = intval($_GET['org_id']);
        $org_name = mysqli_fetch_assoc(getOrgById($org_id))['name'] ?? "Không xác định";
        $label .= " con của tổ chức $org_name";
    }
    echo "<h2>$label</h2>";
    include_once dirname(__DIR__) . "/../includes/table_content.php";
?>
