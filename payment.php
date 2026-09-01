<?php
include("auth_borrower.php");
include("db.php");

$request_id = isset($_GET["request_id"]) ? (int)$_GET["request_id"] : 0;
if ($request_id <= 0) {
    header("Location: my_requests.php");
    exit();
}

// Load the request + item, make sure it belongs to this borrower and is approved
$stmt = mysqli_prepare($conn, "
    SELECT rr.*, i.item_name, i.rental_price, i.security_deposit
    FROM rental_requests rr
    JOIN items i ON i.item_id = rr.item_id
    WHERE rr.request_id = ? AND rr.borrower_id = ?
");
mysqli_stmt_bind_param($stmt, "ii", $request_id, $borrower_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$req = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$req) {
    header("Location: my_requests.php");
    exit();
}

if ($req["status"] !== "approved") {
    header("Location: my_requests.php?msg=" . urlencode("This request isn't approved yet, so it can't be paid for."));
    exit();
}

// Check if already paid
$stmtPaid = mysqli_prepare($conn, "SELECT * FROM payments WHERE booking_id = ? AND status = 'completed' LIMIT 1");
mysqli_stmt_bind_param($stmtPaid, "i", $request_id);
mysqli_stmt_execute($stmtPaid);
$resPaid = mysqli_stmt_get_result($stmtPaid);
$alreadyPaid = mysqli_fetch_assoc($resPaid);
mysqli_stmt_close($stmtPaid);

// Calculate amount: (days * rental_price) + security_deposit
$days = max(1, (strtotime($req["end_date"]) - strtotime($req["start_date"])) / 86400);
$rentCost = $days * $req["rental_price"];
$totalAmount = $rentCost + $req["security_deposit"];

$current = "requests";
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Pay for <?php echo htmlspecialchars($req["item_name"]); ?> | BorrowBee</title>
<link rel="stylesheet" href="style.css">
</head>
<body class="app-body">
<div class="app-layout">
    <?php include("borrower_sidebar.php"); ?>

    <main class="main-content">
        <header class="topbar">
            <div>
                <h1>Complete Payment</h1>
                <p class="topbar-sub"><a href="my_requests.php" class="panel-link">← Back to My Requests</a></p>
            </div>
        </header>

        <?php if ($alreadyPaid): ?>
            <div class="success-msg">This request has already been paid for (Transaction ID: <?php echo htmlspecialchars($alreadyPaid["transaction_id"]); ?>).</div>
        <?php else: ?>

            <section class="panel panel-narrow">
                <div class="request-item-summary">
                    <h3><?php echo htmlspecialchars($req["item_name"]); ?></h3>
                    <div class="item-meta">
                        <span>📅 <?php echo htmlspecialchars($req["start_date"]); ?> → <?php echo htmlspecialchars($req["end_date"]); ?> (<?php echo (int)$days; ?> days)</span>
                    </div>
                    <div class="item-meta">
                        <span>Rent: ৳<?php echo number_format($rentCost, 2); ?></span>
                        <span>Deposit: ৳<?php echo number_format($req["security_deposit"], 2); ?></span>
                    </div>
                    <div class="pay-total-row">
                        <span>Total Payable</span>
                        <strong>৳<?php echo number_format($totalAmount, 2); ?></strong>
                    </div>
                </div>
            </section>

            <section class="panel panel-narrow">
                <h2 class="panel-title-inline">Choose a Payment Method</h2>

                <div class="payment-method-grid">
                    <a href="payment_process.php?request_id=<?php echo $request_id; ?>&method=bkash&amount=<?php echo $totalAmount; ?>" class="payment-method-card pm-bkash">
                        <div class="pm-logo">bKash</div>
                        <div class="pm-label">Pay with bKash</div>
                    </a>

                    <a href="payment_process.php?request_id=<?php echo $request_id; ?>&method=nagad&amount=<?php echo $totalAmount; ?>" class="payment-method-card pm-nagad">
                        <div class="pm-logo">Nagad</div>
                        <div class="pm-label">Pay with Nagad</div>
                    </a>

                    <a href="payment_process.php?request_id=<?php echo $request_id; ?>&method=bank&amount=<?php echo $totalAmount; ?>" class="payment-method-card pm-bank">
                        <div class="pm-logo">🏦</div>
                        <div class="pm-label">Bank Transfer</div>
                    </a>
                </div>

                <p class="sandbox-note">⚠️ This is a demo/sandbox payment simulation. No real money is processed.</p>
            </section>

        <?php endif; ?>
    </main>
</div>
</body>
</html>