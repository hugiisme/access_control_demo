<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

http_response_code(404);
?>
<div class="error404-container">
    <h1 class="error404-title">404</h1>
    <p class="error404-message">Xin lỗi, trang bạn tìm không tồn tại.</p>
    <a href="index.php?view=home" class="error404-button">Quay về Trang chủ</a>
</div>
