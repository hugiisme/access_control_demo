<?php
    $pagePath = "pages/$currentPage";

    if (is_dir($pagePath) && file_exists("$pagePath/content.php")) {
        if (file_exists("$pagePath/controller.php")) {
            include_once "$pagePath/controller.php";
        }
        
        include_once "$pagePath/content.php";
    } else {
        include_once "pages/404.php";
    }
?>
