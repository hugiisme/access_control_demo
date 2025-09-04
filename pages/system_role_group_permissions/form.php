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
    $resourceTypeName = 'org_permissions';
    $resourceTypeId = getResourceTypeByName($resourceTypeName)['id'];
    // TODO: chưa xử lý trường hợp quan hệ với chính nó
    $permissionList = permissionList();

    

    function createHandler($redirectLink, $resourceTypeId){
        global $conn;

        if(!hasPermission($_SESSION['user']['id'], 'Create', null, 'org_permissions')) {
            redirectWithError("Bạn không có quyền truy cập vào trang này.", $redirectLink);
        }
        $label = "Gán quyền mới cho tổ chức";
        if ($_SERVER['REQUEST_METHOD'] == "POST") {
            $permissionId = $_POST['permission_id'];
            $org_id = $_POST['org_id'];

            // Bước 1: insert
            $insertQuery = "INSERT INTO org_permissions (org_id, permission_id) 
                            VALUES ('$org_id', '$permissionId')";

            if (!query($conn, $insertQuery)) {
                redirectWithError("Lỗi khi Gán quyền mới cho tổ chức: " . mysqli_error($conn), $redirectLink);
            }
            
            // Bước 2: tạo resource 
            $newResource = mysqli_insert_id($conn);
            createResource('Org Permission ' . $org_id . '-' . $permissionId , 
            null, null, $resourceTypeId, $newResource);
            
            // Bước 3: update version
            updateResourceTypeVersion($resourceTypeId);

            // Bước 4: gắn quan hệ người tạo
            // TODO

            

            add_notification("success", "Gán quyền mới cho tổ chức thành công", 4000);
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
        [$label, $orgPermission] = createHandler($redirectLink, $resourceTypeId);
    } else if ($mode === 'edit') {
        [$label, $orgPermission] = editHandler($redirectLink, $resourceTypeId);
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

        <input type="hidden" name="org_id" value="<?php echo $_GET['org_id'] ?? '' ?>">            
        

        <div class="form-buttons">
            <button type="button" onclick="location.href='<?= $redirectLink ?>'" class="button-cancel">Hủy</button>
            <input type="submit" value="<?= $mode === 'edit' ? 'Cập nhật' : 'Thêm' ?>" class="button-submit">
        </div>
    </form>
</body>
</html>