<?php
    require_once "config/database.php";
?>
<nav class="sidebar">
  <ul class="menu">
    
    <li><a href="?pageName=home" class="<?= $pageName === 'home' ? 'active' : '' ?>">Trang chủ</a></li>
    <li><a href="?pageName=org_types" class="<?= $pageName === 'org_types' ? 'active' : '' ?>">Loại tổ chức</a></li>
    <li><a href="?pageName=organizations" class="<?= $pageName === 'organizations' ? 'active' : '' ?>">Tổ chức</a></li>
    <?php if (defined('IS_DEBUG') && IS_DEBUG): ?>
        <li><a href="?pageName=all_organizations" class="<?= $pageName === 'all_organizations' ? 'active' : '' ?>">Tất cả tổ chức</a></li>
        <li><a href="?pageName=actions" class="<?= $pageName === 'actions' ? 'active' : '' ?>">Hành động</a></li>
        <li><a href="?pageName=action_relations" class="<?= $pageName === 'action_relations' ? 'active' : '' ?>">Quan hệ Hành động</a></li>
        <li><a href="?pageName=resource_types" class="<?= $pageName === 'resource_types' ? 'active' : '' ?>">Loại tài nguyên</a></li>
        <li><a href="?pageName=permissions" class="<?= $pageName === 'permissions' ? 'active' : '' ?>">Quyền</a></li>
    <?php endif; ?>
  </ul>

  <div class="logout-section">
    <a href="/includes/logout.php" class="logout-button">Đăng xuất</a>
  </div>
</nav>
