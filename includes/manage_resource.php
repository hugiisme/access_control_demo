<?php 
    function createResource($name, $description, $org_id, $resource_type_id, $entity_id){
        global $conn;

        // Nếu org_id null hoặc rỗng thì để NULL trong SQL
        $org_id_value = ($org_id === null || $org_id === '') ? "NULL" : (int)$org_id;

        $insertQuery = "INSERT INTO resources (name, description, org_id, resource_type_id, entity_id)
                        VALUES ('" . mysqli_real_escape_string($conn, $name) . "',
                                '" . mysqli_real_escape_string($conn, $description) . "',
                                $org_id_value,
                                $resource_type_id,
                                $entity_id)";

        if (!query($conn, $insertQuery)) {
            add_notification("error", "Lỗi khi tạo resource", 4000);
            echo "Query failed: " . mysqli_error($conn) . "<br>SQL: " . $insertQuery;
            exit;
        }
    }

    function updateResource($id, $name, $description, $org_id, $resource_type_id, $entity_id){
        global $conn;

        $org_id_value = ($org_id === null || $org_id === '') ? "NULL" : (int)$org_id;

        $updateQuery = "UPDATE resources 
                        SET name='" . mysqli_real_escape_string($conn, $name) . "',
                            description='" . mysqli_real_escape_string($conn, $description) . "',
                            org_id=$org_id_value,
                            resource_type_id=$resource_type_id,
                            entity_id=$entity_id
                        WHERE id=$id";

        if (!query($conn, $updateQuery)) {
            add_notification("error", "Lỗi khi cập nhật resource", 4000);
            echo "Query failed: " . mysqli_error($conn) . "<br>SQL: " . $updateQuery;
            exit;
        }
    }

    function getResourceId($resource_type_id, $entity_id) {
        global $conn;

        $query = "SELECT id FROM resources WHERE resource_type_id = $resource_type_id AND entity_id = $entity_id";
        $result = query($conn, $query);

        if(!$result) {
            add_notification("error", "Lỗi khi lấy resource ID", 4000);
            echo "Query failed: " . mysqli_error($conn) . "<br>SQL: " . $query;
            exit;
        }

        if(mysqli_num_rows($result) === 0) {
            return null;
        }

        $row = mysqli_fetch_assoc($result);
        return $row['id'];
    }

    function updateResourceTypeVersion($resource_type_id){
        global $conn;

        $updateVersionQuery = "UPDATE resource_types SET version = version + 1 WHERE id = $resource_type_id";

        if (!query($conn, $updateVersionQuery)) {
            add_notification("error", "Lỗi khi cập nhật version loại tài nguyên", 4000);
            echo "Query failed: " . mysqli_error($conn) . "<br>SQL: " . $updateVersionQuery;
            exit;
        }
    }

    function assignResourceRole($resource_id, $role_id) {
        global $conn;

        // Check nếu đã có quyền to hơn rồi thì không thêm nữa
        $checkQuery = "SELECT * FROM resource_roles WHERE resource_id = $resource_id AND role_id = $role_id";

        $insertQuery = "INSERT INTO resource_roles (resource_id, role_id) VALUES ($resource_id, $role_id)";

        if (!query($conn, $insertQuery)) {
            add_notification("error", "Lỗi khi gán vai trò cho resource", 4000);
            echo "Query failed: " . mysqli_error($conn) . "<br>SQL: " . $insertQuery;
            exit;
        }
    }
?>
