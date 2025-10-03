<?php 
    echo "<div class='top-bar'>";
        echo "<div class='total-results'><strong>Tổng số kết quả tìm được:</strong> $total_results</div>";
        echo "<div class='btn-container'>";
            foreach ($button_list as $button){
                if($button["placement"] != "table"){
                    $url = $button["btn_url"];
                    $label = $button["label"];
                    $btn_class = $button["btn_class"];
                    echo "<a href='$url' class='btn-wrapper'>
                                <button class='action-btn $btn_class'>$label</button>
                            </a>";
                }
            }
        echo "</div>";
    echo "</div>";
    if ($result && mysqli_num_rows($result) > 0) {
        table_renderer($result, $table_name, $button_list, $reload_link);
        render_pagination($current_page, $total_pages, "$reload_link");
    } else {
        echo "<h2 class='no-result-message'>Không tìm thấy kết quả nào</h2>";
    }
?>