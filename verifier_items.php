<?php
include("auth_verifier.php");
include("db.php");

$current = "items";
$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $ownership_id = (int)$_POST["ownership_id"];
    $status = $_POST["status"];
    $remarks = trim($_POST["remarks"]);

    if ($status != "approved" && $status != "rejected") {

        $error = "Invalid verification status.";

    } else {

        $stmt = mysqli_prepare($conn, "
            UPDATE ownership_verifications
            SET status = ?, verified_by = ?, remarks = ?, verified_at = CURDATE()
            WHERE ownership_id = ?
        ");

        mysqli_stmt_bind_param(
            $stmt,
            "sisi",
            $status,
            $verifier_id,
            $remarks,
            $ownership_id
        );

        if (mysqli_stmt_execute($stmt)) {
            $success = "Item verification updated successfully.";
        } else {
            $error = "Could not update verification.";
        }

        mysqli_stmt_close($stmt);
    }
}

$result = mysqli_query($conn, "
    SELECT ov.*, i.item_name, i.description, i.rental_price
    FROM ownership_verifications ov
    JOIN items i ON ov.item_id = i.item_id
    ORDER BY ov.ownership_id DESC
");
?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<title>Verify Items | BorrowBee</title>

<link rel="stylesheet" href="style.css">

</head>

<body class="app-body">

<div class="app-layout">

<?php include("verifier_sidebar.php"); ?>

<main class="main-content">

<header class="topbar">

<div>

<h1>Verify Items 📦</h1>

<p class="topbar-sub">
Approve or reject item ownership verification.
</p>

</div>

</header>


<?php if ($error): ?>

<div class="error-msg">
<?php echo htmlspecialchars($error); ?>
</div>

<?php endif; ?>


<?php if ($success): ?>

<div class="success-msg">
<?php echo htmlspecialchars($success); ?>
</div>

<?php endif; ?>


<section class="panel">

<div class="table-responsive">

<table class="admin-table">

<thead>

<tr>

<th>ID</th>
<th>Item</th>
<th>Owner</th>
<th>Price</th>
<th>Status</th>
<th>Action</th>

</tr>

</thead>

<tbody>

<?php if (mysqli_num_rows($result) > 0): ?>

<?php while ($row = mysqli_fetch_assoc($result)): ?>

<tr>

<td>
#<?php echo htmlspecialchars($row["ownership_id"]); ?>
</td>

<td>

<strong>
<?php echo htmlspecialchars($row["item_name"]); ?>
</strong>

<br>

<small>
<?php echo htmlspecialchars($row["description"]); ?>
</small>

</td>

<td>
<?php echo htmlspecialchars($row["owner_id"]); ?>
</td>

<td>
৳<?php echo number_format($row["rental_price"], 2); ?>
</td>

<td>

<span class="status-pill status-<?php echo htmlspecialchars($row["status"]); ?>">

<?php echo ucfirst(htmlspecialchars($row["status"])); ?>

</span>

</td>

<td>

<?php if ($row["status"] == "pending"): ?>

<form method="post">

<input type="hidden"
       name="ownership_id"
       value="<?php echo $row["ownership_id"]; ?>">

<input type="text"
       name="remarks"
       placeholder="Remarks"
       required>

<button type="submit"
        name="status"
        value="approved"
        class="btn-login btn-inline">
Approve
</button>

<button type="submit"
        name="status"
        value="rejected"
        class="btn-secondary">
Reject
</button>

</form>

<?php else: ?>

Already Reviewed

<?php endif; ?>

</td>

</tr>

<?php endwhile; ?>

<?php else: ?>

<tr>

<td colspan="6">
No item verification records found.
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