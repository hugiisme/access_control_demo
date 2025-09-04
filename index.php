<?php
    $pageName = $_GET['pageName'] ?? 'home';
    include_once 'includes/notify.php';
    // Thêm noti trước khi load trang
    // add_notification("info", "Welcome to the $pageName page!", 5);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= ucfirst($pageName) ?></title>
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="assets/css/notifications.css">
    <link rel="stylesheet" href="assets/css/navigations.css">
    <link rel="stylesheet" href="assets/css/details.css">
    <script src="assets/js/scripts.js"></script>
</head>
<body>

    <?php

        include_once 'includes/notifications.php';
        if(isset($_SESSION['user'])) {
            include_once 'includes/nav.php';
            include_once 'router.php';
        } else {
            include_once 'pages/login/controller.php';
            include_once 'pages/login/content.php';
        }
        
    ?>

</body>
</html>
