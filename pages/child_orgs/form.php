<?php
    include __DIR__ . "/../../config/database.php";
    include __DIR__ . "/../../includes/notify.php";
    include __DIR__ . "/../../includes/access_control.php";
    include __DIR__ . "/../../includes/manage_resource.php";
    include __DIR__ . "/../../includes/helper_functions.php";
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $organizationsResult = getOrgName();
    $orgTypesResult = getOrgType();
    $redirectLink = $_GET['redirectLink'] ?? '/index.php';
    $mode = isset($_GET['id']) ? 'edit' : 'create';
    $resourceTypeName = 'organizations';
    $resourceTypeId = getResourceTypeByName($resourceTypeName)['id'];

    function createHandler($redirectLink, $resourceTypeId){
        global $conn;

        if(!hasPermission($_SESSION['user']['id'], 'Create', null, 'organizations')) {
            redirectWithError("Bạn không có quyền truy cập vào trang này.", $redirectLink);
        }
        
        $label = "Tạo tổ chức mới";

        if ($_SERVER['REQUEST_METHOD'] == "POST") {
            $org_name = $_POST["org_name"];
            $org_level = $_POST["org_level"] ?? "NULL";
            // $parent_org_id = ($_POST["parent_org_id"] == "NULL") ? "NULL" : $_POST["parent_org_id"];
            // $org_type_id = ($_POST["org_type_id"] == "NULL") ? "NULL" : $_POST["org_type_id"];
            $parent_org_id = !empty($_POST["parent_org_id"]) && $_POST["parent_org_id"] !== "NULL" 
                ? intval($_POST["parent_org_id"]) 
                : "NULL";

            $org_type_id = !empty($_POST["org_type_id"]) && $_POST["org_type_id"] !== "NULL" 
                ? intval($_POST["org_type_id"]) 
                : "NULL";

            // Bước 1: insert
            $insertQuery = "INSERT INTO organizations (name, parent_org_id, org_level, org_type_id) 
                            VALUES ('$org_name', $parent_org_id, $org_level, $org_type_id)";
            if (!query($conn, $insertQuery)) {
                redirectWithError("Lỗi khi tạo tổ chức mới: " . mysqli_error($conn), $redirectLink);
            }
            // Bước 2: tạo resource 
            createResource($org_name, "Resource for $org_name", null, $resourceTypeId, mysqli_insert_id($conn));
            
            // Bước 3: update version
            updateResourceTypeVersion($resourceTypeId);

            // Bước 4: gắn quan hệ người tạo
            // TODO

            add_notification("success", "Tạo tổ chức mới thành công", 4000);
            header("Location: $redirectLink");
            exit;
        }
        return [$label, null];
    }

    function editHandler($redirectLink, $resourceTypeId) {
        global $conn;

        $orgId = $_GET['id'] ?? null;
        
        if ($orgId) {
            if(!hasPermission($_SESSION['user']['id'], 'Create', $orgId, 'organizations')) {
                redirectWithError("Bạn không có quyền truy cập vào trang này.", $redirectLink);
            }

            $query = "SELECT * FROM organizations WHERE id = $orgId";
            $result = query($conn, $query);
            if ($result && mysqli_num_rows($result) > 0) {
                $org = mysqli_fetch_assoc($result);
                $label = "Chỉnh sửa tổ chức ". $org['name'];
            } else {
                add_notification("error", "Organization not found", 4000);
                exit;
            }
        } else {
            add_notification("error", "Invalid organization ID", 4000);
            exit;
        }

        if($_SERVER['REQUEST_METHOD'] == "POST"){
            $org_name = $_POST["org_name"];
            // $parent_org_id = ($_POST["parent_org_id"] == "NULL") ? "NULL" : $_POST["parent_org_id"];
            $org_level = $_POST["org_level"] ?? "NULL";
            $org_type_id = ($_POST["org_type_id"] == "NULL") ? "NULL" : $_POST["org_type_id"];

            // Bước 1: update
            $updateQuery = "UPDATE organizations SET 
                                name = '$org_name',
                                org_level = $org_level,
                                org_type_id = $org_type_id
                            WHERE id = $orgId";

            if (! query($conn, $updateQuery)) {
                redirectWithError("Lỗi khi cập nhật tổ chức: " . mysqli_error($conn), $redirectLink);
            }

            // Bước 2: cập nhật resource tương ứng
            $resourceId = getResourceId($resourceTypeId, $orgId);
            updateResource($resourceId, $org_name, "Resource for $org_name", 1, $resourceTypeId, $orgId);

            // Bước 3: update version
            updateResourceTypeVersion($resourceTypeId);

            // Bước 4: gắn quan hệ người edit
            // TODO

            add_notification("success", "Update tổ chức $org_name thành công", 4000);
            header("Location: $redirectLink");
            exit;
        }

        return [$label, $org];
    }


    if ($mode === 'create') {
        [$label, $org] = createHandler($redirectLink, $resourceTypeId);
    } else if ($mode === 'edit') {
        [$label, $org] = editHandler($redirectLink, $resourceTypeId);
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
            <label for="org-name">Tên tổ chức</label>
            <input type="text" name="org_name" id="org-name" class="input-text"
                <?php if (isset($org)) echo "value='" . $org["name"] . "'"; ?>>
        </div>

        <input type="hidden" name="parent_org_id" value="<?php echo $_GET['org_id'] ?>">

        <div class="form-group">
            <label for="org-level">Cấp tổ chức</label>
            <input type="number" name="org_level" id="org-level" class="input-number"
                <?php if (isset($org)) echo "value='" . $org["org_level"] . "'"; ?>>
        </div>

        <div class="form-group">
            <label for="org-type">Loại tổ chức</label>
            <select name="org_type_id" id="org-type" class="input-select">
                <option value="NULL">NULL</option>
                <?php
                    if ($orgTypesResult && mysqli_num_rows($orgTypesResult) > 0) {
                        while ($row = mysqli_fetch_assoc($orgTypesResult)) {
                            $orgTypeSelected = (!empty($org) && $org['org_type_id'] == $row['id']) ? 'selected' : '';
                            echo "<option value='" . $row['id'] . "' $orgTypeSelected >" . 
                                htmlentities($row['name']) 
                            . "</option>";
                        }
                    } else {
                        echo "<option value=''>Không tìm thấy loại tổ chức nào</option>";
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