<?php
include("auth_verifier.php");
include("db.php");

$current = "dashboard";

$totalUsers = 0;
$totalItems = 0;
$pendingUsers = 0;
$approvedUsers = 0;
$rejectedUsers = 0;
$pendingItems = 0;
$approvedItems = 0;
$rejectedItems = 0;

$result = mysqli_query($conn, "SELECT COUNT(*) AS total FROM users WHERE role_id IN (2,3)");
if ($row = mysqli_fetch_assoc($result)) {
    $totalUsers = $row["total"];
}

$result = mysqli_query($conn, "SELECT COUNT(*) AS total FROM items");
if ($row = mysqli_fetch_assoc($result)) {
    $totalItems = $row["total"];
}

$result = mysqli_query($conn, "SELECT COUNT(*) AS total FROM nid_verifications WHERE status = 'pending'");
if ($row = mysqli_fetch_assoc($result)) {
    $pendingUsers = $row["total"];
}

$result = mysqli_query($conn, "SELECT COUNT(*) AS total FROM nid_verifications WHERE status = 'approved'");
if ($row = mysqli_fetch_assoc($result)) {
    $approvedUsers = $row["total"];
}

$result = mysqli_query($conn, "SELECT COUNT(*) AS total FROM nid_verifications WHERE status = 'rejected'");
if ($row = mysqli_fetch_assoc($result)) {
    $rejectedUsers = $row["total"];
}

$result = mysqli_query($conn, "SELECT COUNT(*) AS total FROM ownership_verifications WHERE status = 'pending'");
if ($row = mysqli_fetch_assoc($result)) {
    $pendingItems = $row["total"];
}

$result = mysqli_query($conn, "SELECT COUNT(*) AS total FROM ownership_verifications WHERE status = 'approved'");
if ($row = mysqli_fetch_assoc($result)) {
    $approvedItems = $row["total"];
}

$result = mysqli_query($conn, "SELECT COUNT(*) AS total FROM ownership_verifications WHERE status = 'rejected'");
if ($row = mysqli_fetch_assoc($result)) {
    $rejectedItems = $row["total"];
}

$recentUsers = mysqli_query($conn, "
    SELECT nv.*, u.email
    FROM nid_verifications nv
    JOIN users u ON nv.user_id = u.user_id
    ORDER BY nv.verification_id DESC
    LIMIT 5
");

$recentItems = mysqli_query($conn, "
    SELECT ov.*, i.item_name
    FROM ownership_verifications ov
    JOIN items i ON ov.item_id = i.item_id
    ORDER BY ov.ownership_id DESC
    LIMIT 5
");
?>

<!DOCTYPE html>
<html lang="en">

<head>
<meta charset="UTF-8">
<title>Verifier Dashboard | BorrowBee</title>
<link rel="stylesheet" href="style.css">
</head>

<body class="app-body">

<div class="app-layout">

<?php include("verifier_sidebar.php"); ?>

<main class="main-content">

<header class="topbar">

    <div>
        <h1>Verifier Dashboard 🔎</h1>
        <p class="topbar-sub">
            Verify users and items and review verification history.
        </p>
    </div>

</header>


<div class="stats-grid">

    <div class="stat-card">
        <div class="stat-icon">👥</div>
        <div>
            <h3><?php echo $totalUsers; ?></h3>
            <p>Total Users</p>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon">📦</div>
        <div>
            <h3><?php echo $totalItems; ?></h3>
            <p>Total Items</p>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon">⏳</div>
        <div>
            <h3><?php echo $pendingUsers; ?></h3>
            <p>Pending Users</p>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon">⏳</div>
        <div>
            <h3><?php echo $pendingItems; ?></h3>
            <p>Pending Items</p>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon">✅</div>
        <div>
            <h3><?php echo $approvedUsers; ?></h3>
            <p>Approved Users</p>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon">✅</div>
        <div>
            <h3><?php echo $approvedItems; ?></h3>
            <p>Approved Items</p>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon">❌</div>
        <div>
            <h3><?php echo $rejectedUsers; ?></h3>
            <p>Rejected Users</p>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon">❌</div>
        <div>
            <h3><?php echo $rejectedItems; ?></h3>
            <p>Rejected Items</p>
        </div>
    </div>

</div>


<section class="panel">

<div class="panel-header">

    <div>
        <h2>Recent User Verifications</h2>
        <p>Latest user verification activities.</p>
    </div>

    <a href="verifier_users.php" class="btn-login btn-inline">
        View All
    </a>

</div>

<div class="table-responsive">

<table class="admin-table">

<thead>

<tr>
    <th>ID</th>
    <th>User</th>
    <th>NID</th>
    <th>Status</th>
    <th>Date</th>
</tr>

</thead>

<tbody>

<?php if (mysqli_num_rows($recentUsers) > 0): ?>

<?php while ($row = mysqli_fetch_assoc($recentUsers)): ?>

<tr>

<td>
#<?php echo htmlspecialchars($row["verification_id"]); ?>
</td>

<td>
<?php echo htmlspecialchars($row["user_id"]); ?>
<br>
<small>
<?php echo htmlspecialchars($row["email"]); ?>
</small>
</td>

<td>
<?php echo htmlspecialchars($row["nid_number"]); ?>
</td>

<td>
<span class="status-pill status-<?php echo htmlspecialchars($row["status"]); ?>">
<?php echo ucfirst(htmlspecialchars($row["status"])); ?>
</span>
</td>

<td>
<?php echo htmlspecialchars($row["verified_at"]); ?>
</td>

</tr>

<?php endwhile; ?>

<?php else: ?>

<tr>
<td colspan="5">No user verification history.</td>
</tr>

<?php endif; ?>

</tbody>

</table>

</div>

</section>


<section class="panel">

<div class="panel-header">

    <div>
        <h2>Recent Item Verifications</h2>
        <p>Latest item ownership verification activities.</p>
    </div>

    <a href="verifier_items.php" class="btn-login btn-inline">
        View All
    </a>

</div>

<div class="table-responsive">

<table class="admin-table">

<thead>

<tr>
    <th>ID</th>
    <th>Item</th>
    <th>Owner</th>
    <th>Status</th>
    <th>Date</th>
</tr>

</thead>

<tbody>

<?php if (mysqli_num_rows($recentItems) > 0): ?>

<?php while ($row = mysqli_fetch_assoc($recentItems)): ?>

<tr>

<td>
#<?php echo htmlspecialchars($row["ownership_id"]); ?>
</td>

<td>
<?php echo htmlspecialchars($row["item_name"]); ?>
</td>

<td>
<?php echo htmlspecialchars($row["owner_id"]); ?>
</td>

<td>
<span class="status-pill status-<?php echo htmlspecialchars($row["status"]); ?>">
<?php echo ucfirst(htmlspecialchars($row["status"])); ?>
</span>
</td>

<td>
<?php echo htmlspecialchars($row["verified_at"]); ?>
</td>

</tr>

<?php endwhile; ?>

<?php else: ?>

<tr>
<td colspan="5">No item verification history.</td>
</tr>

<?php endif; ?>

</tbody>

</table>

</div>

</section>

</main>

</div>

</body>
</html>