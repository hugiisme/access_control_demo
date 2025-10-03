document.addEventListener("DOMContentLoaded", () => {
    // Validate required fields
    const forms = document.querySelectorAll("form.form");
    forms.forEach((form) => {
        form.addEventListener("submit", (e) => {
            const requiredFields = form.querySelectorAll("[required]");
            let valid = true;

            requiredFields.forEach((field) => {
                if (!field.value.trim()) {
                    field.classList.add("input-error");
                    valid = false;
                } else {
                    field.classList.remove("input-error");
                }
            });

            if (!valid) {
                e.preventDefault();
                alert("Vui lòng điền đầy đủ các trường bắt buộc.");
            }
        });
    });

    // Handle matrix checkboxes (AJAX update)
    document.querySelectorAll(".matrix-checkbox").forEach((cb) => {
        cb.addEventListener("change", function () {
            const idValue = this.dataset.id;
            const checked = this.checked ? 1 : 0;
            const updateUrl = this.dataset.updateUrl;

            fetch(updateUrl, {
                method: "POST",
                headers: {
                    "Content-Type": "application/x-www-form-urlencoded",
                },
                body:
                    "id=" +
                    encodeURIComponent(idValue) +
                    "&checked=" +
                    encodeURIComponent(checked),
            })
                .then((r) => {
                    if (!r.ok) {
                        console.error(
                            "Matrix update failed:",
                            r.status,
                            r.statusText
                        );
                    }
                    return r.json().catch(() => null);
                })
                .then((data) => {
                    if (data) {
                        console.log("Matrix update success:", data);
                    }
                })
                .catch((err) => {
                    console.error("Matrix update error:", err);
                });
        });
    });
});
