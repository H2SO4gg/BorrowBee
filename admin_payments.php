<?php
include("auth_admin.php");
include("db.php");

$current = "payments";


$sql = "

SELECT

p.*,

rr.item_id,

i.item_name,

u.email,

up.full_name

FROM payments p

LEFT JOIN rental_requests rr
ON p.booking_id = rr.request_id

LEFT JOIN items i
ON rr.item_id = i.item_id

LEFT JOIN users u
ON p.payer_id = u.user_id

LEFT JOIN user_profiles up
ON u.user_id = up.user_id

ORDER BY p.paid_at DESC

";


$result = mysqli_query($conn, $sql);


$payments = [];

while ($row = mysqli_fetch_assoc($result)) {

    $payments[] = $row;

}


$totalRevenue = 0;

foreach ($payments as $payment) {

    if ($payment["status"] == "completed") {

        $totalRevenue += $payment["amount"];

    }
}

?>


<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<title>Payments | BorrowBee</title>

<link rel="stylesheet" href="style.css">

</head>


<body class="app-body">


<div class="app-layout">


<?php include("admin_sidebar.php"); ?>


<main class="main-content">


<header class="topbar">

<div>

<h1>Payments 💳</h1>

<p class="topbar-sub">

Monitor all rental payments.

</p>

</div>


<div class="revenue-summary">

<strong>

Total Revenue

</strong>

<br>

<span>

৳<?php echo number_format($totalRevenue, 2); ?>

</span>

</div>


</header>


<section class="panel">


<div class="table-responsive">


<table class="admin-table">


<thead>

<tr>

<th>Payment ID</th>

<th>Transaction ID</th>

<th>Borrower</th>

<th>Item</th>

<th>Amount</th>

<th>Method</th>

<th>Status</th>

<th>Paid At</th>

</tr>

</thead>


<tbody>


<?php if (count($payments) > 0): ?>


<?php foreach ($payments as $payment): ?>


<tr>


<td>

#<?php echo htmlspecialchars($payment["payment_id"]); ?>

</td>


<td>

<?php echo htmlspecialchars(
$payment["transaction_id"]
); ?>

</td>


<td>

<?php echo htmlspecialchars(
$payment["full_name"] ?? "Unknown"
); ?>

<br>

<small>

<?php echo htmlspecialchars(
$payment["email"] ?? ""
); ?>

</small>

</td>


<td>

<?php echo htmlspecialchars(
$payment["item_name"] ?? "Unknown"
); ?>

</td>


<td>

৳<?php echo number_format(
$payment["amount"],
2
); ?>

</td>


<td>

<?php echo htmlspecialchars(
$payment["payment_method"]
); ?>

</td>


<td>

<span class="status-pill status-<?php echo htmlspecialchars($payment["status"]); ?>">

<?php echo ucfirst(
htmlspecialchars($payment["status"])
); ?>

</span>

</td>


<td>

<?php echo htmlspecialchars(
$payment["paid_at"]
); ?>

</td>


</tr>


<?php endforeach; ?>


<?php else: ?>


<tr>

<td colspan="8">

No payments found.

</td>

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