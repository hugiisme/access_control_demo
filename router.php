<main>
    <?php
        $pageName = $_GET['pageName'] ?? 'home'; // default to home

        $pagePath = "pages/$pageName";

        // Check if the page folder and files exist
        if (is_dir($pagePath) && file_exists("$pagePath/content.php")) {
            // Optional controller logic
            if (file_exists("$pagePath/controller.php")) {
                include_once "$pagePath/controller.php";
            }
            
            include_once "$pagePath/content.php";
        } else {
            include_once "pages/404.php";
        }
    ?>
</main>
