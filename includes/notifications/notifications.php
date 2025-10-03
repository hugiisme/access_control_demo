<?php
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $notifications = $_SESSION['notifications'] ?? [];
    $_SESSION['notifications'] = [];
?>

<div class="notification-container" id="notificationContainer">
    <?php foreach ($notifications as $i => $n): ?>
        <div class="notification <?= htmlspecialchars($n['type']) ?>"
             data-timeout="<?= (int)$n['timeout'] ?>">
            <span class="message"><?= htmlspecialchars($n['message']) ?></span>
            <button class="close-btn" onclick="this.parentElement.remove()">×</button>
        </div>
    <?php endforeach; ?>
</div>

<script>
    document.querySelectorAll('.notification').forEach(note => {
        const timeout = parseInt(note.dataset.timeout) || 3000;
        setTimeout(() => {
            note.classList.add('hide');
            setTimeout(() => note.remove(), 300); // wait for animation
        }, timeout);
    });
</script>