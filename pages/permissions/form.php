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
    $resourceTypeName = 'permissions';
    $resourceTypeId = getResourceTypeByName($resourceTypeName)['id'];
    $actionsResult = getActionList();
    $resourceTypesResult = getResourceTypesList();

    function createHandler($redirectLink, $resourceTypeId){
        global $conn;

        if(!hasPermission($_SESSION['user']['id'], 'Create', null, 'permissions')) {
            redirectWithError("Bạn không có quyền truy cập vào trang này.", $redirectLink);
        }
        $label = "Tạo quyền mới";
        if ($_SERVER['REQUEST_METHOD'] == "POST") {
            $action_id = $_POST['action_id'];
            $resource_type_id = $_POST['resource_type_id'];

            // Bước 1: insert
            $insertQuery = "INSERT INTO permissions (action_id, resource_type_id) 
                            VALUES ('$action_id', '$resource_type_id')";

            if (!query($conn, $insertQuery)) {
                redirectWithError("Lỗi khi tạo quyền mới: " . mysqli_error($conn), $redirectLink);
            }
            
            // Bước 2: tạo resource 
            $newResource = mysqli_insert_id($conn);
            createResource('Permission ' . $action_id . '-' . $resource_type_id , 
            null, null, $resourceTypeId, $newResource);

            // tạo resource cho user_orgs
            
            // Bước 3: update version
            updateResourceTypeVersion($resourceTypeId);

            // Bước 4: gắn quan hệ người tạo
            // TODO

            

            add_notification("success", "Tạo quyền mới thành công", 4000);
            header("Location: $redirectLink");
            exit;
        }
        return [$label, null];
    }

    function editHandler($redirectLink, $resourceTypeId) {
        global $conn;

        $permission_id = $_GET['id'] ?? null;
          
        if ($permission_id) {
            if(!hasPermission($_SESSION['user']['id'], 'Create', $permission_id, 'permissions')) {
                redirectWithError("Bạn không có quyền truy cập vào trang này.", $redirectLink);
            }

            $query = "SELECT * FROM permissions WHERE id = $permission_id";
            $result = query($conn, $query);
            if ($result && mysqli_num_rows($result) > 0) {
                $permission = mysqli_fetch_assoc($result);
                $label = "Chỉnh sửa quyền ". $permission['id'];
            } else {
                add_notification("error", " Không tìm thấy quyền", 4000);
                exit;
            }
        } else {
            add_notification("error", "quyền không hợp lệ", 4000);
            exit;
        }

        if($_SERVER['REQUEST_METHOD'] == "POST"){
            $action_id = $_POST['action_id'];
            $resource_type_id = $_POST['resource_type_id'];

            // Bước 1: update
            $updateQuery =  "UPDATE permissions SET 
                                action_id = '$action_id',
                                resource_type_id = '$resource_type_id'
                            WHERE id = $permission_id";

            if (! query($conn, $updateQuery)) {
                redirectWithError("Lỗi khi cập nhật quyền: " . mysqli_error($conn), $redirectLink);
            }

            // Bước 2: cập nhật resource tương ứng
            $resourceId = getResourceId($resourceTypeId, $permission_id);
            updateResource($resourceId, 'Permission ' . $action_id . '-' . $resource_type_id, 
            null, 1, $resourceTypeId, $permission_id);

            // Bước 3: update version
            updateResourceTypeVersion($resourceTypeId);

            // Bước 4: gắn quan hệ người edit
            // TODO

            add_notification("success", "Update quyền $permission_id thành công", 4000);
            header("Location: $redirectLink");
            exit;
        }

        return [$label, $permission];
    }


    if ($mode === 'create') {
        [$label, $permission] = createHandler($redirectLink, $resourceTypeId);
    } else if ($mode === 'edit') {
        [$label, $permission] = editHandler($redirectLink, $resourceTypeId);
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
    <form action="" method="post" class="form">
        
        <h1 class="page-title"><?php echo $label ?></h1>

        <div class="form-group">
            <label for="action-id">Hành động</label>
            <select name="action_id" id="action-id" class="input-select" required>
                <?php while ($action = mysqli_fetch_assoc($actionsResult)): ?>
                    <option value="<?php echo $action['id'] ?>" <?php echo isset($permission) && $permission['action_id'] == $action['id'] ? 'selected' : '' ?>>
                        <?php echo htmlspecialchars($action['name']) ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="resource-type-id">quyền</label>
            <select name="resource_type_id" id="resource-type-id" class="input-select" required>
                <?php while ($resourceType = mysqli_fetch_assoc($resourceTypesResult)): ?>
                    <option value="<?php echo $resourceType['id'] ?>" <?php echo isset($permission) && $permission['resource_type_id'] == $resourceType['id'] ? 'selected' : '' ?>>
                        <?php echo htmlspecialchars($resourceType['name']) ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>

        <div class="form-buttons">
            <button type="button" onclick="location.href='<?= $redirectLink ?>'" class="button-cancel">Hủy</button>
            <input type="submit" value="<?= $mode === 'edit' ? 'Cập nhật' : 'Thêm' ?>" class="button-submit">
        </div>
    </form>
</body>
</html>