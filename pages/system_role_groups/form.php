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

    $table_name = "system_role_groups";
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
            redirect_with_message("error", "Nhóm vai trò không tồn tại hoặc bạn không có quyền truy cập.");
            exit;
        }
    } else {
        $resource_id = null;
    }

    $canEdit = hasPermission($userId, 'edit', $resource_id, $resource_type_id);
    $canCreate = hasPermission($userId, 'create', $resource_id, $resource_type_id);
    if ($mode == "edit" && !$canEdit) {
       redirect_with_message("error", "Bạn không có quyền chỉnh sửa nhóm vai trò này.");
        exit;
    }
    if ($mode == "create" && !$canCreate) {
        redirect_with_message("error", "Bạn không có quyền tạo nhóm vai trò mới.");
        exit;
    }
    $formTitle = ($mode == "edit") ? "Chỉnh sửa nhóm vai trò" : "Tạo nhóm vai trò mới";
    $formAction = ($mode == "edit") ? "update" : "create";
    
    $form = new FormBuilder($conn, $formTitle, $table_name, $row_id);

    // TODO: fix
    // Với edit, không được chọn chính nó
    $form->addField('text', 'name', 'Tên nhóm vai trò');
    $form->addField(
        'text',
        'description',
        'Mô tả'
    );
    $form->addField(
        'select',
        'parent_group_id',
        'Nhóm vai trò cha',
        resultToOptions(getParentRoleGroupList(),  'id', 'name', true)
    );
    $form->addField(
        "hidden", 
        "org_id",
        ""
    );

    if(isset($_POST['submit'])) {
        $_POST["org_id"] = isset($_GET["org_id"]) ? intval($_GET["org_id"]) : null;
        $form->handleSubmit();
    }
    
    $form->render();
?>