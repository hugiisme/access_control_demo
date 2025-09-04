<?php
    echo "<div class='top-bar'>";

        // Tổng số kết quả
        echo "<div class='total-results'><strong>Tổng số kết quả tìm được:</strong> $totalResults</div>";

        echo '<div class="btn-container">';
            echo '<button type="button" class="action-btn filter-toggle-btn" onclick="toggleFilter()">Bộ lọc</button>';

            // Assign button
            if (isset($assignButton)) {
                $url = $assignButton['btn_url'];
                $label = htmlspecialchars($assignButton['label']);
                echo "<a href='$url' class='assign-btn-wrapper'>
                        <button class='action-btn assign-btn'>$label</button>
                    </a>";
            }

            // Create button
            if (isset($createButton)) {
                $url = $createButton['btn_url'];
                $label = htmlspecialchars($createButton['label']);
                echo "<a href='$url' class='create-btn-wrapper'>
                        <button class='action-btn create-btn'>$label</button>
                    </a>";
            }
        echo '</div>';

    echo "</div>";

    // Chỉ render filter + bảng khi query gốc không phải SELECT 1 WHERE 1=0
    if (!preg_match('/SELECT\s+1\s+WHERE\s+1=0/i', $query)) {
        // Lấy tên field từ result ban đầu (query gốc)
        if ($result && mysqli_num_rows($result) > 0) {
            $fieldNames = mysqli_fetch_fields($result);
            renderFilterForm($conn, $tableName, $fieldNames, $columnMapping ?? []);

        }

        // Lấy filter/sort/order từ GET
        $filters = $_GET['filter'] ?? [];
        $sort    = $_GET['sort'] ?? '';
        $order   = $_GET['order'] ?? 'ASC';

        // Áp dụng filter + sort (có truyền mapping từ controller.php)
        $query = applyFiltersAndSort($query, $filters, $sort, $order, $columnMapping ?? []);

        // Phân trang
        error_log("row per page: $rowsPerPage, current page: $currentPage");
        $query = buildQuery($query, $rowsPerPage, $currentPage);
        error_log("Final query: $query");
        
        $result = query($conn, $query);

        if ($result && mysqli_num_rows($result) > 0) {
            TableRenderer($result, $tableName, $buttonList, $reloadLink);
            renderPagination($currentPage, $totalPages, "$reloadLink");
        } else {
            echo "<h2 class='no-result-message'>Không tìm thấy kết quả nào</h2>";
        }
    } else {
        echo "<h2 class='no-result-message'>Không tìm thấy kết quả nào</h2>";
    }
?>

<script>
function toggleFilter() {
    const filter = document.getElementById('filter-container');
    if (filter.style.display === 'none' || filter.style.display === '') {
        filter.style.display = 'block';
    } else {
        filter.style.display = 'none';
    }
}
</script>