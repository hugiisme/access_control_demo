<?php
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    function add_notification($type, $message, $timeout = 4000) {
        $_SESSION['notifications'][] = [
            'type' => $type,
            'message' => $message,
            'timeout' => $timeout
        ];

        $logFile = LOG_PATH . "/notifications.log";

        $logLine = sprintf("[%s] (%s) %s\n", date("Y-m-d H:i:s"), strtoupper($type), $message);
        file_put_contents($logFile, $logLine, FILE_APPEND);
    }

?>