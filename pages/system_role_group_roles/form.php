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
    $resourceTypeName = 'system_role_group_roles';
    $resourceTypeId = getResourceTypeByName($resourceTypeName)['id'];
    // TODO: chưa xử lý trường hợp quan hệ với chính nó
    $orgIDQuery = "SELECT org_id FROM system_role_groups WHERE id = " . ($_GET['group_id'] ?? 0);
    $org_id = mysqli_fetch_assoc(query($conn, $orgIDQuery))['org_id'] ?? null;
    if(!$org_id) {
        echo "<h2>Vui lòng chọn tổ chức để xem dữ liệu.</h2>";
        exit;
    }

    $systemRoleList = systemRoleList($org_id);

    function systemRoleList($org_id) {
        global $conn;
        $query = "SELECT id, name FROM system_roles WHERE org_id = $org_id";
        return query($conn, $query);
    }

    function createHandler($redirectLink, $resourceTypeId){
        global $conn;

        if(!hasPermission($_SESSION['user']['id'], 'Create', null, 'system_role_group_roles')) {
            redirectWithError("Bạn không có quyền truy cập vào trang này.", $redirectLink);
        }
        $label = "Thêm vai trò mới cho nhóm vai trò";
        if ($_SERVER['REQUEST_METHOD'] == "POST") {
            $group_id = $_POST['group_id'];
            $systemRoleId = $_POST['system_role_id'];

            // Bước 1: insert
            $insertQuery = "INSERT INTO system_role_group_roles (system_role_group_id, system_role_id) 
                            VALUES ($group_id, $systemRoleId)";

            if (!query($conn, $insertQuery)) {
                redirectWithError("Lỗi khi Thêm vai trò mới cho nhóm vai trò: " . mysqli_error($conn), $redirectLink);
            }
            
            // Bước 2: tạo resource 
            $newResource = mysqli_insert_id($conn);
            createResource('system role group role ' . $group_id . '-' . $systemRoleId , 
            null, null, $resourceTypeId, $newResource);
            
            // Bước 3: update version
            updateResourceTypeVersion($resourceTypeId);

            // Bước 4: gắn quan hệ người tạo
            // TODO

            

            add_notification("success", "Thêm vai trò mới cho nhóm vai trò thành công", 4000);
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
        [$label, $system_role_group_role] = createHandler($redirectLink, $resourceTypeId);
    } else if ($mode === 'edit') {
        [$label, $system_role_group_role] = editHandler($redirectLink, $resourceTypeId);
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
            <label for="system_role">Vai trò</label>
            <?php
                echo '<select name="system_role_id" id="system-role-id" class="input-select">';
                while ($row = mysqli_fetch_assoc($systemRoleList)) {
                    echo "<option value='" . $row['id'] . "'>" . htmlspecialchars($row['name']) . "</option>";
                }
                echo '</select>';
            ?>
        </div>

        <input type="hidden" name="group_id" value="<?php echo $_GET['group_id'] ?? '' ?>">            
        

        <div class="form-buttons">
            <button type="button" onclick="location.href='<?= $redirectLink ?>'" class="button-cancel">Hủy</button>
            <input type="submit" value="<?= $mode === 'edit' ? 'Cập nhật' : 'Thêm' ?>" class="button-submit">
        </div>
    </form>
</body>
</html>