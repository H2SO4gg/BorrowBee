<?php
include("auth_admin.php");
include("db.php");

$current = "users";

$message = "";
$error = "";

// Delete User
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["delete_user_id"])) {

    $deleteUserId = (int)$_POST["delete_user_id"];

    if ($deleteUserId == $admin_id) {

        $error = "You cannot delete your own admin account.";

    } else {

        $stmt = mysqli_prepare(
            $conn,
            "DELETE FROM users WHERE user_id = ?"
        );

        mysqli_stmt_bind_param(
            $stmt,
            "i",
            $deleteUserId
        );

        if (mysqli_stmt_execute($stmt)) {

            $message = "User deleted successfully.";

        } else {

            $error = "Could not delete user.";

        }

        mysqli_stmt_close($stmt);
    }
}


// Search
$search = isset($_GET["search"]) ? trim($_GET["search"]) : "";

if ($search != "") {

    $sql = "
        SELECT 
            u.*,
            up.full_name
        FROM users u
        LEFT JOIN user_profiles up
        ON u.user_id = up.user_id

        WHERE 
            u.user_id LIKE ?
            OR u.email LIKE ?
            OR up.full_name LIKE ?

        ORDER BY u.user_id ASC
    ";

    $stmt = mysqli_prepare($conn, $sql);

    $like = "%" . $search . "%";

    mysqli_stmt_bind_param(
        $stmt,
        "sss",
        $like,
        $like,
        $like
    );

} else {

    $sql = "
        SELECT 
            u.*,
            up.full_name
        FROM users u
        LEFT JOIN user_profiles up
        ON u.user_id = up.user_id

        ORDER BY u.user_id ASC
    ";

    $stmt = mysqli_prepare($conn, $sql);
}

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$users = [];

while ($row = mysqli_fetch_assoc($result)) {
    $users[] = $row;
}

mysqli_stmt_close($stmt);


function getRoleName($role_id) {

    switch ($role_id) {

        case 1:
            return "Admin";

        case 2:
            return "Borrower";

        case 3:
            return "Owner";

        case 4:
            return "Verifier";

        default:
            return "Unknown";
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">

<title>Manage Users | BorrowBee</title>

<link rel="stylesheet" href="style.css">

</head>

<body class="app-body">

<div class="app-layout">

<?php include("admin_sidebar.php"); ?>


<main class="main-content">


<header class="topbar">

<div>

<h1>Manage Users 👥</h1>

<p class="topbar-sub">
View and manage all registered users.
</p>

</div>


<form method="get" class="search-bar search-bar-top">

<span class="icon">🔎</span>

<input
type="text"
name="search"
placeholder="Search user..."
value="<?php echo htmlspecialchars($search); ?>">

<button
type="submit"
class="btn-login btn-inline">

Search

</button>

</form>

</header>


<?php if ($message != ""): ?>

<div class="success-msg">

<?php echo htmlspecialchars($message); ?>

</div>

<?php endif; ?>


<?php if ($error != ""): ?>

<div class="error-msg">

<?php echo htmlspecialchars($error); ?>

</div>

<?php endif; ?>


<section class="panel">

<div class="table-responsive">

<table class="admin-table">

<thead>

<tr>

<th>User ID</th>

<th>Full Name</th>

<th>Email</th>

<th>Phone</th>

<th>Role</th>

<th>Created At</th>

<th>Action</th>

</tr>

</thead>


<tbody>


<?php foreach ($users as $user): ?>


<tr>

<td>

<?php echo htmlspecialchars($user["user_id"]); ?>

</td>


<td>

<?php echo htmlspecialchars(
$user["full_name"] ?? "Not Provided"
); ?>

</td>


<td>

<?php echo htmlspecialchars($user["email"]); ?>

</td>


<td>

<?php echo htmlspecialchars($user["phone"]); ?>

</td>


<td>

<span class="status-pill">

<?php echo getRoleName($user["role_id"]); ?>

</span>

</td>


<td>

<?php echo htmlspecialchars($user["created_at"]); ?>

</td>


<td>

<?php if ($user["user_id"] != $admin_id): ?>

<form method="post">

<input
type="hidden"
name="delete_user_id"
value="<?php echo $user["user_id"]; ?>">

<button
type="submit"
class="btn-danger"
onclick="return confirm('Are you sure you want to delete this user?');">

Delete

</button>

</form>

<?php else: ?>

<span>Current Admin</span>

<?php endif; ?>

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