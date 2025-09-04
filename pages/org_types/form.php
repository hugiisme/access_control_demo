<?php 
    include __DIR__ . "/../../config/database.php";
    include __DIR__ . "/../../includes/notify.php";
    include __DIR__ . "/../../includes/access_control.php";
    include __DIR__ . "/../../includes/manage_resource.php";
    include __DIR__ . "/../../includes/helper_functions.php";
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $mode = isset($_GET['id']) ? 'edit' : 'create';
    $redirectLink = $_GET['redirectLink'] ?? '/index.php';
    $resourceTypeName = 'org_types';
    $resourceTypeId = getResourceTypeByName($resourceTypeName)['id'];

    function createHandler($redirectLink, $resourceTypeId){
        global $conn;

        if(!hasPermission($_SESSION['user']['id'], 'Create', null, 'org_types')) {
            redirectWithError("Bạn không có quyền truy cập vào trang này.", $redirectLink);
        }

        $label = "Tạo loại tổ chức mới";

        if ($_SERVER['REQUEST_METHOD'] == "POST") {
            $org_type_name = $_POST["org_type_name"];

            // Bước 1: insert
            $insertQuery = "INSERT INTO org_types (name) VALUES ('$org_type_name')";
            if (!query($conn, $insertQuery)) {
                redirectWithError("Lỗi khi tạo loại tổ chức mới: " . mysqli_error($conn), $redirectLink);
            }

            // Bước 2: Tạo resource cho org_type vừa tạo
            createResource($org_type_name, "$org_type_name Description", null, $resourceTypeId, mysqli_insert_id($conn));

            // Bước 3: update version
            updateResourceTypeVersion($resourceTypeId);

            add_notification("success", "Tạo loại tổ chức mới thành công", 4000);
            header("Location: $redirectLink");
            exit;
        }
        return [$label, null];
    }

    function editHandler($redirectLink, $resourceTypeId) {
        global $conn;
        
        $orgTypeId = $_GET['id'] ?? null;
        if ($orgTypeId) {
            if(!hasPermission($_SESSION['user']['id'], 'Edit', $orgTypeId, 'org_types')) {
                redirectWithError("Bạn không có quyền truy cập vào trang này.", $redirectLink);
            }

            // Lấy thông tin loại tổ chức hiện tại
            $query = "SELECT * FROM org_types WHERE id = $orgTypeId";
            $result = query($conn, $query);
            if ($result && mysqli_num_rows($result) > 0) {
                $orgType = mysqli_fetch_assoc($result);
                $label = "Chỉnh sửa loại tổ chức ". $orgType['name'];
            } else {
                add_notification("error", "Loại tổ chức không tồn tại", 4000);
                exit;
            }
        } else {
            add_notification("error", "ID loại tổ chức không hợp lệ", 4000);
            exit;
        }

        if($_SERVER['REQUEST_METHOD'] == "POST"){
            $org_type_name = $_POST["org_type_name"];
            // Bước 1: cập nhật org_type
            $updateQuery = "UPDATE org_types SET name='$org_type_name' WHERE id=$orgTypeId";
            if (!query($conn, $updateQuery)) {
                add_notification("error", "Lỗi khi cập nhật loại tổ chức", 4000);
                echo "Query failed: " . mysqli_error($conn);
                exit;
            }
            // Bước 2: cập nhật resource tương ứng
            $resource_id = getResourceId($resourceTypeId, $orgTypeId);
            updateResource($resource_id, $org_type_name, "$org_type_name Description", 1, $resourceTypeId, $orgTypeId);
            // Bước 3: update version
            updateResourceTypeVersion($resourceTypeId);
            // Bước 4: gắn quan hệ người tạo
            // TODO
            add_notification("success", "Cập nhật loại tổ chức thành công", 4000);
            header("Location: $redirectLink");
            exit;
        }
        return [$label, $orgType];
    }

    if ($mode == 'create') {
        [$label, $orgType] = createHandler($redirectLink, $resourceTypeId);
    } else if ($mode === 'edit') {
        [$label, $orgType] = editHandler($redirectLink, $resourceTypeId);
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $label ?></title>
    <link rel="stylesheet" href="../../assets/css/form.css"><title>Document</title>
</head>
<body>
    <form action="" method="POST" class="form">
        <h1 class="page-title"><?php echo $label ?></h1>

        <div class="form-group">
            <label for="org-type-name">Tên loại tổ chức</label>
            <input type="text" name="org_type_name" id="org-type-name" class="input-text"
                <?php if (isset($orgType)) echo "value='" . $orgType["name"] . "'"; ?>>
        </div>

        <div class="form-buttons">
            <button type="button" onclick="location.href='<?php echo $redirectLink ?>'" class="button-cancel">Hủy</button>
            <input type="submit" value="<?= $mode === 'edit' ? 'Cập nhật' : 'Thêm' ?>" class="button-submit">
        </div>
    </form>
</body>
</html>