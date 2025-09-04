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
    $resourceTypeName = 'resource_types';
    $resourceTypeId = getResourceTypeByName($resourceTypeName)['id'];

    function createHandler($redirectLink, $resourceTypeId){
        global $conn;

        if(!hasPermission($_SESSION['user']['id'], 'Create', null, 'resource_types')) {
            redirectWithError("Bạn không có quyền truy cập vào trang này.", $redirectLink);
        }
        $label = "Tạo loại tài nguyên mới";
        if ($_SERVER['REQUEST_METHOD'] == "POST") {
            $resourceTypeName = $_POST["resource_type_name"];
            $resourceTypeVersion = $_POST["resource_type_version"];

            // Bước 1: insert
            $insertQuery = "INSERT INTO resource_types (name, version) 
                            VALUES ('$resourceTypeName', '$resourceTypeVersion')";

            if (!query($conn, $insertQuery)) {
                redirectWithError("Lỗi khi tạo loại tài nguyên mới: " . mysqli_error($conn), $redirectLink);
            }
            
            // Bước 2: tạo resource 
            $newResource = mysqli_insert_id($conn);
            createResource($resourceTypeName, "Resource for $resourceTypeName", null, $resourceTypeId, $newResource);

            // tạo resource cho user_orgs
            
            // Bước 3: update version
            updateResourceTypeVersion($resourceTypeId);

            // Bước 4: gắn quan hệ người tạo
            // TODO

            

            add_notification("success", "Tạo loại tài nguyên mới thành công", 4000);
            header("Location: $redirectLink");
            exit;
        }
        return [$label, null];
    }

    function editHandler($redirectLink, $resourceTypeId) {
        global $conn;

        $resource_type_id = $_GET['id'] ?? null;
          
        if ($resource_type_id) {
            if(!hasPermission($_SESSION['user']['id'], 'Create', $resource_type_id, 'resource_types')) {
                redirectWithError("Bạn không có quyền truy cập vào trang này.", $redirectLink);
            }

            $query = "SELECT * FROM resource_types WHERE id = $resource_type_id";
            $result = query($conn, $query);
            if ($result && mysqli_num_rows($result) > 0) {
                $resource_type = mysqli_fetch_assoc($result);
                $label = "Chỉnh sửa loại tài nguyên ". $resource_type['name'];
            } else {
                add_notification("error", " Không tìm thấy loại tài nguyên", 4000);
                exit;
            }
        } else {
            add_notification("error", "Loại tài nguyên không hợp lệ", 4000);
            exit;
        }

        if($_SERVER['REQUEST_METHOD'] == "POST"){
            $resourceTypeName = $_POST["resource_type_name"];
            $resourceTypeVersion = $_POST["resource_type_version"];

            // Bước 1: update
            $updateQuery =  "UPDATE resource_types SET 
                                name = '$resourceTypeName',
                                version = '$resourceTypeVersion'
                            WHERE id = $resource_type_id";

            if (! query($conn, $updateQuery)) {
                redirectWithError("Lỗi khi cập nhật loại tài nguyên: " . mysqli_error($conn), $redirectLink);
            }

            // Bước 2: cập nhật resource tương ứng
            $resourceId = getResourceId($resourceTypeId, $resource_type_id);
            updateResource($resourceId, $resourceTypeName, null, 1, $resourceTypeId, $resource_type_id);

            // Bước 3: update version
            updateResourceTypeVersion($resourceTypeId);

            // Bước 4: gắn quan hệ người edit
            // TODO

            add_notification("success", "Update loại tài nguyên $resourceTypeName thành công", 4000);
            header("Location: $redirectLink");
            exit;
        }

        return [$label, $resource_type];
    }


    if ($mode === 'create') {
        [$label, $resource_type] = createHandler($redirectLink, $resourceTypeId);
    } else if ($mode === 'edit') {
        [$label, $resource_type] = editHandler($redirectLink, $resourceTypeId);
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
            <label for="resource-type-name">Tên loại tài nguyên</label>
            <input type="text" name="resource_type_name" id="resource_type-name" class="input-text"
                <?php if (isset($resource_type)) echo "value='" . $resource_type["name"] . "'"; ?>>
        </div>

        <div class="form-group">
            <label for="resource-type-version">Version</label>
            <input type="number" name="resource_type_version" id="resource_type-version" class="input-text"
                value="<?php echo isset($resource_type) && !empty($resource_type['version']) ? $resource_type['version'] : 1; ?>">
        </div>

        <div class="form-buttons">
            <button type="button" onclick="location.href='<?php echo $redirectLink ?>'" class="button-cancel">Hủy</button>
            <input type="submit" value="<?= $mode === 'edit' ? 'Cập nhật' : 'Thêm' ?>" class="button-submit">
        </div>
    </form>

</body>
</html>