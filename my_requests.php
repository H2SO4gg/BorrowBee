<?php
include("auth_borrower.php");
include("db.php");

$notice = "";

// Allow cancelling a pending request
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["cancel_request_id"])) {
    $cancelId = (int)$_POST["cancel_request_id"];

    $stmtCheck = mysqli_prepare($conn, "SELECT status FROM rental_requests WHERE request_id = ? AND borrower_id = ?");
    mysqli_stmt_bind_param($stmtCheck, "ii", $cancelId, $borrower_id);
    mysqli_stmt_execute($stmtCheck);
    $resCheck = mysqli_stmt_get_result($stmtCheck);
    $reqRow = mysqli_fetch_assoc($resCheck);
    mysqli_stmt_close($stmtCheck);

    if ($reqRow && $reqRow["status"] === "pending") {
        $stmtCancel = mysqli_prepare($conn, "UPDATE rental_requests SET status = 'cancelled' WHERE request_id = ? AND borrower_id = ?");
        mysqli_stmt_bind_param($stmtCancel, "ii", $cancelId, $borrower_id);
        mysqli_stmt_execute($stmtCancel);
        mysqli_stmt_close($stmtCancel);
        $notice = "Request cancelled.";
    } else {
        $notice = "That request can no longer be cancelled.";
    }
}

// Optional filter by status
$filter = isset($_GET["status"]) ? trim($_GET["status"]) : "all";
$validFilters = ["all", "pending", "approved", "rejected", "cancelled", "completed"];
if (!in_array($filter, $validFilters)) { $filter = "all"; }

if ($filter === "all") {
    $sql = "
        SELECT rr.*, i.item_name, i.rental_price, i.location
        FROM rental_requests rr
        JOIN items i ON i.item_id = rr.item_id
        WHERE rr.borrower_id = ?
        ORDER BY rr.requested_at DESC
    ";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $borrower_id);
} else {
    $sql = "
        SELECT rr.*, i.item_name, i.rental_price, i.location
        FROM rental_requests rr
        JOIN items i ON i.item_id = rr.item_id
        WHERE rr.borrower_id = ? AND rr.status = ?
        ORDER BY rr.requested_at DESC
    ";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "is", $borrower_id, $filter);
}
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$requests = [];
while ($row = mysqli_fetch_assoc($result)) { $requests[] = $row; }
mysqli_stmt_close($stmt);

// Which of these requests are already paid? (used to hide/show the Pay Now button)
$paidRequestIds = [];
if (count($requests) > 0) {
    $stmtPaid = mysqli_prepare($conn, "SELECT booking_id FROM payments WHERE payer_id = ? AND status = 'completed'");
    mysqli_stmt_bind_param($stmtPaid, "i", $borrower_id);
    mysqli_stmt_execute($stmtPaid);
    $resPaid = mysqli_stmt_get_result($stmtPaid);
    while ($rowPaid = mysqli_fetch_assoc($resPaid)) {
        $paidRequestIds[] = (int)$rowPaid["booking_id"];
    }
    mysqli_stmt_close($stmtPaid);
}

// ---------- Stat counts (always across ALL requests, regardless of the filter tab) ----------
$totalRequests = 0;
$stmt1 = mysqli_prepare($conn, "SELECT COUNT(*) AS c FROM rental_requests WHERE borrower_id = ?");
mysqli_stmt_bind_param($stmt1, "i", $borrower_id);
mysqli_stmt_execute($stmt1);
$r1 = mysqli_stmt_get_result($stmt1);
if ($row1 = mysqli_fetch_assoc($r1)) { $totalRequests = (int)$row1["c"]; }
mysqli_stmt_close($stmt1);

$pendingRequests = 0;
$stmt2 = mysqli_prepare($conn, "SELECT COUNT(*) AS c FROM rental_requests WHERE borrower_id = ? AND status = 'pending'");
mysqli_stmt_bind_param($stmt2, "i", $borrower_id);
mysqli_stmt_execute($stmt2);
$r2 = mysqli_stmt_get_result($stmt2);
if ($row2 = mysqli_fetch_assoc($r2)) { $pendingRequests = (int)$row2["c"]; }
mysqli_stmt_close($stmt2);

$approvedRequests = 0;
$stmt3 = mysqli_prepare($conn, "SELECT COUNT(*) AS c FROM rental_requests WHERE borrower_id = ? AND status = 'approved'");
mysqli_stmt_bind_param($stmt3, "i", $borrower_id);
mysqli_stmt_execute($stmt3);
$r3 = mysqli_stmt_get_result($stmt3);
if ($row3 = mysqli_fetch_assoc($r3)) { $approvedRequests = (int)$row3["c"]; }
mysqli_stmt_close($stmt3);

