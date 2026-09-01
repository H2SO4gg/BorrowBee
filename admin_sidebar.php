<?php
if (!isset($current)) {
    $current = "";
}
?>

<aside class="sidebar">

    <div class="sidebar-brand">
        <span class="sidebar-badge">🐝</span>
        <span>BorrowBee</span>
    </div>

    <div class="sidebar-user">
        <div class="sidebar-user-icon">👨‍💼</div>
        <div>
            <strong>Administrator</strong>
            <small>Admin Panel</small>
        </div>
    </div>

    <nav class="sidebar-nav sidebar-nav-buttons">

        <a href="admin_dashboard.php"
           class="nav-btn <?php echo ($current == 'dashboard') ? 'active' : ''; ?>">
            <span class="nav-icon">🏠</span>
            Dashboard
        </a>

        <a href="admin_users.php"
           class="nav-btn <?php echo ($current == 'users') ? 'active' : ''; ?>">
            <span class="nav-icon">👥</span>
            Manage Users
        </a>

        <a href="admin_items.php"
           class="nav-btn <?php echo ($current == 'items') ? 'active' : ''; ?>">
            <span class="nav-icon">📦</span>
            Manage Items
        </a>

        <a href="admin_requests.php"
           class="nav-btn <?php echo ($current == 'requests') ? 'active' : ''; ?>">
            <span class="nav-icon">📋</span>
            Rental Requests
        </a>

        <a href="admin_payments.php"
           class="nav-btn <?php echo ($current == 'payments') ? 'active' : ''; ?>">
            <span class="nav-icon">💳</span>
            Payments
        </a>

    </nav>

    <a href="logout.php" class="nav-btn sidebar-logout">
        <span class="nav-icon">🚪</span>
        Log Out
    </a>

</aside>