<?php 
    require_once "table_renderer.php";
    include "config/database.php";

    $tableName = "users";

    // Query gốc luôn có WHERE 1=1
    $query = "SELECT * FROM `$tableName` WHERE 1=1";

    $rowsPerPage = 10;
    $currentPage = isset($_GET['page']) ? (int)$_GET['page'] : 1;

    // Tính tổng kết quả trước khi phân trang
    $totalResults = totalResults($conn, $query);
    $totalPages = ceil($totalResults / $rowsPerPage);

    // Link reload
    $reloadLink = $_SERVER['REQUEST_URI'];

    // Danh sách button
    $buttonList = [
        [   
            "btn_type" => "Select", 
            "label"    => "Chọn", 
            "btn_url"  => "/pages/login/login.php"
        ]
    ];

    // Tách riêng Create / Assign button nếu cần
    $createButton = null;
    $assignButton = null;
    foreach ($buttonList as $button) {
        if ($button['btn_type'] === 'Create') {
            $createButton = $button;
        }
        if ($button['btn_type'] === 'Assign') {
            $assignButton = $button;
        }
    }

    // Lấy result ban đầu (chưa phân trang, chưa filter) để table_content dùng
    $result = query($conn, $query);
?>
