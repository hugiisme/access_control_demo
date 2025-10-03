<?php 
    require_once __DIR__ . '/sidebar_controller.php';
?>
<div class="sidebar-wrapper">
    <div id="sidebar" class="sidebar active">
        <h1 class="sidebar-title">Danh sách tổ chức</h1>
        <div class="sidebar-content">
            <?php 
                renderTree($org_tree, $active_org_id);
            ?>
        </div>
    </div>
    <button id="sidebar-toggle" class="sidebar-toggle active">☰</button>
</div>
