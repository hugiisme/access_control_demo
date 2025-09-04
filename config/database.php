<?php
    define("IS_DEBUG", true);

    // local
    // $db_server = "localhost";
    // $db_user = "root";
    // $db_password = "";
    // $db_name = "ac_demo";

    // host
    $db_server = "nvme.h2cloud.vn";
    $db_user = "diemdanh_ctd_root";
    $db_password = "@cntt2025";
    $db_name = "diemdanh_doan_v1";
    
    // Bật chế độ báo lỗi chi tiết cho mysqli
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

    $conn = mysqli_connect($db_server, $db_user, $db_password, $db_name);
    if (!$conn) {
        die("Không thể kết nối với cơ sở dữ liệu: " . mysqli_connect_error());
    }
    date_default_timezone_set('Asia/Ho_Chi_Minh');
    function query($conn, $sql) {
        try {
            return mysqli_query($conn, $sql);
        } catch (mysqli_sql_exception $e) {
             $errorMsg  = "❌ SQL Error: " . $e->getMessage() . "\n";
            $errorMsg .= "👉 Query: " . $sql . "\n";
            $errorMsg .= "📅 Time: " . date("Y-m-d H:i:s") . "\n";
            $errorMsg .= "-----------------------------\n";
            echo "<pre>$errorMsg</pre>";
            error_log($errorMsg);
            exit;
        }
    }
?>