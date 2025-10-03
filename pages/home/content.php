<?php 
    $user = $_SESSION["user"];
    $user_id = $user["id"] ?? null;
    $user_name = $user["name"] ?? "Người dùng";
?>
<h1>Trang chủ</h1>
<div class="user-box">
    <p><strong>Tài khoản:</strong> <?php echo htmlspecialchars($user_name); ?></p>
    <p><strong>ID:</strong> <?php echo htmlspecialchars($user_id); ?></p>
</div>

<?php if (defined("IS_DEBUG") && IS_DEBUG): ?>
    <form action="" method="POST" id="permission-form">
        <div class="form-group">
            <label for="action-name">Chọn hành động:</label>
            <select name="action_id" id="action-name" class="input-select">
                <?php 
                    $action_results = getActionList();
                    if(!$action_results || mysqli_num_rows($action_results) === 0) {
                        echo "<option value=''>Không có hành động nào</option>";
                    } else {
                        echo "<option value=''>--Chọn hành động--</option>";
                        while ($action = mysqli_fetch_assoc($action_results)) {
                            $action_id = $action['id'];
                            $action_name = $action['name'];
                            echo "<option value='" . htmlspecialchars($action_id) . "'>" . htmlspecialchars($action_name) . "</option>";
                        }
                    }
                ?>
            </select>
        </div>
        <div class="form-group">
            <label>Chọn kiểu kiểm tra:</label><br>
            <label><input type="radio" name="check_mode" value="resource_id" checked> Theo Resource ID</label>
            <label><input type="radio" name="check_mode" value="resource_type"> Theo Resource Type</label>
            <label><input type="radio" name="check_mode" value="resource_type_entity"> Theo Resource Type + Entity</label>
        </div>

        <div class="form-group mode-resource_id">
            <label for="resource_id">Resource ID:</label>
            <input type="number" name="resource_id" id="resource_id" class="input-number">
        </div>

        <div class="form-group mode-resource_type mode-resource_type_entity" style="display:none;">
            <label for="resource_type_id">Chọn Resource Type:</label>
            <select name="resource_type_id" id="resource_type_id" class="input-select">
                <?php 
                    $resource_type_results = getResourceTypeList();
                    if(!$resource_type_results || mysqli_num_rows($resource_type_results) === 0) {
                        echo "<option value=''>Không có Resource Type nào</option>";
                    } else {
                        echo "<option value=''>--Chọn Resource Type--</option>";
                        while ($resource_type = mysqli_fetch_assoc($resource_type_results)) {
                            $resource_type_id = $resource_type['id'];
                            $resource_type_name = $resource_type['name'];
                            echo "<option value='" . htmlspecialchars($resource_type_id) . "'>" . htmlspecialchars($resource_type_name) . "</option>";
                        }
                    }
                ?>
            </select>
        </div>

        <div class="form-group mode-resource_type_entity" style="display:none;">
            <label for="entity_id">Entity ID:</label>
            <input type="number" name="entity_id" id="entity_id" class="input-number">
        </div>

        <div class="form-buttons">
            <input type="submit" id="submit-btn" value="Check" class="home-button-submit">
        </div>

    </form>

    <script src="assets/js/permission_debug_form.js"></script>

<?php endif; ?>