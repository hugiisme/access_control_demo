<?php
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    function add_notification($type, $message, $timeout = 3) {
        $_SESSION['notifications'][] = [
            'type' => $type,
            'message' => $message,
            'timeout' => $timeout * 1000 
        ];
    }

?>