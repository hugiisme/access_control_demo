<link rel="stylesheet" href="../../assets/css/home.css">
<h1>Trang chủ</h1>
<form action="" method="POST" id="permission-form">
    <div class="form-group">
        <label for="action_name">Chọn hành động:</label>
        <select name="action_name" id="action_name" class="input-select">
            <option value="">--Chọn hành động--</option>
            <?php if ($actionResult && mysqli_num_rows($actionResult) > 0): ?>
                <?php while ($row = mysqli_fetch_assoc($actionResult)): ?>
                    <option value="<?= $row['name'] ?>">
                        <?= htmlentities($row['name']) ?>
                    </option>
                <?php endwhile; ?>
            <?php endif; ?>
        </select>
    </div>

    <!-- Lựa chọn loại kiểm tra -->
    <div class="form-group">
        <label>Chọn kiểu kiểm tra:</label><br>
        <label><input type="radio" name="check_mode" value="resource_id" checked> Theo Resource ID</label>
        <label><input type="radio" name="check_mode" value="resource_type"> Theo Resource Type</label>
        <label><input type="radio" name="check_mode" value="resource_type_entity"> Theo Resource Type + Entity</label>
    </div>

    <!-- Resource ID -->
    <div class="form-group mode-resource_id">
        <label for="resource_id">Resource ID:</label>
        <input type="number" name="resource_id" id="resource_id" class="input-number">
    </div>

    <!-- Resource Type -->
    <div class="form-group mode-resource_type mode-resource_type_entity" style="display:none;">
        <label for="resource_type_id">Chọn Resource Type:</label>
        <select name="resource_type_id" id="resource_type_id" class="input-select">
            <option value="">--Chọn Resource Type--</option>
            <?php 
                $rtQuery = "SELECT id, name FROM resource_types";
                $rtResult = query($conn, $rtQuery);
                if ($rtResult && mysqli_num_rows($rtResult) > 0):
                    while ($rt = mysqli_fetch_assoc($rtResult)): ?>
                        <option value="<?= $rt['id'] ?>">
                            <?= htmlentities($rt['name']) ?>
                        </option>
                    <?php endwhile;
                endif;
            ?>
        </select>
    </div>

    <!-- Entity ID -->
    <div class="form-group mode-resource_type_entity" style="display:none;">
        <label for="entity_id">Entity ID:</label>
        <input type="number" name="entity_id" id="entity_id" class="input-number">
    </div>

    <div class="form-buttons">
        <input type="submit" id="submit-btn" value="Check" class="button-submit">
    </div>
</form>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const radioButtons = document.querySelectorAll('input[name="check_mode"]');
    const allGroups = {
        resource_id: document.querySelectorAll('.mode-resource_id'),
        resource_type: document.querySelectorAll('.mode-resource_type'),
        resource_type_entity: document.querySelectorAll('.mode-resource_type_entity')
    };

    function updateFormVisibility() {
        const selectedMode = document.querySelector('input[name="check_mode"]:checked').value;

        // Ẩn tất cả
        for (let group in allGroups) {
            allGroups[group].forEach(el => el.style.display = 'none');
        }

        // Hiện các nhóm input liên quan
        if (selectedMode === 'resource_id') {
            allGroups.resource_id.forEach(el => el.style.display = 'block');
        } else if (selectedMode === 'resource_type') {
            allGroups.resource_type.forEach(el => el.style.display = 'block');
        } else if (selectedMode === 'resource_type_entity') {
            allGroups.resource_type_entity.forEach(el => el.style.display = 'block');
            allGroups.resource_type.forEach(el => el.style.display = 'block');
        }
    }

    radioButtons.forEach(radio => {
        radio.addEventListener('change', updateFormVisibility);
    });

    updateFormVisibility(); // chạy khi load trang
});
</script>
