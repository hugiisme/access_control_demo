<?php 
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/notify.php';
require_once __DIR__ . '/../../includes/access_control.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user'])) {
    add_notification("error", "Vui lòng đăng nhập để truy cập trang này.");
    header("Location: /index.php?pageName=home");
    exit();
}

$user = $_SESSION['user'];
if (!$user || !isset($user['id'])) {
    add_notification("error", "Thông tin người dùng không hợp lệ.");
    header("Location: /index.php?pageName=home");
    exit();
}
$userId = $user['id'];

// Lấy danh sách action
$actionQuery = "SELECT id, name FROM actions";
$actionResult = query($conn, $actionQuery);
if (!$actionResult) {
    add_notification("error", "Lỗi khi lấy danh sách hành động: " . mysqli_error($conn));
    $actionResult = [];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $actionName = $_POST['action_name'] ?? '';
    $resourceId = $_POST['resource_id'] ?? '';
    $resourceTypeId = $_POST['resource_type_id'] ?? '';
    $entityId = $_POST['entity_id'] ?? '';

    $hasPermission = false;

    if ($actionName) {
        if (!empty($resourceId)) {
            // ✅ Trường hợp 1: Check quyền theo resource_id cụ thể
            $hasPermission = hasPermission($userId, $actionName, $resourceId, null);

        } elseif (!empty($resourceTypeId) && empty($entityId)) {
            // ✅ Trường hợp 2: Check quyền cấp resource_type (resource_id IS NULL)
            $rtData = getResourceType($resourceTypeId);
            if ($rtData) {
                $hasPermission = hasPermission($userId, $actionName, null, $rtData['name']);
            }

        } elseif (!empty($resourceTypeId) && !empty($entityId)) {
            // ✅ Trường hợp 3: Check quyền theo resource_type + entity_id
            $rtData = getResourceType($resourceTypeId);
            if ($rtData) {
                $query = "
                    SELECT 1
                    FROM user_permission_snapshots ups
                    JOIN resources r ON r.id = ups.resource_id
                    WHERE user_id = $userId
                      AND ups.action_id = " . getActionID($actionName) . "
                      AND ups.resource_type_id = $resourceTypeId
                      AND r.entity_id = $entityId
                    LIMIT 1
                ";
                $res = query($conn, $query);
                $hasPermission = ($res && mysqli_num_rows($res) > 0);
                if ($res) mysqli_free_result($res);
            }
        }
    }

    if ($hasPermission) {
        add_notification("success", "Bạn có quyền thực hiện hành động này.");
    } else {
        add_notification("error", "Bạn không có quyền thực hiện hành động này.");
    }

    header("Location: " . $_SERVER['REQUEST_URI']);
    exit();
}
?>