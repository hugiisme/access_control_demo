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
    $resourceTypeName = 'actions';
    $resourceTypeId = getResourceTypeByName($resourceTypeName)['id'];

    function createHandler($redirectLink, $resourceTypeId){
        global $conn;

        if(!hasPermission($_SESSION['user']['id'], 'Create', null, 'actions')) {
            redirectWithError("Bạn không có quyền truy cập vào trang này.", $redirectLink);
        }
        $label = "Tạo hành động mới";
        if ($_SERVER['REQUEST_METHOD'] == "POST") {
            $actionName = $_POST['action_name'];
            $actiondescription = $_POST['action_description'];

            // Bước 1: insert
            $insertQuery = "INSERT INTO actions (name, description) 
                            VALUES ('$actionName', '$actiondescription')";

            if (!query($conn, $insertQuery)) {
                redirectWithError("Lỗi khi tạo hành động mới: " . mysqli_error($conn), $redirectLink);
            }
            
            // Bước 2: tạo resource 
            $newResource = mysqli_insert_id($conn);
            createResource($actionName, "Resource for $actionName", null, $resourceTypeId, $newResource);

            // tạo resource cho user_orgs
            
            // Bước 3: update version
            updateResourceTypeVersion($resourceTypeId);

            // Bước 4: gắn quan hệ người tạo
            // TODO

            

            add_notification("success", "Tạo hành động mới thành công", 4000);
            header("Location: $redirectLink");
            exit;
        }
        return [$label, null];
    }

    function editHandler($redirectLink, $resourceTypeId) {
        global $conn;

        $action_id = $_GET['id'] ?? null;
          
        if ($action_id) {
            if(!hasPermission($_SESSION['user']['id'], 'Create', $action_id, 'actions')) {
                redirectWithError("Bạn không có quyền truy cập vào trang này.", $redirectLink);
            }

            $query = "SELECT * FROM actions WHERE id = $action_id";
            $result = query($conn, $query);
            if ($result && mysqli_num_rows($result) > 0) {
                $action = mysqli_fetch_assoc($result);
                $label = "Chỉnh sửa hành động ". $action['name'];
            } else {
                add_notification("error", " Không tìm thấy hành động", 4000);
                exit;
            }
        } else {
            add_notification("error", "hành động không hợp lệ", 4000);
            exit;
        }

        if($_SERVER['REQUEST_METHOD'] == "POST"){
            $actionName = $_POST['action_name'];
            $actiondescription = $_POST['action_description'];

            // Bước 1: update
            $updateQuery =  "UPDATE actions SET 
                                name = '$actionName',
                                description = '$actiondescription'
                            WHERE id = $action_id";

            if (! query($conn, $updateQuery)) {
                redirectWithError("Lỗi khi cập nhật hành động: " . mysqli_error($conn), $redirectLink);
            }

            // Bước 2: cập nhật resource tương ứng
            $resourceId = getResourceId($resourceTypeId, $action_id);
            updateResource($resourceId, $actionName, null, 1, $resourceTypeId, $action_id);

            // Bước 3: update version
            updateResourceTypeVersion($resourceTypeId);

            // Bước 4: gắn quan hệ người edit
            // TODO

            add_notification("success", "Update hành động $actionName thành công", 4000);
            header("Location: $redirectLink");
            exit;
        }

        return [$label, $action];
    }


    if ($mode === 'create') {
        [$label, $action] = createHandler($redirectLink, $resourceTypeId);
    } else if ($mode === 'edit') {
        [$label, $action] = editHandler($redirectLink, $resourceTypeId);
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
            <label for="action-name">Tên hành động</label>
            <input type="text" name="action_name" id="action-name" class="input-text"
                <?php if (isset($action)) echo "value='" . $action["name"] . "'"; ?>>
        </div>

        <div class="form-group">
            <label for="action-description">Mô tả hành động</label>
            <textarea name="action_description" id="action-description" class="input-textarea"
                <?php if (isset($action)) echo "value='" . htmlspecialchars($action["description"]) . "'"; ?>></textarea>
        </div>
        

        <div class="form-buttons">
            <button type="button" onclick="location.href='<?= $redirectLink ?>'" class="button-cancel">Hủy</button>
            <input type="submit" value="<?= $mode === 'edit' ? 'Cập nhật' : 'Thêm' ?>" class="button-submit">
        </div>
    </form>
</body>
</html>