<?php if (!isset($current)) { $current = ''; } ?>
<aside class="sidebar">
    <div class="sidebar-brand"><span class="sidebar-badge">🐝</span><span>BorrowBee</span></div>
    <nav class="sidebar-nav">
        <a href="owner_profile.php" class="nav-btn <?php echo $current==='profile'?'active':''; ?>">👤 <span>My Profile</span></a>
        <a href="owner_dashboard.php" class="nav-btn <?php echo $current==='dashboard'?'active':''; ?>">🏠 <span>Dashboard</span></a>
        <a href="owner_items.php" class="nav-btn <?php echo $current==='items'?'active':''; ?>">📦 <span>My Items</span></a>
        <a href="add_item.php" class="nav-btn <?php echo $current==='add'?'active':''; ?>">➕ <span>Add Item</span></a>
        <a href="owner_requests.php" class="nav-btn <?php echo $current==='requests'?'active':''; ?>">📨 <span>Rental Requests</span></a>
        <a href="owner_history.php" class="nav-btn <?php echo $current==='history'?'active':''; ?>">🕘 <span>History</span></a>
    </nav>
    <a href="logout.php" class="nav-btn sidebar-logout">🚪 <span>Log Out</span></a>
</aside>
