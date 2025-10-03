<?php 
    require_once __DIR__ . "/config/config.php";
    require_once __DIR__ . "/config/database.php";
    include_once 'includes/navigations/nav_config.php';
    include_once 'includes/navigations/nav_helper.php';

    $currentPage = $_GET['view'] ?? 'home';
    $pageTitle = getTitle($currentPage, $views) ?? "Không tìm thấy";

    if (session_status() === PHP_SESSION_NONE) {
        session_start();
        
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/sidebar.css">
    <link rel="stylesheet" href="assets/css/navigations.css">
    <link rel="stylesheet" href="assets/css/notifications.css">
    <link rel="stylesheet" href="assets/css/home.css">
    <link rel="stylesheet" href="assets/css/table.css">
    <link rel="stylesheet" href="assets/css/404.css">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>

    <title><?php echo htmlspecialchars($pageTitle) ?></title>
</head>
<body>
    <?php 
        require_once __DIR__ . "/includes/notifications/notifications.php";
        if (!isset($_SESSION['user'])) {
            $msg = "Bạn chưa đăng nhập";
            $msgType = "warning";
            redirect_with_message($msgType, $msg);
            include_once("pages/login/controller.php");
            include_once("pages/login/content.php");
            exit();
        }
    ?>
    <?php 
        include_once 'includes/navigations/navigations.php'; 
        include_once 'includes/sidebar/sidebar.php';
        // TODO: fix 
        // Bỏ sidebar ở trang home
    ?>
    
    
    <main id="main-content" class="main-content active">
        <?php 
            require_once __DIR__ . "/router.php"; 
            // if (!empty($_SESSION["logText"])) {
            //     echo "<pre>" . htmlspecialchars($_SESSION["logText"]) . "</pre>";
            //     unset($_SESSION["logText"]); 
            // }
        ?>
    </main>

    <script src="assets/js/sidebar.js"></script>
    
</body>
</html>
