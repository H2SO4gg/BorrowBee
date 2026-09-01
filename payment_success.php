<?php
include("auth_borrower.php");
include("db.php");

$request_id = isset($_GET["request_id"]) ? (int)$_GET["request_id"] : 0;
$txn        = isset($_GET["txn"]) ? $_GET["txn"] : "";

$stmt = mysqli_prepare($conn, "
    SELECT p.*, rr.item_id, i.item_name
    FROM payments p
    JOIN rental_requests rr ON rr.request_id = p.booking_id
    JOIN items i ON i.item_id = rr.item_id
    WHERE p.booking_id = ? AND p.payer_id = ? AND p.transaction_id = ?
    LIMIT 1
");
mysqli_stmt_bind_param($stmt, "iis", $request_id, $borrower_id, $txn);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$payment = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$payment) {
    header("Location: my_requests.php");
    exit();
}

$current = "requests";
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Payment Successful | BorrowBee</title>
<link rel="stylesheet" href="style.css">
</head>
<body class="app-body">
<div class="app-layout">
    <?php include("borrower_sidebar.php"); ?>

    <main class="main-content">
        <header class="topbar">
            <div>
                <h1>Payment Successful 🎉</h1>
                <p class="topbar-sub">Your payment has been recorded.</p>
            </div>
        </header>

        <section class="panel panel-narrow payment-success-panel">
            <div class="success-check">✅</div>
            <h2>৳<?php echo number_format($payment["amount"], 2); ?> Paid</h2>
            <p class="topbar-sub">for <?php echo htmlspecialchars($payment["item_name"]); ?></p>

            <table class="detail-table receipt-table">
                <tbody>
                    <tr>
                        <th>Transaction ID</th>
                        <td><?php echo htmlspecialchars($payment["transaction_id"]); ?></td>
                    </tr>
                    <tr>
                        <th>Payment Method</th>
                        <td><?php echo htmlspecialchars(ucfirst($payment["payment_method"])); ?></td>
                    </tr>
                    <tr>
                        <th>Status</th>
                        <td><span class="status-pill status-approved"><?php echo htmlspecialchars(ucfirst($payment["status"])); ?></span></td>
                    </tr>
                    <tr>
                        <th>Paid At</th>
                        <td><?php echo htmlspecialchars($payment["paid_at"]); ?></td>
                    </tr>
                </tbody>
            </table>

            <a href="my_requests.php" class="btn-login btn-block">BACK TO MY REQUESTS</a>
        </section>
    </main>
</div>
</body>
</html>