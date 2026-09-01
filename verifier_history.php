<?php
include("auth_verifier.php");
include("db.php");

$current = "history";

$userHistory = mysqli_query($conn, "
    SELECT nv.*, u.email
    FROM nid_verifications nv
    JOIN users u ON nv.user_id = u.user_id
    WHERE nv.status IN ('approved','rejected')
    ORDER BY nv.verification_id DESC
");

$itemHistory = mysqli_query($conn, "
    SELECT ov.*, i.item_name
    FROM ownership_verifications ov
    JOIN items i ON ov.item_id = i.item_id
    WHERE ov.status IN ('approved','rejected')
    ORDER BY ov.ownership_id DESC
");
?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<title>Verification History | BorrowBee</title>

<link rel="stylesheet" href="style.css">

</head>

<body class="app-body">

<div class="app-layout">

<?php include("verifier_sidebar.php"); ?>

<main class="main-content">

<header class="topbar">

<div>

<h1>Verification History 📜</h1>

<p class="topbar-sub">
Past user and item approval/rejection records.
</p>

</div>

</header>


<section class="panel">

<div class="panel-header">

<div>

<h2>User Verification History</h2>

<p>Previous user approvals and rejections.</p>

</div>

</div>


<div class="table-responsive">

<table class="admin-table">

<thead>

<tr>

<th>ID</th>
<th>User</th>
<th>NID</th>
<th>Status</th>
<th>Verifier</th>
<th>Remarks</th>
<th>Date</th>

</tr>

</thead>

<tbody>

<?php if (mysqli_num_rows($userHistory) > 0): ?>

<?php while ($row = mysqli_fetch_assoc($userHistory)): ?>

<tr>

<td>
#<?php echo htmlspecialchars($row["verification_id"]); ?>
</td>

<td>
<?php echo htmlspecialchars($row["email"]); ?>
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
<?php echo htmlspecialchars($row["verified_by"]); ?>
</td>

<td>
<?php echo htmlspecialchars($row["remarks"]); ?>
</td>

<td>
<?php echo htmlspecialchars($row["verified_at"]); ?>
</td>

</tr>

<?php endwhile; ?>

<?php else: ?>

<tr>

<td colspan="7">
No user history found.
</td>

</tr>

<?php endif; ?>

</tbody>

</table>

</div>

</section>


<section class="panel">

<div class="panel-header">

<div>

<h2>Item Verification History</h2>

<p>Previous item ownership approvals and rejections.</p>

</div>

</div>


<div class="table-responsive">

<table class="admin-table">

<thead>

<tr>

<th>ID</th>
<th>Item</th>
<th>Owner</th>
<th>Status</th>
<th>Verifier</th>
<th>Remarks</th>
<th>Date</th>

</tr>

</thead>

<tbody>

<?php if (mysqli_num_rows($itemHistory) > 0): ?>

<?php while ($row = mysqli_fetch_assoc($itemHistory)): ?>

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
<?php echo htmlspecialchars($row["verified_by"]); ?>
</td>

<td>
<?php echo htmlspecialchars($row["remarks"]); ?>
</td>

<td>
<?php echo htmlspecialchars($row["verified_at"]); ?>
</td>

</tr>

<?php endwhile; ?>

<?php else: ?>

<tr>

<td colspan="7">
No item history found.
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