<?php
    include __DIR__ . "/../config/database.php";
    include __DIR__ . "/../includes/notify.php";
    include __DIR__ . "/../includes/access_control.php";
    $table = $_GET['table']; 
    $id = (int)$_GET['id'];
    $redirectLink = $_GET["redirectLink"] ?? "/index.php";

    if ($id && $table) {
        $query = "SELECT name FROM `$table` WHERE id=$id";
        $result = query($conn, $query);
        if (!$result) {
            add_notification("error", "Lỗi khi truy vấn dữ liệu: " . mysqli_error($conn), 4000);
            echo "Query failed: " . mysqli_error($conn);
            exit;
        }
        if (mysqli_num_rows($result) === 0) {
            add_notification("error", "Không tìm thấy bản ghi với ID $id trong bảng $table", 4000);
            header("Location: $redirectLink");
            exit;
        }
        // Xóa trong bảng resource 
        $resource_type_id = getResourceTypeByName($table)['id'];
        $resourceQuery = "SELECT id FROM resources WHERE resource_type_id='$resource_type_id' AND entity_id=$id";
        $result = query($conn, $resourceQuery);
        if ($result && mysqli_num_rows($result) > 0) {
            $resourceRow = mysqli_fetch_assoc($result);
            $resourceId = $resourceRow['id'];
            // Xóa resource
            query($conn, "DELETE FROM resources WHERE id=$resourceId");
        }
        $row = mysqli_fetch_assoc($result);
        $name = $row['name'] ?? '???';
        query($conn, "DELETE FROM `$table` WHERE id=$id");
        add_notification("success", "Xóa item $name bảng $table thành công", 4000);
        header("Location: $redirectLink"); 
        exit;
    }

    echo "<h1>Invalid id or table</h1>";

?>
