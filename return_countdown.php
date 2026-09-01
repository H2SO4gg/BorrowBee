<?php
include("auth_borrower.php");
include("db.php");

$notice = "";
$error  = "";

// Mark an item as returned
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["mark_returned_id"])) {
    $returnId = (int)$_POST["mark_returned_id"];

    $stmtCheck = mysqli_prepare($conn, "SELECT status FROM rental_requests WHERE request_id = ? AND borrower_id = ?");
    mysqli_stmt_bind_param($stmtCheck, "ii", $returnId, $borrower_id);
    mysqli_stmt_execute($stmtCheck);
    $resCheck = mysqli_stmt_get_result($stmtCheck);
    $reqRow = mysqli_fetch_assoc($resCheck);
    mysqli_stmt_close($stmtCheck);

    if ($reqRow && $reqRow["status"] === "approved") {
        $stmtReturn = mysqli_prepare($conn, "UPDATE rental_requests SET status = 'completed', returned_at = NOW() WHERE request_id = ? AND borrower_id = ?");
        mysqli_stmt_bind_param($stmtReturn, "ii", $returnId, $borrower_id);
        mysqli_stmt_execute($stmtReturn);
        mysqli_stmt_close($stmtReturn);
        $notice = "Marked as returned. Thanks for keeping your rentals on track!";
    } else {
        $error = "That item can't be marked as returned right now.";
    }
}

// Load all approved (currently borrowed) items for this borrower
$stmt = mysqli_prepare($conn, "
    SELECT rr.*, i.item_name, i.location
    FROM rental_requests rr
    JOIN items i ON i.item_id = rr.item_id
    WHERE rr.borrower_id = ? AND rr.status = 'approved'
    ORDER BY rr.end_date ASC
");
mysqli_stmt_bind_param($stmt, "i", $borrower_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$activeRentals = [];
$today = strtotime(date("Y-m-d"));
while ($row = mysqli_fetch_assoc($result)) {
    $endTime = strtotime($row["end_date"]);
    $daysLeft = round(($endTime - $today) / 86400);
    $row["daysLeft"] = $daysLeft;

    if ($daysLeft < 0) {
        $row["urgency"] = "overdue";
        $row["urgencyLabel"] = abs($daysLeft) . " day" . (abs($daysLeft) == 1 ? "" : "s") . " overdue";
    } elseif ($daysLeft == 0) {
        $row["urgency"] = "urgent";
        $row["urgencyLabel"] = "Due today";
    } elseif ($daysLeft <= 2) {
        $row["urgency"] = "urgent";
        $row["urgencyLabel"] = "Due in " . $daysLeft . " day" . ($daysLeft == 1 ? "" : "s");
    } elseif ($daysLeft <= 5) {
        $row["urgency"] = "soon";
        $row["urgencyLabel"] = "Due in " . $daysLeft . " days";
    } else {
        $row["urgency"] = "safe";
        $row["urgencyLabel"] = "Due in " . $daysLeft . " days";
    }

    $activeRentals[] = $row;
}
mysqli_stmt_close($stmt);

$current = "countdown";
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Return Countdown | BorrowBee</title>
<link rel="stylesheet" href="style.css">
</head>
<body class="app-body">
<div class="app-layout">
    <?php include("borrower_sidebar.php"); ?>

    <main class="main-content">
        <header class="topbar">
            <div>
                <h1>Return Countdown</h1>
                <p class="topbar-sub">Track when your borrowed items are due back.</p>
            </div>
        </header>

        <?php if ($notice): ?>
            <div class="success-msg"><?php echo htmlspecialchars($notice); ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="error-msg"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if (count($activeRentals) === 0): ?>
            <div class="empty-state">
                <p>You don't have any items currently borrowed.</p>
                <a href="borrower_dashboard.php" class="btn-login btn-inline">Browse Items</a>
            </div>
        <?php else: ?>
            <div class="countdown-list">
                <?php foreach ($activeRentals as $r): ?>
                    <div class="countdown-card countdown-<?php echo $r["urgency"]; ?>">
                        <div class="countdown-info">
                            <h3><?php echo htmlspecialchars($r["item_name"]); ?></h3>
                            <p class="topbar-sub">📍 <?php echo htmlspecialchars($r["location"]); ?> · Due <?php echo htmlspecialchars($r["end_date"]); ?></p>
                        </div>
                        <div class="countdown-badge">
                            <?php echo htmlspecialchars($r["urgencyLabel"]); ?>
                        </div>
                        <form method="post" action="return_countdown.php" onsubmit="return confirm('Confirm you have returned this item?');">
                            <input type="hidden" name="mark_returned_id" value="<?php echo (int)$r["request_id"]; ?>">
                            <button type="submit" class="btn-login btn-inline">Mark as Returned</button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>
</div>
</body>
</html>