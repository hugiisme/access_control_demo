<?php
    require_once __DIR__ . '/../../config/database.php';
    require_once __DIR__ . '/../../includes/notifications/notify.php';
    require_once __DIR__ . '/../../includes/access_control.php';
    require_once __DIR__ . '/../../includes/helper_function.php';

    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // Kiểm tra đăng nhập
    if (!isset($_SESSION['user']) || !isset($_SESSION['user']['id'])) {
        $msgType = "error";
        $msg = "Vui lòng đăng nhập để truy cập trang này.";
        redirect_with_message($msgType, $msg, "/index.php");
        exit();
    }

    $user = $_SESSION['user'];
    $user_id = $user['id'];

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action_id = $_POST['action_id'] ?? '';
        $check_mode = $_POST['check_mode'] ?? 'resource_id';

        // Gộp dữ liệu đầu vào từ form
        $resource_type_id = $_POST['resource_type_id'] ?? null;
        $resource_id = (int) $_POST['resource_id'] ?? null;
        $entity_id = $_POST['entity_id'] ?? null; // từ content.php

        if (empty($action_id)) {
            redirect_with_message("error", "Vui lòng chọn hành động.", "/index.php?view=home");
            exit();
        } else {
            $action_name = (string)mysqli_fetch_assoc(getActionByID($action_id))['name'] ?? null;
            if (!$action_name) {
                redirect_with_message("error", "Hành động không hợp lệ.", "/index.php?view=home");
                exit();
            }
        }

        // Validate theo mode
        if ($check_mode === 'resource_type' && empty($resource_type_id)) {
            redirect_with_message("error", "Vui lòng chọn Resource Type.", "/index.php?view=home");
            exit();
        }

        if ($check_mode === 'resource_type_entity' && (empty($resource_type_id) || empty($entity_id))) {
            redirect_with_message("error", "Vui lòng chọn Resource Type và Entity ID.", "/index.php?view=home");
            exit();
        }

        // Điều chỉnh tham số gọi hasPermission() theo mode
        $permission_granted = false;
        switch ($check_mode) {
            case 'resource_id':
                $permission_granted = hasPermission($user_id, $action_name, $resource_id, null);
                break;
            case 'resource_type':
                $permission_granted = hasPermission($user_id, $action_name, null, $resource_type_id);
                break;
            case 'resource_type_entity':
                $resource_id = mysqli_fetch_assoc(getResourceByTypeAndID($resource_type_id, $entity_id))['id'] ?? null;
                $permission_granted = hasPermission($user_id, $action_name, $resource_id, null);
                break;
        }

        if ($permission_granted) {
            $msgType = "info";
            $msg = "Người dùng " . htmlspecialchars($user['name']) . " được phép thực hiện hành động '" . htmlspecialchars($action_name) . "'";
        } else {
            $msgType = "warning";
            $msg = "Người dùng " . htmlspecialchars($user['name']) . " KHÔNG được phép thực hiện hành động '" . htmlspecialchars($action_name) . "'";
        }

        // Thêm thông tin chi tiết vào thông báo
        if ($check_mode === 'resource_type') {
            $msg .= " trên Resource Type ID " . htmlspecialchars($resource_type_id) . ".";
        } elseif ($check_mode === 'resource_type_entity') {
            $msg .= " trên Resource Type ID " . htmlspecialchars($resource_type_id) . " và Entity ID " . htmlspecialchars($entity_id) . ".";
        } elseif ($check_mode === 'resource_id' && !empty($resource_id)) {
            $msg .= " trên Resource ID " . htmlspecialchars($resource_id) . ".";
        } else {
            $msg .= ".";
        }

        redirect_with_message($msgType, $msg, "/index.php?view=home");
        exit();
    }
