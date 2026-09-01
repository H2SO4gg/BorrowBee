<?php
include("auth_admin.php");
include("db.php");

$current = "dashboard";

// Total Users
$totalUsers = 0;
$result = mysqli_query($conn, "SELECT COUNT(*) AS total FROM users");
if ($row = mysqli_fetch_assoc($result)) {
    $totalUsers = $row["total"];
}

// Total Borrowers
$totalBorrowers = 0;
$result = mysqli_query($conn, "SELECT COUNT(*) AS total FROM users WHERE role_id = 2");
if ($row = mysqli_fetch_assoc($result)) {
    $totalBorrowers = $row["total"];
}

// Total Owners
$totalOwners = 0;
$result = mysqli_query($conn, "SELECT COUNT(*) AS total FROM users WHERE role_id = 3");
if ($row = mysqli_fetch_assoc($result)) {
    $totalOwners = $row["total"];
}

// Total Items
$totalItems = 0;
$result = mysqli_query($conn, "SELECT COUNT(*) AS total FROM items");
if ($row = mysqli_fetch_assoc($result)) {
    $totalItems = $row["total"];
}

// Available Items
$availableItems = 0;
$result = mysqli_query($conn, "SELECT COUNT(*) AS total FROM items WHERE status = 'available'");
if ($row = mysqli_fetch_assoc($result)) {
    $availableItems = $row["total"];
}

// Pending Requests
$pendingRequests = 0;
$result = mysqli_query($conn, "SELECT COUNT(*) AS total FROM rental_requests WHERE status = 'pending'");
if ($row = mysqli_fetch_assoc($result)) {
    $pendingRequests = $row["total"];
}

// Approved Requests
$approvedRequests = 0;
$result = mysqli_query($conn, "SELECT COUNT(*) AS total FROM rental_requests WHERE status = 'approved'");
if ($row = mysqli_fetch_assoc($result)) {
    $approvedRequests = $row["total"];
}

// Completed Payments
$totalPayments = 0;
$result = mysqli_query($conn, "SELECT COUNT(*) AS total FROM payments WHERE status = 'completed'");
if ($row = mysqli_fetch_assoc($result)) {
    $totalPayments = $row["total"];
}

// Total Revenue
$totalRevenue = 0;
$result = mysqli_query($conn, "SELECT SUM(amount) AS total FROM payments WHERE status = 'completed'");
if ($row = mysqli_fetch_assoc($result)) {
    $totalRevenue = $row["total"] ?? 0;
}

// Recent Requests
$sql = "
    SELECT 
        rr.*,
        i.item_name,
        u.email
    FROM rental_requests rr
    JOIN items i ON rr.item_id = i.item_id
    JOIN users u ON rr.borrower_id = u.user_id
    ORDER BY rr.requested_at DESC
    LIMIT 5
";

$recentRequests = mysqli_query($conn, $sql);

?>
<!DOCTYPE html>
<html lang="en">

<head>
<meta charset="UTF-8">
<title>Admin Dashboard | BorrowBee</title>
<link rel="stylesheet" href="style.css">
</head>

<body class="app-body">

<div class="app-layout">

<?php include("admin_sidebar.php"); ?>

<main class="main-content">

<header class="topbar">
    <div>
        <h1>Admin Dashboard 👨‍💼</h1>
        <p class="topbar-sub">
            Manage users, items, rental requests and payments.
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
        <div class="stat-icon">🙋</div>
        <div>
            <h3><?php echo $totalBorrowers; ?></h3>
            <p>Borrowers</p>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon">🏪</div>
        <div>
            <h3><?php echo $totalOwners; ?></h3>
            <p>Owners</p>
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
        <div class="stat-icon">✅</div>
        <div>
            <h3><?php echo $availableItems; ?></h3>
            <p>Available Items</p>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon">⏳</div>
        <div>
            <h3><?php echo $pendingRequests; ?></h3>
            <p>Pending Requests</p>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon">👍</div>
        <div>
            <h3><?php echo $approvedRequests; ?></h3>
            <p>Approved Requests</p>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon">💳</div>
        <div>
            <h3><?php echo $totalPayments; ?></h3>
            <p>Completed Payments</p>
        </div>
    </div>

</div>


<section class="panel">

    <div class="panel-header">
        <div>
            <h2>Recent Rental Requests</h2>
            <p>Latest activities in the system.</p>
        </div>

        <a href="admin_requests.php" class="btn-login btn-inline">
            View All
        </a>
    </div>


    <div class="table-responsive">

        <table class="admin-table">

            <thead>
                <tr>
                    <th>Request ID</th>
                    <th>Item</th>
                    <th>Borrower</th>
                    <th>Start Date</th>
                    <th>End Date</th>
                    <th>Status</th>
                </tr>
            </thead>

            <tbody>

            <?php if (mysqli_num_rows($recentRequests) > 0): ?>

                <?php while ($row = mysqli_fetch_assoc($recentRequests)): ?>

                <tr>

                    <td>
                        #<?php echo htmlspecialchars($row["request_id"]); ?>
                    </td>

                    <td>
                        <?php echo htmlspecialchars($row["item_name"]); ?>
                    </td>

                    <td>
                        <?php echo htmlspecialchars($row["borrower_id"]); ?>
                        <br>
                        <small>
                            <?php echo htmlspecialchars($row["email"]); ?>
                        </small>
                    </td>

                    <td>
                        <?php echo htmlspecialchars($row["start_date"]); ?>
                    </td>

                    <td>
                        <?php echo htmlspecialchars($row["end_date"]); ?>
                    </td>

                    <td>

                        <span class="status-pill status-<?php echo htmlspecialchars($row["status"]); ?>">
                            <?php echo ucfirst(htmlspecialchars($row["status"])); ?>
                        </span>

                    </td>

                </tr>

                <?php endwhile; ?>

            <?php else: ?>

                <tr>
                    <td colspan="6">
                        No rental requests found.
                    </td>
                </tr>

            <?php endif; ?>

            </tbody>

        </table>

    </div>

</section>


<section class="panel admin-revenue-panel">

    <h2>Total Revenue</h2>

    <div class="revenue-number">
        ৳<?php echo number_format($totalRevenue, 2); ?>
    </div>

    <p>
        Revenue from completed payments.
    </p>

</section>

</main>

</div>

</body>
</html>