<?php
include("auth_borrower.php");
include("db.php");

$error   = "";
$success = "";

$item_id = isset($_GET["item_id"]) ? (int)$_GET["item_id"] : 0;
if ($item_id <= 0) {
    header("Location: browse_items.php");
    exit();
}

$stmtItem = mysqli_prepare($conn, "SELECT * FROM items WHERE item_id = ?");
mysqli_stmt_bind_param($stmtItem, "i", $item_id);
mysqli_stmt_execute($stmtItem);
$resItem = mysqli_stmt_get_result($stmtItem);
$item = mysqli_fetch_assoc($resItem);
mysqli_stmt_close($stmtItem);

if (!$item) {
    header("Location: browse_items.php");
    exit();
}

$start_date = "";
$end_date   = "";
$message    = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $start_date = trim($_POST["start_date"]);
    $end_date   = trim($_POST["end_date"]);
    $message    = trim($_POST["message"]);
    $action     = $_POST["action"] ?? "request";

    if (empty($start_date) || empty($end_date)) {
        $error = "Please choose both a start and end date.";
    } elseif (strtotime($end_date) < strtotime($start_date)) {
        $error = "End date cannot be before the start date.";
    } elseif ($item["status"] !== "available") {
        $error = "Sorry, this item is no longer available.";
    } elseif ($action === "cart") {

        // Add to cart (or update dates if already in cart)
        $sql = "INSERT INTO cart (borrower_id, item_id, start_date, end_date, message)
                VALUES (?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE start_date = VALUES(start_date), end_date = VALUES(end_date), message = VALUES(message)";
        $stmt = mysqli_prepare($conn, $sql);
        if ($stmt === false) {
            $error = "SQL Error: " . mysqli_error($conn);
        } else {
            mysqli_stmt_bind_param($stmt, "iisss", $borrower_id, $item_id, $start_date, $end_date, $message);
            if (mysqli_stmt_execute($stmt)) {
                header("Location: view_cart.php?msg=" . urlencode("\"" . $item["item_name"] . "\" added to your cart."));
                exit();
            } else {
                $error = "Could not add to cart: " . mysqli_stmt_error($stmt);
            }
            mysqli_stmt_close($stmt);
        }

    } else {

        // Direct request (skips cart, goes straight to rental_requests)
        $sql = "INSERT INTO rental_requests (item_id, borrower_id, start_date, end_date, message, status, requested_at)
                VALUES (?, ?, ?, ?, ?, 'pending', NOW())";
        $stmt = mysqli_prepare($conn, $sql);
        if ($stmt === false) {
            $error = "SQL Error: " . mysqli_error($conn);
        } else {
            mysqli_stmt_bind_param($stmt, "iisss", $item_id, $borrower_id, $start_date, $end_date, $message);
            if (mysqli_stmt_execute($stmt)) {
                header("Location: my_requests.php?msg=" . urlencode("Request sent for \"" . $item["item_name"] . "\"!"));
                exit();
            } else {
                $error = "Could not submit request: " . mysqli_stmt_error($stmt);
            }
            mysqli_stmt_close($stmt);
        }
    }
}

$current = "browse";
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title><?php echo htmlspecialchars($item["item_name"]); ?> | BorrowBee</title>
<link rel="stylesheet" href="style.css">
</head>
<body class="app-body">
<div class="app-layout">
    <?php include("borrower_sidebar.php"); ?>

    <main class="main-content">
        <header class="topbar">
            <div>
                <h1><?php echo htmlspecialchars($item["item_name"]); ?></h1>
                <p class="topbar-sub"><a href="browse_items.php" class="panel-link">← Back to Browse</a></p>
            </div>
        </header>

        <?php if ($error): ?>
            <div class="error-msg"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <section class="panel">
            <div class="detail-layout">
                <div class="detail-pic">
                    <?php if (!empty($item["Image"])): ?>
                        <img src="<?php echo htmlspecialchars($item["Image"]); ?>" alt="<?php echo htmlspecialchars($item["item_name"]); ?>">
                    <?php else: ?>
                        <span>📦</span>
                    <?php endif; ?>
                </div>

                <table class="detail-table">
                    <tbody>
                        <tr>
                            <th>Item ID</th>
                            <td>#<?php echo htmlspecialchars($item["item_id"]); ?></td>
                        </tr>
                        <tr>
                            <th>Item Name</th>
                            <td><?php echo htmlspecialchars($item["item_name"]); ?></td>
                        </tr>
                        <tr>
                            <th>Description</th>
                            <td><?php echo nl2br(htmlspecialchars($item["description"])); ?></td>
                        </tr>
                        <tr>
                            <th>Rent Price</th>
                            <td>৳<?php echo htmlspecialchars(number_format($item["rental_price"], 2)); ?> / day</td>
                        </tr>
                        <tr>
                            <th>Security Deposit</th>
                            <td>৳<?php echo htmlspecialchars(number_format($item["security_deposit"], 2)); ?></td>
                        </tr>
                        <tr>
                            <th>Location</th>
                            <td><?php echo htmlspecialchars($item["location"]); ?></td>
                        </tr>
                        <tr>
                            <th>Rental Rules</th>
                            <td><?php echo !empty($item["rental_rules"]) ? nl2br(htmlspecialchars($item["rental_rules"])) : "—"; ?></td>
                        </tr>
                        <tr>
                            <th>Status</th>
                            <td>
                                <span class="status-pill status-<?php echo htmlspecialchars(strtolower($item["status"])); ?>">
                                    <?php echo htmlspecialchars(ucfirst($item["status"])); ?>
                                </span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <?php if ($item["status"] === "available"): ?>
        <section class="panel panel-narrow">
            <h2 class="panel-title-inline">Choose Your Dates</h2>
            <form method="post" action="item_detail.php?item_id=<?php echo (int)$item_id; ?>" class="stacked-form">

                <label>Start Date</label>
                <div class="input-group">
                    <span class="icon">📅</span>
                    <input type="date" name="start_date" value="<?php echo htmlspecialchars($start_date); ?>" required>
                </div>

                <label>End Date</label>
                <div class="input-group">
                    <span class="icon">📅</span>
                    <input type="date" name="end_date" value="<?php echo htmlspecialchars($end_date); ?>" required>
                </div>

                <label>Message to Owner (optional)</label>
                <div class="input-group textarea-group">
                    <span class="icon">💬</span>
                    <textarea name="message" rows="3" placeholder="e.g. I'll pick it up in the evening..."><?php echo htmlspecialchars($message); ?></textarea>
                </div>

                <div class="btn-row">
                    <button type="submit" name="action" value="request" class="btn-login">REQUEST NOW</button>
                    <button type="submit" name="action" value="cart" class="btn-secondary">ADD TO CART 🛒</button>
                </div>
            </form>
        </section>
        <?php endif; ?>
    </main>
</div>
</body>
</html>