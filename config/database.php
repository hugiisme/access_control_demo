<?php
    require_once __DIR__ . "/../includes/debug_log.php";
    require_once __DIR__ . "/../includes/notifications/notify.php";
    require_once __DIR__ . "/../includes/query_helper.php";
    require_once __DIR__ . "/../includes/helper_function.php";

    require_once "config.php";

    if (defined('IS_LOCAL') && IS_LOCAL) {
        $db_server   = "localhost";
        $db_user     = "root";
        $db_password = "";
        $db_name     = "access_control";
    } else {
        $db_server   = "nvme.h2cloud.vn";
        $db_user     = "diemdanh_ctd_root";
        $db_password = "@cntt2025";
        $db_name     = "diemdanh_doan_v1";
    }

    // Bật chế độ báo lỗi chi tiết cho mysqli
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

    $conn = mysqli_connect($db_server, $db_user, $db_password, $db_name);
    if (!$conn) {
        $errorMsg  = "❌ Database Connection Error: " . mysqli_connect_error() . "\n";
        $errorMsg .= "📅 Time: " . date("Y-m-d H:i:s") . "\n";
        $errorMsg .= "-----------------------------\n";

        // Ghi log
        $msgType = "error";
        redirect_with_message($msgType, $errorMsg);

        // Hiện ra màn hình nếu bật debug
        if (defined("IS_DEBUG") && IS_DEBUG) {
            echo "<pre>$errorMsg</pre>";
        }

        die("Không thể kết nối với cơ sở dữ liệu.");
    }

    date_default_timezone_set('Asia/Ho_Chi_Minh');

    function query($sql) {
        global $conn;
        try {
            return mysqli_query($conn, $sql);
        } catch (mysqli_sql_exception $e) {
            $errorMsg  = "❌ SQL Error: " . $e->getMessage() . "\n";
            $errorMsg .= "👉 Query: " . $sql . "\n";
            $errorMsg .= "📅 Time: " . date("Y-m-d H:i:s") . "\n";
            $errorMsg .= "-----------------------------\n";

            // Ghi log
            $msgType = "error";
            redirect_with_message($msgType, $errorMsg);

            // Hiện ra màn hình nếu bật debug
            if (defined("IS_DEBUG") && IS_DEBUG) {
                echo "<pre>$errorMsg</pre>";
            }

            exit;
        }
    }
?>
