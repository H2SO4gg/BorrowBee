<?php
include("auth_verifier.php");
include("db.php");

$current = "users";
$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $verification_id = (int)$_POST["verification_id"];
    $status = $_POST["status"];
    $remarks = trim($_POST["remarks"]);

    if ($status != "approved" && $status != "rejected") {

        $error = "Invalid verification status.";

    } else {

        $stmt = mysqli_prepare($conn, "
            UPDATE nid_verifications
            SET status = ?, verified_by = ?, remarks = ?, verified_at = CURDATE()
            WHERE verification_id = ?
        ");

        mysqli_stmt_bind_param(
            $stmt,
            "sisi",
            $status,
            $verifier_id,
            $remarks,
            $verification_id
        );

        if (mysqli_stmt_execute($stmt)) {
            $success = "User verification updated successfully.";
        } else {
            $error = "Could not update verification.";
        }

        mysqli_stmt_close($stmt);
    }
}

$result = mysqli_query($conn, "
    SELECT nv.*, u.email, u.phone
    FROM nid_verifications nv
    JOIN users u ON nv.user_id = u.user_id
    ORDER BY nv.verification_id DESC
");
?>

<!DOCTYPE html>
<html lang="en">

<head>
<meta charset="UTF-8">
<title>Verify Users | BorrowBee</title>
<link rel="stylesheet" href="style.css">
</head>

<body class="app-body">

<div class="app-layout">

<?php include("verifier_sidebar.php"); ?>

<main class="main-content">

<header class="topbar">

<div>
<h1>Verify Users 👥</h1>
<p class="topbar-sub">
Approve or reject user NID verification.
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
<th>User ID</th>
<th>Email</th>
<th>Phone</th>
<th>NID</th>
<th>Status</th>
<th>Action</th>
</tr>

</thead>

<tbody>

<?php if (mysqli_num_rows($result) > 0): ?>

<?php while ($row = mysqli_fetch_assoc($result)): ?>

<tr>

<td>
#<?php echo htmlspecialchars($row["verification_id"]); ?>
</td>

<td>
<?php echo htmlspecialchars($row["user_id"]); ?>
</td>

<td>
<?php echo htmlspecialchars($row["email"]); ?>
</td>

<td>
<?php echo htmlspecialchars($row["phone"]); ?>
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

<?php if ($row["status"] == "pending"): ?>

<form method="post">

<input type="hidden"
       name="verification_id"
       value="<?php echo $row["verification_id"]; ?>">

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
<td colspan="7">No user verification records found.</td>
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