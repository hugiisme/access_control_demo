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
    $resourceTypeName = 'system_role_permissions';
    $resourceTypeId = getResourceTypeByName($resourceTypeName)['id'];
    // TODO: chưa xử lý trường hợp quan hệ với chính nó
    
    $role_id = $_GET['role_id'] ?? null;
    if(!$role_id) {
        echo '<h2>Vui lòng chọn vai trò để xem dữ liệu</h2>';
        exit;
    }
    $group_id = $_GET['group_id'] ?? null;
    if ($group_id == null) {
        $group_id_query = "SELECT * FROM system_role_group_roles WHERE system_role_id = $role_id ORDER BY id LIMIT 1";
        $group_id_results = query($conn, $group_id_query);
        if(!$group_id_results){
            echo '<h2>Không thể thực hiện gán quyền cho vai trò nếu ko biết nhóm vai trò</h2>';
            exit;
        }
        $group_id = mysqli_fetch_assoc($group_id_results)['system_role_group_id'];
    }

    $permissionList = group_permissionList($group_id);

    function createHandler($redirectLink, $resourceTypeId){
        global $conn;

        if(!hasPermission($_SESSION['user']['id'], 'Create', null, 'system_role_permissions')) {
            redirectWithError("Bạn không có quyền truy cập vào trang này.", $redirectLink);
        }
        $label = "Gán quyền mới cho vai trò";
        if ($_SERVER['REQUEST_METHOD'] == "POST") {
            $role_id = $_POST['role_id'];
            $permissionId = $_POST['permission_id'];

            // Bước 1: insert
            $insertQuery = "INSERT INTO system_role_permissions (system_role_id, permission_id) 
                            VALUES ($role_id, $permissionId)";

            if (!query($conn, $insertQuery)) {
                redirectWithError("Lỗi khi Gán quyền mới cho vai trò: " . mysqli_error($conn), $redirectLink);
            }
            
            // Bước 2: tạo resource 
            $newResource = mysqli_insert_id($conn);
            createResource('system role permissions ' . $role_id . '-' . $permissionId , 
            null, null, $resourceTypeId, $newResource);
            
            // Bước 3: update version
            updateResourceTypeVersion($resourceTypeId);

            // Bước 4: gắn quan hệ người tạo
            // TODO

            

            add_notification("success", "Gán quyền mới cho vai trò thành công", 4000);
            header("Location: $redirectLink");
            exit;
        }
        return [$label, null];
    }

    function editHandler($redirectLink, $resourceTypeId) {
        global $conn;

        return ['Không thể edit vì nó chỉ là gán', null];
    }


    if ($mode === 'create') {
        [$label, $system_role_permission] = createHandler($redirectLink, $resourceTypeId);
    } else if ($mode === 'edit') {
        [$label, $system_role_permission] = editHandler($redirectLink, $resourceTypeId);
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
            <label for="permission">Quyền</label>
            <?php
                echo '<select name="permission_id" id="permission-id" class="input-select">';
                while ($row = mysqli_fetch_assoc($permissionList)) {
                    echo "<option value='" . $row['id'] . "'>" . htmlspecialchars($row['action_name'] . ' - ' . $row['resource_type_name']) . "</option>";
                }
                echo '</select>';
            ?>
        </div>

        <input type="hidden" name="role_id" value="<?php echo $_GET['role_id'] ?? '' ?>">            
        

        <div class="form-buttons">
            <button type="button" onclick="location.href='<?= $redirectLink ?>'" class="button-cancel">Hủy</button>
            <input type="submit" value="<?= $mode === 'edit' ? 'Cập nhật' : 'Thêm' ?>" class="button-submit">
        </div>
    </form>
</body>
</html>