$closedRequests = 0;
$stmt4 = mysqli_prepare($conn, "SELECT COUNT(*) AS c FROM rental_requests WHERE borrower_id = ? AND status NOT IN ('pending','approved')");
mysqli_stmt_bind_param($stmt4, "i", $borrower_id);
mysqli_stmt_execute($stmt4);
$r4 = mysqli_stmt_get_result($stmt4);
if ($row4 = mysqli_fetch_assoc($r4)) { $closedRequests = (int)$row4["c"]; }
mysqli_stmt_close($stmt4);

$current = "requests";
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>My Requests | BorrowBee</title>
<link rel="stylesheet" href="style.css">
</head>
<body class="app-body">
<div class="app-layout">
    <?php include("borrower_sidebar.php"); ?>

    <main class="main-content">
        <header class="topbar">
            <div>
                <h1>My Requests</h1>
                <p class="topbar-sub">Track the status of everything you've requested to borrow.</p>
            </div>
        </header>

        <section class="stat-grid">
            <div class="stat-card">
                <div class="stat-icon">📦</div>
                <div class="stat-value"><?php echo $totalRequests; ?></div>
                <div class="stat-label">Total Requests</div>
            </div>
            <div class="stat-card stat-pending">
                <div class="stat-icon">⏳</div>
                <div class="stat-value"><?php echo $pendingRequests; ?></div>
                <div class="stat-label">Pending</div>
            </div>
            <div class="stat-card stat-approved">
                <div class="stat-icon">✅</div>
                <div class="stat-value"><?php echo $approvedRequests; ?></div>
                <div class="stat-label">Approved / Active</div>
            </div>
            <div class="stat-card stat-other">
                <div class="stat-icon">📁</div>
                <div class="stat-value"><?php echo $closedRequests; ?></div>
                <div class="stat-label">Closed / Rejected</div>
            </div>
        </section>

        <?php if ($notice): ?>
            <div class="success-msg"><?php echo htmlspecialchars($notice); ?></div>
        <?php endif; ?>

        <div class="filter-tabs">
            <?php foreach ($validFilters as $f): ?>
                <a href="my_requests.php?status=<?php echo $f; ?>" class="filter-tab <?php echo $filter === $f ? 'active' : ''; ?>">
                    <?php echo ucfirst($f); ?>
                </a>
            <?php endforeach; ?>
        </div>

        <?php if (count($requests) === 0): ?>
            <div class="empty-state">
                <p>No requests found<?php echo $filter !== 'all' ? ' with status "' . htmlspecialchars($filter) . '"' : ''; ?>.</p>
                <a href="borrower_dashboard.php" class="btn-login btn-inline">Browse Items</a>
            </div>
        <?php else: ?>
            <section class="panel">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th>Location</th>
                            <th>Price/day</th>
                            <th>Start</th>
                            <th>End</th>
                            <th>Requested</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($requests as $req): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($req["item_name"]); ?></td>
                                <td><?php echo htmlspecialchars($req["location"]); ?></td>
                                <td>৳<?php echo htmlspecialchars(number_format($req["rental_price"], 2)); ?></td>
                                <td><?php echo htmlspecialchars($req["start_date"]); ?></td>
                                <td><?php echo htmlspecialchars($req["end_date"]); ?></td>
                                <td><?php echo htmlspecialchars($req["requested_at"]); ?></td>
                                <td>
                                    <span class="status-pill status-<?php echo htmlspecialchars(strtolower($req["status"])); ?>">
                                        <?php echo htmlspecialchars(ucfirst($req["status"])); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($req["status"] === "pending"): ?>
                                        <form method="post" action="my_requests.php?status=<?php echo $filter; ?>" onsubmit="return confirm('Cancel this request?');">
                                            <input type="hidden" name="cancel_request_id" value="<?php echo (int)$req["request_id"]; ?>">
                                            <button type="submit" class="btn-cancel">Cancel</button>
                                        </form>
                                    <?php elseif ($req["status"] === "approved"): ?>
                                        <?php if (in_array((int)$req["request_id"], $paidRequestIds)): ?>
                                            <span class="status-pill status-approved">Paid ✓</span>
                                        <?php else: ?>
                                            <a href="payment.php?request_id=<?php echo (int)$req["request_id"]; ?>" class="btn-login btn-inline btn-pay-now">Pay Now</a>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </section>
        <?php endif; ?>
    </main>
</div>
</body>
</html>