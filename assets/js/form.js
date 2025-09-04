const orgSelect = document.getElementById("org-ids");
const tagContainer = document.getElementById("selected-tags");
const hiddenInput = document.getElementById("org-ids-serialized");

let selectedOrgs = [];

function renderTags() {
    tagContainer.innerHTML = "";

    selectedOrgs.forEach((id) => {
        const option = orgSelect.querySelector(`option[value="${id}"]`);
        if (!option) return;

        const tag = document.createElement("span");
        tag.className = "tag";
        tag.textContent = option.textContent;

        const removeBtn = document.createElement("span");
        removeBtn.className = "remove-tag";
        removeBtn.textContent = " ✕";
        removeBtn.onclick = () => {
            selectedOrgs = selectedOrgs.filter((orgId) => orgId !== id);
            renderTags();
            updateHiddenInput();
        };

        tag.appendChild(removeBtn);
        tagContainer.appendChild(tag);
    });
}

function updateHiddenInput() {
    hiddenInput.value = selectedOrgs.join(",");
}

function handleSelect() {
    const selectedId = orgSelect.value;
    if (!selectedId || selectedOrgs.includes(selectedId)) return;

    selectedOrgs.push(selectedId);
    renderTags();
    updateHiddenInput();
    orgSelect.selectedIndex = 0; // Reset dropdown to placeholder
}

orgSelect.addEventListener("change", handleSelect);

// Optional: preload selected orgs (e.g. in edit mode)
if (typeof preloadOrgIds !== "undefined" && preloadOrgIds.length > 0) {
    selectedOrgs = preloadOrgIds.map(String); // Ensure all are strings
    renderTags();
    updateHiddenInput();
}
