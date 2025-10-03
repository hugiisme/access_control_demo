<link rel="stylesheet" href="../../assets/css/form.css">
<?php 
    include_once __DIR__ . "/../../config/database.php";
    include_once __DIR__ . "/../../includes/access_control.php";
    include_once __DIR__ . "/../../includes/resource_manager.php";
    include_once __DIR__ . "/../../includes/query_helper.php";
    include_once __DIR__ . "/../../includes/form/FormBuilder.php";
    include_once __DIR__ . "/../../includes/form/form_helper.php";

    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $table_name = "organizations";
    $resource_type = getResourceTypeByName($table_name);
    $resource_type_id = (int)mysqli_fetch_assoc($resource_type)['id'];

    $userId = $_SESSION['user']['id'] ?? null;
    if (!$userId) {
        redirect_with_message("warning", "Vui lòng đăng nhập để xem dữ liệu.");
        exit;
    }
    $mode = isset($_GET['row_id']) && is_numeric($_GET['row_id']) ? "edit" : "create";
    $row_id = ($mode == "edit") ? intval($_GET['row_id']) : null;

    if($mode == "edit"){
        $resource = getResourceByTypeAndID($resource_type_id, $row_id);
        $resource_id = mysqli_fetch_assoc($resource)['id'] ?? null;
        if(!$resource_id){
            redirect_with_message("error", "Tổ chức không tồn tại hoặc bạn không có quyền truy cập.");
            exit;
        }
    } else {
        $resource_id = null;
    }

    $canEdit = hasPermission($userId, 'edit', $resource_id, $resource_type_id);
    $canCreate = hasPermission($userId, 'create', $resource_id, $resource_type_id);
    if ($mode == "edit" && !$canEdit) {
       redirect_with_message("error", "Bạn không có quyền chỉnh sửa tổ chức này.");
        exit;
    }
    if ($mode == "create" && !$canCreate) {
        redirect_with_message("error", "Bạn không có quyền tạo tổ chức mới.");
        exit;
    }
    $formTitle = ($mode == "edit") ? "Chỉnh sửa tổ chức" : "Tạo tổ chức mới";
    $formAction = ($mode == "edit") ? "update" : "create";
    
    $form = new FormBuilder($conn, $formTitle, $table_name, $row_id);

    // TODO: fix
    // Check xem có giới hạn loại tổ chức được chọn ko (VD: chỉ được chọn loại tổ chức nào)
    // Check xem nếu đã có org_level thì org_types có thừa vài cái ko và bị xung đột ko (có cái level liên chi nhưng vẫn chọn được loại đoàn trường)
    $form->addField('text', 'name', 'Tên tổ chức');
    $form->addField(
        'select',
        'parent_org_id',
        'Tổ chức cha',
        resultToOptions(getParentOrgList($userId), 'id', 'name', true)
    );
    $form->addField(
        'select',
        'org_type_id',
        'Loại tổ chức',
        resultToOptions(getOrgTypeList(), 'id', 'name')
    );
    $form->addField(
        "hidden", 
        "org_level",
        ""
    );

    if(isset($_POST['submit'])) {
        if(!empty($_POST["parent_org_id"])) {
            $parent_org = mysqli_fetch_assoc(getOrgById($_POST["parent_org_id"]));
            $_POST["org_level"] = $parent_org ? $parent_org["org_level"] + 1 : 1;
        }
        $form->handleSubmit();
    }

    $form->render();
?>