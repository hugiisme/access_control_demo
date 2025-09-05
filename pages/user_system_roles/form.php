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
    $resourceTypeName = 'user_system_roles';
    $resourceTypeId = getResourceTypeByName($resourceTypeName)['id'];
    // TODO: chưa xử lý trường hợp quan hệ với chính nó
    
    $role_id = $_GET['role_id'] ?? null;
    if(!$role_id) {
        echo '<h2>Vui lòng chọn vai trò để xem dữ liệu</h2>';
        exit;
    }
    $org_id = $_GET['org_id'] ?? null;
    if ($org_id == null) {
        $org_id_query = "SELECT org_id FROM system_roles WHERE id = $role_id";
        $org_id_results = query($conn, $org_id_query);
        if(!$org_id_results){
            echo '<h2>Không thể thực hiện gán quyền cho vai trò nếu ko biết nhóm vai trò</h2>';
            exit;
        }
    }

    $userList = getUserList(mysqli_fetch_assoc($org_id_results)['org_id']);

    function createHandler($redirectLink, $resourceTypeId){
        global $conn;

        if(!hasPermission($_SESSION['user']['id'], 'Create', null, 'user_system_roles')) {
            redirectWithError("Bạn không có quyền truy cập vào trang này.", $redirectLink);
        }
        $label = "Gán người dùng cho vai trò";
        if ($_SERVER['REQUEST_METHOD'] == "POST") {
            $role_id = $_POST['role_id'];
            $userId = $_POST['userId'];

            // Bước 1: insert
            $insertQuery = "INSERT INTO user_system_roles (system_role_id, user_id) 
                            VALUES ($role_id, $userId)";

            if (!query($conn, $insertQuery)) {
                redirectWithError("Lỗi khi Gán người dùng cho vai trò: " . mysqli_error($conn), $redirectLink);
            }
            
            // Bước 2: tạo resource 
            $newResource = mysqli_insert_id($conn);
            createResource('system role permissions ' . $role_id . '-' . $userId , 
            null, null, $resourceTypeId, $newResource);
            
            // Bước 3: update version
            updateResourceTypeVersion($resourceTypeId);

            // Bước 4: gắn quan hệ người tạo
            // TODO

            

            add_notification("success", "Gán người dùng cho vai trò thành công", 4000);
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
        [$label, $user_system_roles] = createHandler($redirectLink, $resourceTypeId);
    } else if ($mode === 'edit') {
        [$label, $user_system_roles] = editHandler($redirectLink, $resourceTypeId);
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
                echo '<select name="userId" id="user-id" class="input-select">';
                while ($row = mysqli_fetch_assoc($userList)) {
                    echo "<option value='" . $row['id'] . "'>" . htmlspecialchars($row['name'] ) . "</option>";
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