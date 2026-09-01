<?php
include("auth_borrower.php");
include("db.php");

$notice = "";
$error  = "";

// Remove single item from cart
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["remove_cart_id"])) {
    $removeId = (int)$_POST["remove_cart_id"];
    $stmtRemove = mysqli_prepare($conn, "DELETE FROM cart WHERE cart_id = ? AND borrower_id = ?");
    mysqli_stmt_bind_param($stmtRemove, "ii", $removeId, $borrower_id);
    mysqli_stmt_execute($stmtRemove);
    mysqli_stmt_close($stmtRemove);
    $notice = "Item removed from cart.";
}

// Checkout: turn every cart row into a rental_request, then empty the cart
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["checkout"])) {

    $stmtCart = mysqli_prepare($conn, "
        SELECT c.*, i.item_name, i.status AS item_status, i.owner_id
        FROM cart c
        JOIN items i ON i.item_id = c.item_id
        WHERE c.borrower_id = ?
    ");
    mysqli_stmt_bind_param($stmtCart, "i", $borrower_id);
    mysqli_stmt_execute($stmtCart);
    $resCart = mysqli_stmt_get_result($stmtCart);

    $cartRows = [];
    while ($row = mysqli_fetch_assoc($resCart)) { $cartRows[] = $row; }
    mysqli_stmt_close($stmtCart);

    if (count($cartRows) === 0) {
        $error = "Your cart is empty.";
    } else {

        $skipped = [];
        $placed  = 0;

        mysqli_begin_transaction($conn);

        foreach ($cartRows as $row) {

            if ($row["item_status"] !== "available") {
                $skipped[] = $row["item_name"];
                continue;
            }

            $sqlReq = "INSERT INTO rental_requests (item_id, borrower_id, start_date, end_date, message, status, requested_at)
                       VALUES (?, ?, ?, ?, ?, 'pending', NOW())";
            $stmtReq = mysqli_prepare($conn, $sqlReq);
            mysqli_stmt_bind_param($stmtReq, "iisss", $row["item_id"], $borrower_id, $row["start_date"], $row["end_date"], $row["message"]);
            mysqli_stmt_execute($stmtReq);
            mysqli_stmt_close($stmtReq);
            $placed++;

            $stmtDel = mysqli_prepare($conn, "DELETE FROM cart WHERE cart_id = ?");
            mysqli_stmt_bind_param($stmtDel, "i", $row["cart_id"]);
            mysqli_stmt_execute($stmtDel);
            mysqli_stmt_close($stmtDel);
        }

        mysqli_commit($conn);

        if ($placed > 0) {
            $msg = $placed . " request(s) submitted successfully!";
            if (count($skipped) > 0) {
                $msg .= " Skipped (no longer available): " . implode(", ", $skipped);
            }
            header("Location: my_requests.php?msg=" . urlencode($msg));
            exit();
        } else {
            $error = "None of the items in your cart are available anymore.";
        }
    }
}

// Load current cart
$stmtList = mysqli_prepare($conn, "
    SELECT c.cart_id, c.start_date, c.end_date, c.message,
           i.item_id, i.item_name, i.rental_price, i.security_deposit, i.location, i.status
    FROM cart c
    JOIN items i ON i.item_id = c.item_id
    WHERE c.borrower_id = ?
    ORDER BY c.added_at DESC
");
mysqli_stmt_bind_param($stmtList, "i", $borrower_id);
mysqli_stmt_execute($stmtList);
$resList = mysqli_stmt_get_result($stmtList);
$cartItems = [];
$total = 0;
while ($row = mysqli_fetch_assoc($resList)) {
    $days = max(1, (strtotime($row["end_date"]) - strtotime($row["start_date"])) / 86400);
    $row["days"]     = $days;
    $row["lineCost"] = $days * $row["rental_price"];
    $total += $row["lineCost"];
    $cartItems[] = $row;
}
mysqli_stmt_close($stmtList);

$current = "cart";
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>My Cart | BorrowBee</title>
<link rel="stylesheet" href="style.css">
</head>
<body class="app-body">
<div class="app-layout">
    <?php include("borrower_sidebar.php"); ?>

    <main class="main-content">
        <header class="topbar">
            <div>
                <h1>My Cart</h1>
                <p class="topbar-sub">Review your selected items before requesting.</p>
            </div>
        </header>

        <?php if (isset($_GET["msg"])): ?>
            <div class="success-msg"><?php echo htmlspecialchars($_GET["msg"]); ?></div>
        <?php endif; ?>
        <?php if ($notice): ?>
            <div class="success-msg"><?php echo htmlspecialchars($notice); ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="error-msg"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if (count($cartItems) === 0): ?>
            <div class="empty-state">
                <p>Your cart is empty.</p>
                <a href="browse_items.php" class="btn-login btn-inline">Browse Items</a>
            </div>
        <?php else: ?>
            <section class="panel">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th>Start</th>
                            <th>End</th>
                            <th>Days</th>
                            <th>Price/day</th>
                            <th>Subtotal</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cartItems as $c): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($c["item_name"]); ?></td>
                                <td><?php echo htmlspecialchars($c["start_date"]); ?></td>
                                <td><?php echo htmlspecialchars($c["end_date"]); ?></td>
                                <td><?php echo (int)$c["days"]; ?></td>
                                <td>৳<?php echo htmlspecialchars(number_format($c["rental_price"], 2)); ?></td>
                                <td>৳<?php echo htmlspecialchars(number_format($c["lineCost"], 2)); ?></td>
                                <td>
                                    <?php if ($c["status"] === "available"): ?>
                                        <span class="status-pill status-available">Available</span>
                                    <?php else: ?>
                                        <span class="status-pill status-rejected">Unavailable</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <form method="post" action="view_cart.php" onsubmit="return confirm('Remove this item from cart?');">
                                        <input type="hidden" name="remove_cart_id" value="<?php echo (int)$c["cart_id"]; ?>">
                                        <button type="submit" class="btn-cancel">Remove</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <div class="cart-summary">
                    <span>Estimated Total</span>
                    <strong>৳<?php echo number_format($total, 2); ?></strong>
                </div>

                <form method="post" action="view_cart.php" class="checkout-form">
                    <button type="submit" name="checkout" value="1" class="btn-login">
                        CHECKOUT — SUBMIT ALL REQUESTS
                    </button>
                </form>
            </section>
        <?php endif; ?>
    </main>
</div>
</body>
</html>