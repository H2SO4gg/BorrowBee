<?php
if (!isset($current)) {
    $current = "";
}
?>

<aside class="sidebar">

```
<div class="sidebar-brand">
    <span class="sidebar-badge">🐝</span>
    <span>BorrowBee</span>
</div>

<div class="sidebar-user">
    <div class="sidebar-user-icon">🛡️</div>
    <div>
        <strong>Verifier</strong>
        <small>Verifier Panel</small>
    </div>
</div>

<nav class="sidebar-nav sidebar-nav-buttons">

    <a href="verifier_dashboard.php"
       class="nav-btn <?php echo ($current == 'dashboard') ? 'active' : ''; ?>">
        <span class="nav-icon">🏠</span>
        Dashboard
    </a>

    <a href="verifier_users.php"
       class="nav-btn <?php echo ($current == 'users') ? 'active' : ''; ?>">
        <span class="nav-icon">👥</span>
        Verify Users
    </a>

    <a href="verifier_items.php"
       class="nav-btn <?php echo ($current == 'items') ? 'active' : ''; ?>">
        <span class="nav-icon">📦</span>
        Verify Items
    </a>

    <a href="verifier_history.php"
       class="nav-btn <?php echo ($current == 'history') ? 'active' : ''; ?>">
        <span class="nav-icon">📋</span>
        Verification History
    </a>

</nav>

<a href="logout.php" class="nav-btn sidebar-logout">
    <span class="nav-icon">🚪</span>
    Log Out
</a>

</aside>
