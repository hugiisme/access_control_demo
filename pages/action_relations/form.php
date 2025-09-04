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
    $resourceTypeName = 'action_relations';
    $resourceTypeId = getResourceTypeByName($resourceTypeName)['id'];
    $parentActionList = getActionList();
    $childActionList = getActionList();
    // TODO: chưa xử lý trường hợp quan hệ với chính nó

    function createHandler($redirectLink, $resourceTypeId){
        global $conn;

        if(!hasPermission($_SESSION['user']['id'], 'Create', null, 'action_relations')) {
            redirectWithError("Bạn không có quyền truy cập vào trang này.", $redirectLink);
        }
        $label = "Tạo quan hệ hành động mới";
        if ($_SERVER['REQUEST_METHOD'] == "POST") {
            $parentActionId = $_POST['parent_action_relation_id'];
            $childActionId = $_POST['child_action_relation_id'];

            // Bước 1: insert
            $insertQuery = "INSERT INTO action_relations (parent_action_relation_id, child_action_relation_id) 
                            VALUES ('$parentActionId', '$childActionId')";

            if (!query($conn, $insertQuery)) {
                redirectWithError("Lỗi khi tạo quan hệ hành động mới: " . mysqli_error($conn), $redirectLink);
            }
            
            // Bước 2: tạo resource 
            $newResource = mysqli_insert_id($conn);
            createResource('Action relation ' . $parentActionId . '-' . $childActionId , 
            null, null, $resourceTypeId, $newResource);

            // tạo resource cho user_orgs
            
            // Bước 3: update version
            updateResourceTypeVersion($resourceTypeId);

            // Bước 4: gắn quan hệ người tạo
            // TODO

            

            add_notification("success", "Tạo quan hệ hành động mới thành công", 4000);
            header("Location: $redirectLink");
            exit;
        }
        return [$label, null];
    }

    function editHandler($redirectLink, $resourceTypeId) {
        global $conn;

        $action_relation_id = $_GET['id'] ?? null;
          
        if ($action_relation_id) {
            if(!hasPermission($_SESSION['user']['id'], 'Create', $action_relation_id, 'action_relations')) {
                redirectWithError("Bạn không có quyền truy cập vào trang này.", $redirectLink);
            }

            $query = "SELECT * FROM action_relations WHERE id = $action_relation_id";
            $result = query($conn, $query);
            if ($result && mysqli_num_rows($result) > 0) {
                $actionRelation = mysqli_fetch_assoc($result);
                $label = "Chỉnh sửa quan hệ hành động ". $actionRelation['id'];
            } else {
                add_notification("error", " Không tìm thấy quan hệ hành động", 4000);
                exit;
            }
        } else {
            add_notification("error", "quan hệ hành động không hợp lệ", 4000);
            exit;
        }

        if($_SERVER['REQUEST_METHOD'] == "POST"){
            $parentActionId = $_POST['parent_action_relation_id'];
            $childActionId = $_POST['child_action_relation_id'];

            // Bước 1: update
            $updateQuery =  "UPDATE action_relations SET 
                                parent_action_id = '$parentActionId',
                                child_action_id = '$childActionId'
                            WHERE id = $action_relation_id";

            if (! query($conn, $updateQuery)) {
                redirectWithError("Lỗi khi cập nhật quan hệ hành động: " . mysqli_error($conn), $redirectLink);
            }

            // Bước 2: cập nhật resource tương ứng
            $resourceId = getResourceId($resourceTypeId, $action_relation_id);
            updateResource($resourceId, 'Action relation ' . $parentActionId . '-' . $childActionId ,
             null, 1, $resourceTypeId, $action_relation_id);

            // Bước 3: update version
            updateResourceTypeVersion($resourceTypeId);

            // Bước 4: gắn quan hệ người edit
            // TODO

            add_notification("success", "Update quan hệ hành động $action_relation_id thành công", 4000);
            header("Location: $redirectLink");
            exit;
        }

        return [$label, $actionRelation];
    }


    if ($mode === 'create') {
        [$label, $actionRelation] = createHandler($redirectLink, $resourceTypeId);
    } else if ($mode === 'edit') {
        [$label, $actionRelation] = editHandler($redirectLink, $resourceTypeId);
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
            <label for="action-name">Tên quan hệ hành động cha</label>
            <?php
                echo '<select name="parent_action_relation_id" id="parent-action-id" class="input-select">';
                while ($row = mysqli_fetch_assoc($parentActionList)) {
                    $selected = (isset($actionRelation) && $actionRelation['parent_action_relation_id'] == $row['id']) ? 'selected' : '';
                    echo "<option value='" . $row['id'] . "' $selected>" . htmlspecialchars($row['name']) . "</option>";
                }
                echo '</select>';
            ?>
        </div>

        <div class="form-group">
            <label for="action-name">Tên quan hệ hành động con</label>
            <?php
                echo '<select name="child_action_relation_id" id="child-action-id" class="input-select">';
                while ($row = mysqli_fetch_assoc($childActionList)) {
                    $selected = (isset($actionRelation) && $actionRelation['child_action_relation_id'] == $row['id']) ? 'selected' : '';
                    echo "<option value='" . $row['id'] . "' $selected>" . htmlspecialchars($row['name']) . "</option>";
                }
                echo '</select>';
            ?>
        </div>
        

        <div class="form-buttons">
            <button type="button" onclick="location.href='<?= $redirectLink ?>'" class="button-cancel">Hủy</button>
            <input type="submit" value="<?= $mode === 'edit' ? 'Cập nhật' : 'Thêm' ?>" class="button-submit">
        </div>
    </form>
</body>
</html>