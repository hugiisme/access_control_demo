<?php
    include __DIR__ . "/../../config/database.php";
    include __DIR__ . "/../../includes/notify.php";
    include __DIR__ . "/../../includes/access_control.php";
    include __DIR__ . "/../../includes/manage_resource.php";
    include __DIR__ . "/../../includes/helper_functions.php";

    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    
    $redirectLink = $_GET['redirectLink'] ?? '/index.php';
    $mode = isset($_GET['id']) ? 'edit' : 'create';
    $resourceTypeName = 'system_roles';
    $resourceTypeId = getResourceTypeByName($resourceTypeName)['id'];

    function createHandler($redirectLink, $resourceTypeId){
        global $conn;

        if(!hasPermission($_SESSION['user']['id'], 'Create', null, 'system_roles')) {
            redirectWithError("Bạn không có quyền truy cập vào trang này.", $redirectLink);
        }
        $label = "Tạo vai trò mới";
        if ($_SERVER['REQUEST_METHOD'] == "POST") {
            $system_role_name = $_POST["system_role_name"];
            $system_role_description = $_POST["system_role_description"];
            $org_id = isset($_POST["org_id"]) && $_POST["org_id"] !== '' ? (int)$_POST["org_id"] : "NULL";
            $system_role_level = isset($_POST["system_role_level"]) && $_POST["system_role_level"] !== '' ? (int)$_POST["system_role_level"] : "NULL";
            $system_role_available_slots = isset($_POST["system_role_available_slots"]) && $_POST["system_role_available_slots"] !== '' ? (int)$_POST["system_role_available_slots"] : "NULL";

            // Bước 1: insert
            $insertQuery = "INSERT INTO system_roles (name, description, org_id, level, available_slots) 
                            VALUES ('$system_role_name', '$system_role_description', $org_id, $system_role_level, $system_role_available_slots)";

            if (!query($conn, $insertQuery)) {
                redirectWithError("Lỗi khi tạo vai trò mới: " . mysqli_error($conn), $redirectLink);
            }
            // Bước 2: tạo resource 
            $newResourceId = mysqli_insert_id($conn);
            createResource($system_role_name, "Resource for $system_role_name", null, $resourceTypeId, $newResourceId);
            
            // Bước 3: update version
            updateResourceTypeVersion($resourceTypeId);

            // Bước 4: gắn quan hệ người tạo
            // TODO

            add_notification("success", "Tạo vai trò mới thành công", 4000);
            header("Location: $redirectLink");
            exit;
        }
        return [$label, null];
    }

    function editHandler($redirectLink, $resourceTypeId) {
        global $conn;

        $system_role_id = $_GET['id'] ?? null;
          
        if ($system_role_id) {
            if(!hasPermission($_SESSION['user']['id'], 'Create', $system_role_id, 'system_roles')) {
                redirectWithError("Bạn không có quyền truy cập vào trang này.", $redirectLink);
            }

            $query = "SELECT * FROM system_roles WHERE id = $system_role_id";
            $result = query($conn, $query);
            if ($result && mysqli_num_rows($result) > 0) {
                $system_role = mysqli_fetch_assoc($result);
                $label = "Chỉnh sửa vai trò ". $system_role['name'];
            } else {
                add_notification("error", "System role not found", 4000);
                exit;
            }
        } else {
            add_notification("error", "Invalid System role group ID", 4000);
            exit;
        }

        if($_SERVER['REQUEST_METHOD'] == "POST"){
            $system_role_name = $_POST["system_role_name"];
            $system_role_description = $_POST["system_role_description"];
            $org_id = isset($_POST["org_id"]) && $_POST["org_id"] !== '' ? (int)$_POST["org_id"] : "NULL";
            $system_role_level = isset($_POST["system_role_level"]) && $_POST["system_role_level"] !== '' ? (int)$_POST["system_role_level"] : "NULL";
            $system_role_available_slots = isset($_POST["system_role_available_slots"]) && $_POST["system_role_available_slots"] !== '' ? (int)$_POST["system_role_available_slots"] : "NULL";

            // Bước 1: update
            $updateQuery =  "UPDATE system_roles SET 
                                name = '$system_role_name',
                                description = '$system_role_description',
                                org_id = $org_id,
                                level = $system_role_level,
                                available_slots = $system_role_available_slots
                            WHERE id = $system_role_id";

            if (! query($conn, $updateQuery)) {
                redirectWithError("Lỗi khi cập nhật vai trò: " . mysqli_error($conn), $redirectLink);
            }

            // Bước 2: cập nhật resource tương ứng
            $resourceId = getResourceId($resourceTypeId, $system_role_id);
            updateResource($resourceId, $system_role_name, $system_role_description, 1, $resourceTypeId, $system_role_id);

            // Bước 3: update version
            updateResourceTypeVersion($resourceTypeId);

            // Bước 4: gắn quan hệ người edit
            // TODO

            add_notification("success", "Update vai trò $system_role_name thành công", 4000);
            header("Location: $redirectLink");
            exit;
        }

        return [$label, $system_role];
    }


    if ($mode === 'create') {
        [$label, $system_role] = createHandler($redirectLink, $resourceTypeId);
    } else if ($mode === 'edit') {
        [$label, $system_role] = editHandler($redirectLink, $resourceTypeId);
    }

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $label ?></title>
    <link rel="stylesheet" href="../../assets/css/form.css">
</head>
<body>
    <form action="" method="POST" class="form">
        <h1 class="page-title"><?php echo $label ?></h1>

        <div class="form-group">
            <label for="system-role-name">Tên vai trò</label>
            <input type="text" name="system_role_name" id="system-role-name" class="input-text"
                <?php if (isset($system_role)) echo "value='" . $system_role["name"] . "'"; ?>>
        </div>

        <div class="form-group">
            <label for="system-role-description">Mô tả vai trò</label>
            <input type="text" name="system_role_description" id="system-role-description" class="input-text"
                <?php if (isset($system_role)) echo "value='" . $system_role["description"] . "'"; ?>>
        </div>

        <div class="form-group">
            <label for="system-role-level">Cấp độ  vai trò</label>
            <input type="number" name="system_role_level" id="system-role-level" class="input-text"
                <?php if (isset($system_role)) echo "value='" . $system_role["level"] . "'"; ?>>
        </div>

        <div class="form-group">
            <label for="system-role-available-slots">Số slot còn trống</label>
            <input type="number" name="system_role_available_slots" id="system-role-available-slots" class="input-text"
                <?php if (isset($system_role)) echo "value='" . $system_role["available_slots"] . "'"; ?>>
        </div>

        <input type="hidden" name="org_id" value="<?php echo $_GET['org_id'] ?? '' ?>">


        <div class="form-buttons">
            <button type="button" onclick="location.href='<?php echo $redirectLink ?>'" class="button-cancel">Hủy</button>
            <input type="submit" value="<?= $mode === 'edit' ? 'Cập nhật' : 'Thêm' ?>" class="button-submit">
        </div>
    </form>

</body>
</html>