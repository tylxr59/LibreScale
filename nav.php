<?php
/**
 * Bottom Navigation Component
 */
if (!defined('DB_PATH')) die('Direct access not permitted');
?>
<nav class="bottom-nav">
    <a href="index.php?page=home" class="nav-item <?php echo ($page === 'home') ? 'active' : ''; ?>">
        <span class="material-symbols-outlined nav-icon">home</span>
        <span class="nav-label">Home</span>
    </a>
    <a href="index.php?page=entries" class="nav-item <?php echo ($page === 'entries') ? 'active' : ''; ?>">
        <span class="material-symbols-outlined nav-icon">list</span>
        <span class="nav-label">Entries</span>
    </a>
    <a href="index.php?page=settings" class="nav-item <?php echo ($page === 'settings') ? 'active' : ''; ?>">
        <span class="material-symbols-outlined nav-icon">settings</span>
        <span class="nav-label">Settings</span>
    </a>
</nav>
