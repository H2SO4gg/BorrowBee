<?php
// Expects $current to be set by the including page
// (e.g. "dashboard", "browse", "requests", "profile", "cart", "reliability", "countdown")
if (!isset($current)) { $current = ""; }

// Cart item count badge (only if $conn + $borrower_id are available)
$cartCount = 0;
$dueSoonCount = 0;
if (isset($conn) && isset($borrower_id)) {
    $stmtCartCount = mysqli_prepare($conn, "SELECT COUNT(*) AS c FROM cart WHERE borrower_id = ?");
    mysqli_stmt_bind_param($stmtCartCount, "i", $borrower_id);
    mysqli_stmt_execute($stmtCartCount);
    $resCartCount = mysqli_stmt_get_result($stmtCartCount);
    if ($rowCartCount = mysqli_fetch_assoc($resCartCount)) { $cartCount = (int)$rowCartCount["c"]; }
    mysqli_stmt_close($stmtCartCount);

    // Items due within 2 days or already overdue, for the sidebar badge
    $stmtDueSoon = mysqli_prepare($conn, "
        SELECT COUNT(*) AS c FROM rental_requests
        WHERE borrower_id = ? AND status = 'approved' AND end_date <= DATE_ADD(CURDATE(), INTERVAL 2 DAY)
    ");
    mysqli_stmt_bind_param($stmtDueSoon, "i", $borrower_id);
    mysqli_stmt_execute($stmtDueSoon);
    $resDueSoon = mysqli_stmt_get_result($stmtDueSoon);
    if ($rowDueSoon = mysqli_fetch_assoc($resDueSoon)) { $dueSoonCount = (int)$rowDueSoon["c"]; }
    mysqli_stmt_close($stmtDueSoon);
}
?>
<aside class="sidebar">
    <div class="sidebar-brand">
        <span class="sidebar-badge">🐝</span>
        <span>BorrowBee</span>
    </div>
    <nav class="sidebar-nav sidebar-nav-buttons">
        <a href="borrower_profile.php" class="nav-btn <?php echo $current === 'profile' ? 'active' : ''; ?>">
            <span class="nav-icon">👤</span> My Profile
        </a>
        <a href="my_requests.php" class="nav-btn <?php echo $current === 'requests' ? 'active' : ''; ?>">
            <span class="nav-icon">📦</span> My Request
        </a>
        <a href="borrower_dashboard.php" class="nav-btn <?php echo $current === 'dashboard' ? 'active' : ''; ?>">
            <span class="nav-icon">🏠</span> Dashboard
        </a>
        <a href="my_requests.php" class="nav-btn <?php echo $current === 'history' ? 'active' : ''; ?>">
            <span class="nav-icon">🕘</span> History
        </a>
        <a href="view_cart.php" class="nav-btn <?php echo $current === 'cart' ? 'active' : ''; ?>">
            <span class="nav-icon">🛒</span> View Cart
            <?php if ($cartCount > 0): ?><span class="nav-badge"><?php echo $cartCount; ?></span><?php endif; ?>
        </a>
        <a href="reliability_score.php" class="nav-btn <?php echo $current === 'reliability' ? 'active' : ''; ?>">
            <span class="nav-icon">⭐</span> Reliability Score
        </a>
        <a href="return_countdown.php" class="nav-btn <?php echo $current === 'countdown' ? 'active' : ''; ?>">
            <span class="nav-icon">⏳</span> Return Countdown
            <?php if ($dueSoonCount > 0): ?><span class="nav-badge nav-badge-urgent"><?php echo $dueSoonCount; ?></span><?php endif; ?>
        </a>
    </nav>
    <a href="logout.php" class="nav-btn sidebar-logout">
        <span class="nav-icon">🚪</span> Log Out
    </a>
</aside>