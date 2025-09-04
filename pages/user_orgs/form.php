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
    $resourceTypeName = 'users';
    $resourceTypeId = getResourceTypeByName($resourceTypeName)['id'];

    function createHandler($redirectLink, $resourceTypeId){
        global $conn;

        if(!hasPermission($_SESSION['user']['id'], 'Create', null, 'users')) {
            redirectWithError("Bạn không có quyền truy cập vào trang này.", $redirectLink);
        }
        $label = "Tạo người dùng mới";
        if ($_SERVER['REQUEST_METHOD'] == "POST") {
            $user_name = $_POST["user_name"];
            $org_id = isset($_POST["org_id"]) && $_POST["org_id"] !== '' ? (int)$_POST["org_id"] : "NULL";

            // Bước 1: insert
            $insertQuery = "INSERT INTO users (name) 
                            VALUES ('$user_name')";

            if (!query($conn, $insertQuery)) {
                redirectWithError("Lỗi khi tạo người dùng mới: " . mysqli_error($conn), $redirectLink);
            }
            $newUserResource = mysqli_insert_id($conn);

            // insert vào user_orgs
            $insertIntoUserOrgs = "INSERT INTO user_orgs (user_id, org_id) VALUES (". mysqli_insert_id($conn) .", $org_id)";
            if (!query($conn, $insertIntoUserOrgs)) {
                redirectWithError("Lỗi khi gán tổ chức cho người dùng mới: " . mysqli_error($conn), $redirectLink);
            }
            $newUserOrgResource = mysqli_insert_id($conn);

            // Bước 2: tạo resource 
            
            createResource($user_name, "Resource for $user_name", null, $resourceTypeId, $newUserResource);

            // tạo resource cho user_orgs
            createResource("user_orgs_". $newUserOrgResource, "Resource for user_orgs_". $newUserOrgResource, null, getResourceTypeByName('user_orgs')['id'], $newUserOrgResource);
            
            // Bước 3: update version
            updateResourceTypeVersion($resourceTypeId);
            updateResourceTypeVersion(getResourceTypeByName('user_orgs')['id']);

            // Bước 4: gắn quan hệ người tạo
            // TODO

            

            add_notification("success", "Tạo người dùng mới thành công", 4000);
            header("Location: $redirectLink");
            exit;
        }
        return [$label, null];
    }

    function editHandler($redirectLink, $resourceTypeId) {
        global $conn;

        $user_id = $_GET['id'] ?? null;
          
        if ($user_id) {
            if(!hasPermission($_SESSION['user']['id'], 'Create', $user_id, 'users')) {
                redirectWithError("Bạn không có quyền truy cập vào trang này.", $redirectLink);
            }

            $query = "SELECT * FROM users WHERE id = $user_id";
            $result = query($conn, $query);
            if ($result && mysqli_num_rows($result) > 0) {
                $user = mysqli_fetch_assoc($result);
                $label = "Chỉnh sửa người dùng ". $user['name'];
            } else {
                add_notification("error", "System role not found", 4000);
                exit;
            }
        } else {
            add_notification("error", "Invalid System role group ID", 4000);
            exit;
        }

        if($_SERVER['REQUEST_METHOD'] == "POST"){
            $user_name = $_POST["user_name"];

            // Bước 1: update
            $updateQuery =  "UPDATE users SET 
                                name = '$user_name'
                            WHERE id = $user_id";

            if (! query($conn, $updateQuery)) {
                redirectWithError("Lỗi khi cập nhật người dùng: " . mysqli_error($conn), $redirectLink);
            }

            // Bước 2: cập nhật resource tương ứng
            $resourceId = getResourceId($resourceTypeId, $user_id);
            updateResource($resourceId, $user_name, null, 1, $resourceTypeId, $user_id);

            // Bước 3: update version
            updateResourceTypeVersion($resourceTypeId);

            // Bước 4: gắn quan hệ người edit
            // TODO

            add_notification("success", "Update người dùng $user_name thành công", 4000);
            header("Location: $redirectLink");
            exit;
        }

        return [$label, $user];
    }


    if ($mode === 'create') {
        [$label, $user] = createHandler($redirectLink, $resourceTypeId);
    } else if ($mode === 'edit') {
        [$label, $user] = editHandler($redirectLink, $resourceTypeId);
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
            <label for="user-name">Tên người dùng</label>
            <input type="text" name="user_name" id="user-name" class="input-text"
                <?php if (isset($user)) echo "value='" . $user["name"] . "'"; ?>>
        </div>

        <input type="hidden" name="org_id" value="<?php echo $_GET['org_id'] ?? '' ?>">

        <div class="form-buttons">
            <button type="button" onclick="location.href='<?php echo $redirectLink ?>'" class="button-cancel">Hủy</button>
            <input type="submit" value="<?= $mode === 'edit' ? 'Cập nhật' : 'Thêm' ?>" class="button-submit">
        </div>
    </form>

</body>
</html>