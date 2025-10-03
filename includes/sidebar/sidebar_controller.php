<?php 
    require_once "config/database.php";
    require_once "includes/query_helper.php";
    require_once "includes/access_control.php";
    require_once "includes/debug_log.php";
    include_once 'includes/sidebar/sidebar_helper.php'; 

    $user_id = $_SESSION['user']['id'];

    $action_name = "view";
    $action_id = mysqli_fetch_assoc(getActionByName($action_name))['id'];

    $resource_type = "organizations";
    $resource_type_id = mysqli_fetch_assoc(getResourceTypeByName($resource_type))['id'];

    $org_resource_list = getAccesibleResources($user_id, $action_id, $resource_type_id);
    $org_entity_id = array_column($org_resource_list, 'entity_id');
    debug_log("info", "Số lượng tổ chức user $user_id có quyền truy cập: " . count($org_entity_id));
    if(!empty($org_entity_id)){
        $org_id_list = implode(',', $org_entity_id);
    } else {
        $org_id_list = '0'; // không có quyền truy cập tổ chức nào
    }
    $org_list = getOrgByIdList($org_id_list);
    $org_tree = buildTree($org_list);

    $active_org_id = isset($_GET['org_id']) ? intval($_GET['org_id']) : null;
?>