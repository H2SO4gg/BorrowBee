<?php
include("auth_admin.php");
include("db.php");

$current = "requests";

$message = "";


if ($_SERVER["REQUEST_METHOD"] == "POST") {

    if (
        isset($_POST["request_id"])
        &&
        isset($_POST["status"])
    ) {

        $request_id = (int)$_POST["request_id"];

        $status = $_POST["status"];

        $allowedStatuses = [
            "pending",
            "approved",
            "rejected",
            "cancelled",
            "completed"
        ];


        if (in_array($status, $allowedStatuses)) {

            $stmt = mysqli_prepare(
                $conn,
                "UPDATE rental_requests SET status = ? WHERE request_id = ?"
            );

            mysqli_stmt_bind_param(
                $stmt,
                "si",
                $status,
                $request_id
            );

            mysqli_stmt_execute($stmt);

            mysqli_stmt_close($stmt);

            $message = "Request status updated successfully.";
        }
    }
}


$statusFilter = isset($_GET["status"])
    ? $_GET["status"]
    : "all";


$sql = "
SELECT

rr.*,

i.item_name,

i.rental_price,

i.location,

u.email,

up.full_name

FROM rental_requests rr

JOIN items i
ON rr.item_id = i.item_id

JOIN users u
ON rr.borrower_id = u.user_id

LEFT JOIN user_profiles up
ON u.user_id = up.user_id
";


if ($statusFilter != "all") {

    $sql .= "
    WHERE rr.status = ?
    ORDER BY rr.requested_at DESC
    ";

    $stmt = mysqli_prepare($conn, $sql);

    mysqli_stmt_bind_param(
        $stmt,
        "s",
        $statusFilter
    );

} else {

    $sql .= "
    ORDER BY rr.requested_at DESC
    ";

    $stmt = mysqli_prepare($conn, $sql);
}


mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$requests = [];

while ($row = mysqli_fetch_assoc($result)) {

    $requests[] = $row;

}

mysqli_stmt_close($stmt);

?>


<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<title>Rental Requests | BorrowBee</title>

<link rel="stylesheet" href="style.css">

</head>


<body class="app-body">


<div class="app-layout">


<?php include("admin_sidebar.php"); ?>


<main class="main-content">


<header class="topbar">

<div>

<h1>Rental Requests 📋</h1>

<p class="topbar-sub">

Monitor and manage all rental requests.

</p>

</div>


<form method="get">

<select
name="status"
onchange="this.form.submit()">

<option value="all">

All Requests

</option>


<option value="pending"
<?php echo ($statusFilter == "pending") ? "selected" : ""; ?>>

Pending

</option>


<option value="approved"
<?php echo ($statusFilter == "approved") ? "selected" : ""; ?>>

Approved

</option>


<option value="rejected"
<?php echo ($statusFilter == "rejected") ? "selected" : ""; ?>>

Rejected

</option>


<option value="cancelled"
<?php echo ($statusFilter == "cancelled") ? "selected" : ""; ?>>

Cancelled

</option>


<option value="completed"
<?php echo ($statusFilter == "completed") ? "selected" : ""; ?>>

Completed

</option>


</select>

</form>

</header>


<?php if ($message != ""): ?>

<div class="success-msg">

<?php echo htmlspecialchars($message); ?>

</div>

<?php endif; ?>


<section class="panel">


<div class="table-responsive">


<table class="admin-table">


<thead>

<tr>

<th>ID</th>

<th>Item</th>

<th>Borrower</th>

<th>Rental Date</th>

<th>Message</th>

<th>Status</th>

<th>Update</th>

</tr>

</thead>


<tbody>


<?php foreach ($requests as $request): ?>


<tr>


<td>

#<?php echo $request["request_id"]; ?>

</td>


<td>

<strong>

<?php echo htmlspecialchars($request["item_name"]); ?>

</strong>

<br>

<small>

৳<?php echo number_format($request["rental_price"], 2); ?>/day

</small>

</td>


<td>

<?php echo htmlspecialchars(
$request["full_name"] ?? "Unknown"
); ?>

<br>

<small>

<?php echo htmlspecialchars($request["email"]); ?>

</small>

</td>


<td>

<?php echo htmlspecialchars($request["start_date"]); ?>

<br>

to

<br>

<?php echo htmlspecialchars($request["end_date"]); ?>

</td>


<td>

<?php echo htmlspecialchars(
$request["message"] ?? "-"
); ?>

</td>


<td>

<span class="status-pill status-<?php echo htmlspecialchars($request["status"]); ?>">

<?php echo ucfirst(
htmlspecialchars($request["status"])
); ?>

</span>

</td>


<td>


<form method="post">


<input
type="hidden"
name="request_id"
value="<?php echo $request["request_id"]; ?>">


<select name="status">

<?php

$statuses = [
"pending",
"approved",
"rejected",
"cancelled",
"completed"
];

foreach ($statuses as $status):

?>


<option
value="<?php echo $status; ?>"
<?php echo ($request["status"] == $status) ? "selected" : ""; ?>>

<?php echo ucfirst($status); ?>

</option>


<?php endforeach; ?>


</select>


<button
type="submit"
class="btn-small">

Update

</button>


</form>


</td>


</tr>


<?php endforeach; ?>


</tbody>


</table>


</div>


</section>


</main>


</div>


</body>


</html>