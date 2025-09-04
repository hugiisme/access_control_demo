<?php
    // Helper: in input hidden cho cả string và array
    function renderHiddenInputs($key, $value) {
        if (is_array($value)) {
            foreach ($value as $subKey => $subVal) {
                renderHiddenInputs("{$key}[{$subKey}]", $subVal);
            }
        } else {
            echo "<input type='hidden' name='" . htmlspecialchars($key) . "' value='" . htmlspecialchars((string)$value) . "'>";
        }
    }

    // Helper: lấy tên cột gốc (bỏ alias)
    function getBaseColumn($colWithAlias) {
        if (strpos($colWithAlias, '.') !== false) {
            return substr($colWithAlias, strpos($colWithAlias, '.') + 1);
        }
        return $colWithAlias;
    }

    // Render filter form với columnMapping
    function renderFilterForm($conn, $tableName, $fieldNames, $columnMapping = []) {
        echo "<div id='filter-container' style='display:none;'>";
        echo "<form method='GET' class='filter-form'>";

        // Giữ nguyên GET param khác ngoài filter/sort/order/page
        foreach ($_GET as $key => $value) {
            if (!in_array($key, ['filter', 'sort', 'order', 'page'])) {
                renderHiddenInputs($key, $value);
            }
        }

        // Bắt buộc giữ table
        echo "<input type='hidden' name='table' value='" . htmlspecialchars($tableName) . "'>";

        foreach ($fieldNames as $field) {
            $aliasName = $field->name;                                // alias hiển thị
            $dbCol     = $columnMapping[$aliasName] ?? $aliasName;    // cột thật
            $type      = $field->type;
            $currentValue = $_GET['filter'][$aliasName] ?? '';

            // Input text cho string
            if (in_array($type, [
                MYSQLI_TYPE_VAR_STRING, MYSQLI_TYPE_STRING, MYSQLI_TYPE_BLOB,
                MYSQLI_TYPE_TINY_BLOB, MYSQLI_TYPE_MEDIUM_BLOB, MYSQLI_TYPE_LONG_BLOB
            ])) {
                echo "<label>$aliasName: <input type='text' name='filter[$aliasName]' value='" . htmlspecialchars((string)$currentValue) . "'></label> ";
            } else {
                // DISTINCT phải dùng tên cột gốc (không có alias)
                $baseCol = getBaseColumn($dbCol);
                $distinctQuery = "SELECT DISTINCT `$baseCol` AS val FROM `$tableName` ORDER BY val ASC";
                $distinctResult = query($conn, $distinctQuery);

                if ($distinctResult && mysqli_num_rows($distinctResult) > 0) {
                    echo "<label>$aliasName: <select name='filter[$aliasName]'><option value=''>--All--</option>";
                    while ($row = mysqli_fetch_assoc($distinctResult)) {
                        $val = $row['val'];
                        $selected = ($currentValue !== '' && $currentValue == $val) ? "selected" : "";
                        echo "<option value='" . htmlspecialchars((string)$val) . "' $selected>" . htmlspecialchars((string)$val) . "</option>";
                    }
                    echo "</select></label> ";
                }
            }
        }

        // Sort
        $currentSort  = $_GET['sort'] ?? '';
        $currentOrder = $_GET['order'] ?? 'ASC';
        echo "<label>Sort by: <select name='sort'>";
        foreach ($fieldNames as $field) {
            $selected = ($currentSort == $field->name) ? "selected" : "";
            echo "<option value='{$field->name}' $selected>{$field->name}</option>";
        }
        echo "</select></label>";

        echo "<label>Order: <select name='order'>";
        echo "<option value='ASC'" . ($currentOrder == 'ASC' ? ' selected' : '') . ">ASC</option>";
        echo "<option value='DESC'" . ($currentOrder == 'DESC' ? ' selected' : '') . ">DESC</option>";
        echo "</select></label>";

        echo "<button type='submit'>Apply</button>";
        echo "</form>";
        echo "</div>";
    }

    // Apply filter + sort (dùng columnMapping)
    function applyFiltersAndSort($query, $filters = [], $sort = '', $order = 'ASC', $columnMapping = []) {
        $query = trim($query);

        if (stripos($query, 'WHERE') === false) {
            $query .= " WHERE 1=1";
        }

        $where = [];
        foreach ($filters as $aliasCol => $val) {
            if ($val !== '') {
                $dbCol = $columnMapping[$aliasCol] ?? $aliasCol;
                $val = addslashes($val);
                $where[] = "$dbCol LIKE '%$val%'";  // Giữ alias đầy đủ ở đây
            }
        }

        if (!empty($where)) {
            $query .= " AND " . implode(" AND ", $where);
        }

        if ($sort) {
            $dbSort = $columnMapping[$sort] ?? $sort;
            $query .= " ORDER BY $dbSort $order"; // Giữ alias đầy đủ
        }

        return $query;
    }

    // Render table
    function TableRenderer($result, $tableName, $buttonList = [], $reloadLink = '') {
        if (!$result || mysqli_num_rows($result) === 0) {
            return;
        }

        echo "<table class='data-table'>";

        // Header
        echo "<thead><tr>";
        $fieldNames = mysqli_fetch_fields($result);
        foreach ($fieldNames as $fieldName) {
            echo "<th>" . $fieldName->name . "</th>";
        }
        foreach ($buttonList as $button) {
            if ($button['btn_type'] === 'Create' || $button['btn_type']  === 'Assign') continue;
            echo "<th class='btn-col'>" . htmlspecialchars($button['btn_type']) . "</th>";
        }
        echo "</tr></thead>";

        // Rows
        while ($row = mysqli_fetch_assoc($result)) {
            echo "<tr>";
            foreach ($fieldNames as $field) {
                echo "<td>" . htmlentities((string)$row[$field->name]) . "</td>";
            }

            foreach ($buttonList as $button) {
                if ($button['btn_type'] === 'Create' || $button['btn_type']  === 'Assign') continue;

                $btnName   = htmlspecialchars($button['label']);
                $btnUrl    = $button['btn_url'];
                $confirm   = $button['confirm'] ?? false;
                $checkAction = $button['check_action'] ?? null;

                $id = urlencode($row['id']);
                $separator = strpos($btnUrl, '?') !== false ? '&' : '?';
                $link = "$btnUrl{$separator}id=$id&table=$tableName&redirectLink=" . urlencode($reloadLink);
                $onclick = $confirm ? "onclick='return confirm(\"Bạn có chắc chắn muốn xóa?\")'" : "";
                $btnClass = strtolower($button['btn_type']) . "-btn";

                $enabled = true;
                if ($checkAction) {
                    $resourceType = getResourceTypeByName($tableName);
                    if ($resourceType) {
                        $enabled = hasPermission($_SESSION['user']['id'], $checkAction, $row['id'], $tableName);
                    }
                }

                echo "<td class='btn-col'>";
                if ($enabled) {
                    echo "<a href='$link' $onclick><button class='action-btn $btnClass'>$btnName</button></a>";
                } else {
                    echo "<button class='action-btn $btnClass' disabled>$btnName</button>";
                }
                echo "</td>";
            }
            echo "</tr>";
        }

        echo "</table>";
        mysqli_free_result($result);
    }

    // Pagination
    function renderPagination($currentPage, $totalPages, $baseUrl) {
        // parse_str(parse_url($baseUrl, PHP_URL_QUERY), $params);
        parse_str(parse_url($baseUrl, PHP_URL_QUERY) ?? '', $params);


        echo "<form id='pagination-form' class='pagination-form' method='GET'>";
        foreach ($params as $key => $value) {
            if ($key !== 'page') {
                renderHiddenInputs($key, $value);
            }
        }

        echo "<select id='page' name='page' class='pagination-select' onchange='this.form.submit()'>";
        for ($i = 1; $i <= $totalPages; $i++) {
            $selected = $i == $currentPage ? "selected" : "";
            echo "<option value='{$i}' $selected>Page {$i}</option>";
        }
        echo "</select>";
        echo "</form>";
    }

    function totalResults($conn, $query){
        if (strpos($query, 'IN ()') !== false) {
            return 0;
        }
        
        $result = query($conn, $query);
        if (!$result) {
            return 0; 
        }
        return mysqli_num_rows($result);
    }

    function buildQuery($query, $rowsPerPage, $currentPage){
        $offset = ($currentPage - 1) * $rowsPerPage;
        $query .= " LIMIT $rowsPerPage OFFSET $offset";
        return $query;
    }
?>
