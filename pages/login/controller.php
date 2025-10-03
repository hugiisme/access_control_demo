<?php 
    require_once __DIR__ . "/../../includes/table_renderer.php";
    require_once __DIR__ . "/../../config/database.php";
    require_once __DIR__ . "/../../includes/access_control.php";
    require_once __DIR__ . "/../../includes/notifications/notify.php";
    require_once __DIR__ . "/../../includes/helper_function.php";
    require_once __DIR__ . "/../../includes/query_helper.php";


    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $table_name = "users";

    $query = "SELECT 
                u.id AS id,
                u.name AS 'Tên người dùng'
              FROM users u";

    $rows_per_page = 10;
    $current_page = $_GET['page'] ?? 1;
    $total_results = total_results($conn, $query);
    $result = query($query);
    $total_pages = ceil($total_results / $rows_per_page);
    $reload_link = $_SERVER['REQUEST_URI'];

    $button_list[] = [
        "btn_type" => "Select",
        "label"    => "Chọn",
        "btn_url"  => "/pages/login/login.php",
        "placement" => "table",
        "btn_class" => "select-btn"
    ];

?>
