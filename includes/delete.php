<?php 
    include_once __DIR__ . "/../config/database.php";
    include_once __DIR__ . "/../includes/notifications/notify.php";
    include_once __DIR__ . "/../includes/access_control.php";

    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    global $conn;

    $table_name = $_GET['table'] ?? null;
    $row_id = $_GET['row_id'] ?? null;
    $redirect_link = $_GET['redirect_link'] ?? "/index.php?view=home";
    
    if (!$table_name || !$row_id) {
        $msg = "Thiếu thông tin cần thiết để xóa.";
        redirect_with_message("error", $msg, $redirect_link);
        exit();
    }

    // ⚠️ Chống SQL injection cho table và id
    $table_name = mysqli_real_escape_string($conn, $table_name);
    $row_id = intval($row_id);

    // Thực hiện DELETE chính
    $query = "DELETE FROM $table_name WHERE id = $row_id";
    $result = query($query);

    if ($result === false) {
        // Nếu query lỗi → in ra thông tin để debug
        $msg = "Lỗi khi xóa record: " . mysqli_error($conn) . "\nQuery: $query";
        redirect_with_message("error", $msg, $redirect_link);
        exit();
    }

    if (mysqli_affected_rows($conn) > 0) {
        // Xóa resource liên quan trong bảng resources
        $resource_type_res = getResourceTypeByName($table_name);
        $resource_type_id = mysqli_fetch_assoc($resource_type_res)['id'] ?? null;

        $resource_id = null;
        if ($resource_type_id) {
            $resource = getResourceByTypeAndID($resource_type_id, $row_id);
            $resource_id = mysqli_fetch_assoc($resource)['id'] ?? null;

            if ($resource_id) {
                $deleteResourceQuery = "DELETE FROM resources WHERE id = " . intval($resource_id);
                $deleteRes = query($deleteResourceQuery);
                if ($deleteRes === false) {
                    // Debug lỗi xóa resource
                    $msg = "Record đã xóa nhưng lỗi khi xóa resource: " . mysqli_error($conn) . "\nQuery: $deleteResourceQuery";
                    redirect_with_message("warning", $msg, $redirect_link);
                    exit();
                }
            }
        }

        $msg = "Xóa thành công.\n$table_name ID: $row_id\nResource ID: " . ($resource_id ?? "none");
        redirect_with_message("success", $msg, $redirect_link);

    } else {
        $msg = "Không tìm thấy record để xóa (id = $row_id trong $table_name).";
        redirect_with_message("error", $msg, $redirect_link);
        exit();
    }
?>
