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
    $resourceTypeName = 'system_role_groups';
    $resourceTypeId = getResourceTypeByName($resourceTypeName)['id'];

    function getParentGroups($conn) {
        $query = "SELECT id, name FROM system_role_groups";
        $result = query($conn, $query);
        if (!$result) {
            add_notification("error", "Lỗi khi lấy danh sách nhóm vai trò cha", 4000);
            echo "Query failed: " . mysqli_error($conn);
            exit;
        }
        return $result;
    }

    $parentGroupsOptions = getParentGroups($conn);

    function createHandler($redirectLink, $resourceTypeId){
        global $conn;

        if(!hasPermission($_SESSION['user']['id'], 'Create', null, 'system_role_groups')) {
            redirectWithError("Bạn không có quyền truy cập vào trang này.", $redirectLink);
        }
        $label = "Tạo nhóm vai trò mới";
        if ($_SERVER['REQUEST_METHOD'] == "POST") {
            $system_role_group_name = $_POST["system_role_group_name"];
            $system_role_group_description = $_POST["system_role_group_description"];
            $org_id = isset($_POST["org_id"]) && $_POST["org_id"] !== '' ? (int)$_POST["org_id"] : "NULL";
            $parent_group_id = isset($_POST["parent_group_id"]) && $_POST["parent_group_id"] !== '' ? (int)$_POST["parent_group_id"] : "NULL";

            // Bước 1: insert
            $insertQuery = "INSERT INTO system_role_groups (name, description, org_id, parent_group_id) 
                            VALUES ('$system_role_group_name', '$system_role_group_description', $org_id, $parent_group_id)";

            if (!query($conn, $insertQuery)) {
                redirectWithError("Lỗi khi tạo nhóm vai trò mới: " . mysqli_error($conn), $redirectLink);
            }
            // Bước 2: tạo resource 
            $newResourceId = mysqli_insert_id($conn);
            createResource($system_role_group_name, "Resource for $system_role_group_name", null, $resourceTypeId, $newResourceId);
            
            // Bước 3: update version
            updateResourceTypeVersion($resourceTypeId);

            // Bước 4: gắn quan hệ người tạo
            // TODO

            add_notification("success", "Tạo nhóm vai trò mới thành công", 4000);
            header("Location: $redirectLink");
            exit;
        }
        return [$label, null];
    }

    function editHandler($redirectLink, $resourceTypeId) {
        global $conn;

        $system_role_group_id = $_GET['id'] ?? null;
          
        if ($system_role_group_id) {
            if(!hasPermission($_SESSION['user']['id'], 'Create', $system_role_group_id, 'system_role_groups')) {
                redirectWithError("Bạn không có quyền truy cập vào trang này.", $redirectLink);
            }

            $query = "SELECT * FROM system_role_groups WHERE id = $system_role_group_id";
            $result = query($conn, $query);
            if ($result && mysqli_num_rows($result) > 0) {
                $system_role_group = mysqli_fetch_assoc($result);
                $label = "Chỉnh sửa nhóm vai trò ". $system_role_group['name'];
            } else {
                add_notification("error", "System role group not found", 4000);
                exit;
            }
        } else {
            add_notification("error", "Invalid System role group ID", 4000);
            exit;
        }

        if($_SERVER['REQUEST_METHOD'] == "POST"){
            $system_role_group_name = $_POST["system_role_group_name"];
            $system_role_group_description = $_POST["system_role_group_description"];
            $org_id = isset($_POST["org_id"]) && $_POST["org_id"] !== '' ? (int)$_POST["org_id"] : "NULL";
            $parent_group_id = isset($_POST["parent_group_id"]) && $_POST["parent_group_id"] !== '' ? (int)$_POST["parent_group_id"] : "NULL";

            // Bước 1: update
            $updateQuery =  "UPDATE resource_types SET 
                                name='$system_role_group_name', 
                                description='$system_role_group_description', 
                                org_id=$org_id,
                                parent_group_id=$parent_group_id
                            WHERE id=$system_role_group_id";

            if (! query($conn, $updateQuery)) {
                redirectWithError("Lỗi khi cập nhật nhóm vai trò: " . mysqli_error($conn), $redirectLink);
            }

            // Bước 2: cập nhật resource tương ứng
            $resourceId = getResourceId($resourceTypeId, $system_role_group_id);
            updateResource($resourceId, $system_role_group_name, $system_role_group_description, 1, $resourceTypeId, $system_role_group_id);

            // Bước 3: update version
            updateResourceTypeVersion($resourceTypeId);

            // Bước 4: gắn quan hệ người edit
            // TODO

            add_notification("success", "Update nhóm vai trò $system_role_group_name thành công", 4000);
            header("Location: $redirectLink");
            exit;
        }

        return [$label, $system_role_group];
    }


    if ($mode === 'create') {
        [$label, $system_role_group] = createHandler($redirectLink, $resourceTypeId);
    } else if ($mode === 'edit') {
        [$label, $system_role_group] = editHandler($redirectLink, $resourceTypeId);
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
            <label for="system-role-group-name">Tên nhóm vai trò</label>
            <input type="text" name="system_role_group_name" id="system-role-group-name" class="input-text"
                <?php if (isset($system_role_group)) echo "value='" . $system_role_group["name"] . "'"; ?>>
        </div>

        <div class="form-group">
            <label for="system-role-group-description">Mô tả nhóm vai trò</label>
            <input type="text" name="system_role_group_description" id="system-role-group-description" class="input-text"
                <?php if (isset($system_role_group)) echo "value='" . $system_role_group["description"] . "'"; ?>>
        </div>

        <input type="hidden" name="org_id" value="<?php echo $_GET['org_id'] ?? '' ?>">

        <div class="form-group">
            <label for="parent-group">Nhóm vai trò cha</label>
            <select name="parent_group_id" id="parent-group" class="input-text">
                <option value="">NULL</option>
                <?php
                    if ($parentGroupsOptions && mysqli_num_rows($parentGroupsOptions) > 0) {
                        while ($row = mysqli_fetch_assoc($parentGroupsOptions)) {
                            $selected = (isset($system_role_group) && $system_role_group['parent_group_id'] == $row['id']) ? 'selected' : '';
                            echo "<option value='" . $row['id'] . "' $selected>" . htmlspecialchars($row['name']) . "</option>";
                        }
                    }
                ?>
            </select>
        </div>

        <div class="form-buttons">
            <button type="button" onclick="location.href='<?php echo $redirectLink ?>'" class="button-cancel">Hủy</button>
            <input type="submit" value="<?= $mode === 'edit' ? 'Cập nhật' : 'Thêm' ?>" class="button-submit">
        </div>
    </form>

</body>
</html>