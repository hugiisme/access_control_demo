document.addEventListener("DOMContentLoaded", () => {
    const toggleSidebarButton = document.getElementById("sidebar-toggle");
    const sidebar = document.getElementById("sidebar");
    const content = document.getElementById("main-content");

    // Toggle toàn bộ sidebar
    toggleSidebarButton.addEventListener("click", () => {
        sidebar.classList.toggle("active");
        content.classList.toggle("active");
        toggleSidebarButton.classList.toggle("active");
    });

    document.querySelectorAll(".sidebar-tree li > a").forEach((link) => {
        link.addEventListener("click", (e) => {
            const li = link.closest("li");
            const subTree = li.querySelector(":scope > ul");

            // reset active
            document
                .querySelectorAll(".sidebar-tree li.active")
                .forEach((node) => node.classList.remove("active"));
            li.classList.add("active");

            const orgId = new URL(
                link.href,
                window.location.href
            ).searchParams.get("org_id");
            const url = new URL(window.location.href);

            // luôn set org_id theo node click
            url.searchParams.set("org_id", orgId);

            if (subTree) {
                e.preventDefault();

                const currentExpand = url.searchParams.get("expand");
                if (currentExpand === orgId) {
                    // đang mở → đóng lại
                    url.searchParams.delete("expand");
                } else {
                    // đang đóng → mở ra
                    url.searchParams.set("expand", orgId);
                }
            }

            // reload với URL mới
            window.location.href = url.href;
        });
    });
});
