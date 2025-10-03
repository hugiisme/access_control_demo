<?php 
    function table_renderer($result, $table_name, $button_list = [], $reload_link = '') {
        if (!$result || mysqli_num_rows($result) === 0) {
            return;
        }

        echo "<table class='data-table'>";

        // Header
        echo "<thead><tr>";
        $fieldNames = mysqli_fetch_fields($result);
        foreach ($fieldNames as $fieldName) {
            echo "<th>" . htmlspecialchars($fieldName->name) . "</th>";
        }
        foreach ($button_list as $button) {
            if ($button['placement'] !== "table") continue;
            echo "<th class='btn-col'>" . htmlspecialchars($button['btn_type']) . "</th>";
        }
        echo "</tr></thead>";

        // Rows
        while ($row = mysqli_fetch_assoc($result)) {
            echo "<tr>";
            foreach ($fieldNames as $field) {
                echo "<td>" . htmlentities((string)$row[$field->name]) . "</td>";
            }

            foreach ($button_list as $button) {
                if ($button['placement'] !== "table") continue;

                $btnName   = htmlspecialchars($button['label']);
                $btnUrl    = $button['btn_url'];
                $confirm   = $button['confirm'] ?? null;
                $checkAction = $button['check_action'] ?? null;

                $id = urlencode($row['id']);
                $separator = (strpos($btnUrl, '?') !== false) ? '&' : '?';
                $link = $btnUrl . $separator . "row_id={$id}&table={$table_name}&redirect_link=" . urlencode($reload_link);

                if ($confirm) {
                    // escape JS string để không bị lỗi khi có ký tự đặc biệt
                    $confirmMsg = htmlspecialchars($confirm, ENT_QUOTES);
                    $onclick = "onclick='return confirm(\"{$confirmMsg}\")'";
                } else {
                    $onclick = "";
                }
                $btnClass = $button['btn_class'] ?? '';
                $btnId = $button['btn_id'] ?? '';

                // Kiểm tra quyền nếu có
                $enabled = true;
                if ($checkAction) {
                    $resourceType = getResourceTypeByName($table_name);
                    if ($resourceType) {
                        $resourceTypeId = mysqli_fetch_assoc($resourceType)["id"];
                        $entityId= $row['id'];
                        $resourceId = mysqli_fetch_assoc(getResourceByTypeAndID($resourceTypeId, $entityId))["id"] ?? null;
                        $userId = $_SESSION["user"]["id"];
                        $enabled = hasPermission($userId, $checkAction, $resourceId, $resourceTypeId);
                    }
                }

                echo "<td class='btn-col'>";
                if ($enabled) {
                    echo "<a href='" . htmlspecialchars($link, ENT_QUOTES) . "' {$onclick}>
                                <button class='action-btn {$btnClass}' id='{$btnId}'>{$btnName}</button>
                            </a>";
                } else {
                    echo "<button class='action-btn {$btnClass}' disabled>{$btnName}</button>";
                }
                echo "</td>";
            }
            echo "</tr>";      
        }

        echo "</table>";
        mysqli_free_result($result);
    }


    function render_hidden_input($key, $value) {
        if (is_array($value)) {
            foreach ($value as $subKey => $subVal) {
                render_hidden_input("{$key}[{$subKey}]", $subVal);
            }
        } else {
            echo "<input type='hidden' name='" . htmlspecialchars($key) . "' value='" . htmlspecialchars((string)$value) . "'>";
        }
    }

    function render_pagination($currentPage, $totalPages, $baseUrl) {
        // Lấy các param khác ngoài page
        parse_str(parse_url($baseUrl, PHP_URL_QUERY) ?? '', $params);

        echo "<form id='pagination-form' class='pagination-form' method='GET'>";

        // render hidden inputs để giữ lại query string
        foreach ($params as $key => $value) {
            if ($key !== 'page') {
                render_hidden_input($key, $value);
            }
        }

        // Nút về trang đầu
        if ($currentPage > 1) {
            echo "<button type='submit' name='page' value='1'>&laquo; Đầu</button>";
        } else {
            echo "<button type='button' disabled>&laquo; Đầu</button>";
        }

        // Nút trang trước
        if ($currentPage > 1) {
            $prev = $currentPage - 1;
            echo "<button type='submit' name='page' value='{$prev}'>&lsaquo; Trước</button>";
        } else {
            echo "<button type='button' disabled>&lsaquo; Trước</button>";
        }

        // Select trang
        echo "<select id='page' name='page' class='pagination-select' onchange='this.form.submit()'>";
        for ($i = 1; $i <= $totalPages; $i++) {
            $selected = $i == $currentPage ? "selected" : "";
            echo "<option value='{$i}' $selected>Trang {$i}</option>";
        }
        echo "</select>";

        // Nút trang tiếp
        if ($currentPage < $totalPages) {
            $next = $currentPage + 1;
            echo "<button type='submit' name='page' value='{$next}'>Tiếp &rsaquo;</button>";
        } else {
            echo "<button type='button' disabled>Tiếp &rsaquo;</button>";
        }

        // Nút tới trang cuối
        if ($currentPage < $totalPages) {
            echo "<button type='submit' name='page' value='{$totalPages}'>Cuối &raquo;</button>";
        } else {
            echo "<button type='button' disabled>Cuối &raquo;</button>";
        }

        echo "</form>";
    }

    function total_results($conn, $query){
        if (strpos($query, 'IN ()') !== false) {
            return 0;
        }
        
        $result = query($query);
        if (!$result) {
            return 0; 
        }
        return mysqli_num_rows($result);
    }

    function build_query($query, $rowsPerPage, $currentPage){
        $offset = ($currentPage - 1) * $rowsPerPage;
        $query .= " LIMIT $rowsPerPage OFFSET $offset";
        return $query;
    }

?>