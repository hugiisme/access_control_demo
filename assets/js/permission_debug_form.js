document.addEventListener("DOMContentLoaded", function () {
    const radioButtons = document.querySelectorAll('input[name="check_mode"]');
    const allGroups = {
        resource_id: document.querySelectorAll(".mode-resource_id"),
        resource_type: document.querySelectorAll(".mode-resource_type"),
        resource_type_entity: document.querySelectorAll(
            ".mode-resource_type_entity"
        ),
    };

    function updateFormVisibility() {
        const selectedMode = document.querySelector(
            'input[name="check_mode"]:checked'
        ).value;

        // Ẩn tất cả
        for (let group in allGroups) {
            allGroups[group].forEach((el) => (el.style.display = "none"));
        }

        // Hiện các nhóm input liên quan
        if (selectedMode === "resource_id") {
            allGroups.resource_id.forEach((el) => (el.style.display = "block"));
        } else if (selectedMode === "resource_type") {
            allGroups.resource_type.forEach(
                (el) => (el.style.display = "block")
            );
        } else if (selectedMode === "resource_type_entity") {
            allGroups.resource_type_entity.forEach(
                (el) => (el.style.display = "block")
            );
            allGroups.resource_type.forEach(
                (el) => (el.style.display = "block")
            );
        }
    }

    radioButtons.forEach((radio) => {
        radio.addEventListener("change", updateFormVisibility);
    });

    updateFormVisibility(); // chạy khi load trang
});
