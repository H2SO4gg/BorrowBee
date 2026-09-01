<?php
include("auth_admin.php");
include("db.php");

$current = "items";

$message = "";
$error = "";


if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Change Status
    if (isset($_POST["item_id"]) && isset($_POST["new_status"])) {

        $item_id = (int)$_POST["item_id"];
        $new_status = $_POST["new_status"];

        $allowed = ["available", "unavailable"];

        if (in_array($new_status, $allowed)) {

            $stmt = mysqli_prepare(
                $conn,
                "UPDATE items SET status = ? WHERE item_id = ?"
            );

            mysqli_stmt_bind_param(
                $stmt,
                "si",
                $new_status,
                $item_id
            );

            mysqli_stmt_execute($stmt);

            mysqli_stmt_close($stmt);

            $message = "Item status updated successfully.";
        }
    }


    // Delete Item
    if (isset($_POST["delete_item_id"])) {

        $deleteItemId = (int)$_POST["delete_item_id"];

        $stmt = mysqli_prepare(
            $conn,
            "DELETE FROM items WHERE item_id = ?"
        );

        mysqli_stmt_bind_param(
            $stmt,
            "i",
            $deleteItemId
        );

        if (mysqli_stmt_execute($stmt)) {

            $message = "Item deleted successfully.";

        } else {

            $error = "Could not delete item.";
        }

        mysqli_stmt_close($stmt);
    }

}


// Search
$search = isset($_GET["search"]) ? trim($_GET["search"]) : "";


if ($search != "") {

    $sql = "
        SELECT 
            i.*,
            u.email
        FROM items i
        LEFT JOIN users u
        ON i.owner_id = u.user_id

        WHERE
            i.item_name LIKE ?
            OR i.item_id LIKE ?

        ORDER BY i.created_at DESC
    ";

    $stmt = mysqli_prepare($conn, $sql);

    $like = "%" . $search . "%";

    mysqli_stmt_bind_param(
        $stmt,
        "ss",
        $like,
        $like
    );

} else {

    $sql = "
        SELECT 
            i.*,
            u.email
        FROM items i
        LEFT JOIN users u
        ON i.owner_id = u.user_id

        ORDER BY i.created_at DESC
    ";

    $stmt = mysqli_prepare($conn, $sql);
}


mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$items = [];

while ($row = mysqli_fetch_assoc($result)) {

    $items[] = $row;

}

mysqli_stmt_close($stmt);

?>


<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<title>Manage Items | BorrowBee</title>

<link rel="stylesheet" href="style.css">

</head>


<body class="app-body">


<div class="app-layout">


<?php include("admin_sidebar.php"); ?>


<main class="main-content">


<header class="topbar">

<div>

<h1>Manage Items 📦</h1>

<p class="topbar-sub">

View and manage all rental items.

</p>

</div>


<form method="get" class="search-bar search-bar-top">

<span class="icon">🔎</span>

<input
type="text"
name="search"
placeholder="Search item..."
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

<th>ID</th>

<th>Image</th>

<th>Item Name</th>

<th>Owner</th>

<th>Price</th>

<th>Deposit</th>

<th>Location</th>

<th>Status</th>

<th>Action</th>

</tr>

</thead>


<tbody>


<?php foreach ($items as $item): ?>


<tr>


<td>

#<?php echo htmlspecialchars($item["item_id"]); ?>

</td>


<td>

<?php if (!empty($item["Image"])): ?>

<img
src="<?php echo htmlspecialchars($item["Image"]); ?>"
style="width:60px;height:60px;object-fit:cover;border-radius:8px;">

<?php else: ?>

📦

<?php endif; ?>

</td>


<td>

<strong>

<?php echo htmlspecialchars($item["item_name"]); ?>

</strong>

<br>

<small>

<?php echo htmlspecialchars($item["description"]); ?>

</small>

</td>


<td>

<?php echo htmlspecialchars($item["owner_id"]); ?>

</td>


<td>

৳<?php echo number_format($item["rental_price"], 2); ?>

</td>


<td>

৳<?php echo number_format($item["security_deposit"], 2); ?>

</td>


<td>

<?php echo htmlspecialchars($item["location"]); ?>

</td>


<td>


<form method="post">


<input
type="hidden"
name="item_id"
value="<?php echo $item["item_id"]; ?>">


<select name="new_status">

<option
value="available"
<?php echo ($item["status"] == "available") ? "selected" : ""; ?>>

Available

</option>


<option
value="unavailable"
<?php echo ($item["status"] == "unavailable") ? "selected" : ""; ?>>

Unavailable

</option>

</select>


<button
type="submit"
class="btn-small">

Update

</button>


</form>


</td>


<td>


<form method="post">

<input
type="hidden"
name="delete_item_id"
value="<?php echo $item["item_id"]; ?>">


<button
type="submit"
class="btn-danger"
onclick="return confirm('Delete this item?');">

Delete

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