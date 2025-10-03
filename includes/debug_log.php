<?php
    require_once dirname(__DIR__) . '/config/config.php';
    // Đường dẫn file log
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $logFile = LOG_PATH . "/debug.log";
    if (!is_dir(LOG_PATH)) {
        mkdir(LOG_PATH, 0777, true);
    }

    /**
     * Ghi log vào file debug.log
     * @param string $message Nội dung log
     * @param string $type   Mức log: info, error, warning, debug
     */
    function debug_log($type = "info", $message) {
        global $logFile;

        // Tự động chuyển type thành viết hoa
        $type = strtoupper($type);

        $timestamp = date("Y-m-d H:i:s");
        $logText = "[$timestamp] [$type] $message\n";

        file_put_contents($logFile, $logText, FILE_APPEND);

        // Nếu bật debug thì in ra màn hình
        if (defined("IS_DEBUG") && IS_DEBUG) {
            if (!isset($_SESSION["logText"])) {
                $_SESSION["logText"] = ""; // tạo trước khi nối
            }
            $_SESSION["logText"] .= $logText;
        }
    }
?>
