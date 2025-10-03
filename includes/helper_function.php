<?php 
    require_once "debug_log.php";
    require_once "notifications/notify.php";

    function redirect_with_message($type, $message, $url = null, $log=true) {
        if ($log) {
            debug_log($type, $message);
        }
        add_notification($type, $message);
        if($url) {
            header("Location: $url");
            exit();
        }
    }

    
?>