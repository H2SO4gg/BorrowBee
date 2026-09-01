<?php
include("auth_borrower.php");
include("db.php");

// Full name from user_profiles, if present
$fullName = "Borrower";
$stmtName = mysqli_prepare($conn, "SELECT full_name FROM user_profiles WHERE user_id = ?");
mysqli_stmt_bind_param($stmtName, "i", $borrower_id);
mysqli_stmt_execute($stmtName);
$resName = mysqli_stmt_get_result($stmtName);
if ($rowName = mysqli_fetch_assoc($resName)) {
    if (!empty($rowName["full_name"])) { $fullName = $rowName["full_name"]; }
}
mysqli_stmt_close($stmtName);

$error   = "";
$success = "";

// Quick add-to-cart straight from the dashboard row (uses today/tomorrow as placeholder dates;
// the borrower can still adjust exact dates later from the Cart or Item Detail page)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["quick_add_item_id"])) {

    $agreed = isset($_POST["agree_rules"]) && $_POST["agree_rules"] === "1";

    if (!$agreed) {
        $error = "You must agree to the item's rules before adding it to your cart.";
    } else {

        $item_id = (int)$_POST["quick_add_item_id"];

        $stmtItem = mysqli_prepare($conn, "SELECT * FROM items WHERE item_id = ?");
        mysqli_stmt_bind_param($stmtItem, "i", $item_id);
        mysqli_stmt_execute($stmtItem);
        $resItem = mysqli_stmt_get_result($stmtItem);
        $itemRow = mysqli_fetch_assoc($resItem);
        mysqli_stmt_close($stmtItem);

        if (!$itemRow) {
            $error = "Item not found.";
        } elseif ($itemRow["status"] !== "available") {
            $error = "\"" . $itemRow["item_name"] . "\" is no longer available.";
        } else {

            $start_date = date("Y-m-d");
            $end_date   = date("Y-m-d", strtotime("+1 day"));
            $message    = "";

            $sql = "INSERT INTO cart (borrower_id, item_id, start_date, end_date, message)
                    VALUES (?, ?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE start_date = VALUES(start_date), end_date = VALUES(end_date)";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "iisss", $borrower_id, $item_id, $start_date, $end_date, $message);

            if (mysqli_stmt_execute($stmt)) {
                $success = "\"" . $itemRow["item_name"] . "\" added to your cart. Adjust dates any time from View Cart.";
            } else {
                $error = "Could not add to cart: " . mysqli_stmt_error($stmt);
            }
            mysqli_stmt_close($stmt);
        }
    }
}

// Search (by name or item ID)
$search = isset($_GET["search"]) ? trim($_GET["search"]) : "";

if ($search !== "") {
    if (ctype_digit($search)) {
        $sql = "SELECT * FROM items WHERE status = 'available' AND (item_name LIKE ? OR item_id = ?) ORDER BY item_id ASC";
        $stmt = mysqli_prepare($conn, $sql);
        $like = "%" . $search . "%";
        $idVal = (int)$search;
        mysqli_stmt_bind_param($stmt, "si", $like, $idVal);
    } else {
        $sql = "SELECT * FROM items WHERE status = 'available' AND item_name LIKE ? ORDER BY item_id ASC";
        $stmt = mysqli_prepare($conn, $sql);
        $like = "%" . $search . "%";
        mysqli_stmt_bind_param($stmt, "s", $like);
    }
} else {
    $sql = "SELECT * FROM items WHERE status = 'available' ORDER BY item_id ASC";
    $stmt = mysqli_prepare($conn, $sql);
}
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$items = [];
while ($row = mysqli_fetch_assoc($result)) { $items[] = $row; }
mysqli_stmt_close($stmt);

$current = "dashboard";
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Dashboard | BorrowBee</title>
<link rel="stylesheet" href="style.css">
</head>
<body class="app-body">
<div class="app-layout">
    <?php include("borrower_sidebar.php"); ?>

    <main class="main-content">
        <header class="topbar">
            <div>
                <h1>Welcome back, <?php echo htmlspecialchars($fullName); ?> 👋</h1>
                <p class="topbar-sub">Browse available items below and add what you need to your cart.</p>
            </div>
            <form method="get" action="" class="search-bar search-bar-top">
                <span class="icon search-icon">🔎</span>
                <input type="text" name="search" placeholder="Search by name or item ID..." value="<?php echo htmlspecialchars($search); ?>">
                <button type="submit" class="btn-login btn-inline">Search</button>
                <?php if ($search !== ""): ?>
                    <a href="borrower_dashboard.php" class="clear-search">Clear</a>
                <?php endif; ?>
            </form>
        </header>

        <?php if ($error): ?>
            <div class="error-msg"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="success-msg"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <?php if (count($items) === 0): ?>
            <div class="empty-state">
                <p>No available items found<?php echo $search !== "" ? " for \"" . htmlspecialchars($search) . "\"" : ""; ?>.</p>
            </div>
        <?php else: ?>
            <section class="panel item-scroll-panel">
                <div class="item-row-list">
                    <?php foreach ($items as $item):
                        $rowFormId = "agreeForm" . $item["item_id"];
                    ?>
                        <div class="item-row">
                            <div class="item-row-id">#<?php echo htmlspecialchars($item["item_id"]); ?></div>

                            <div class="item-row-pic">
                                <?php if (!empty($item["Image"])): ?>
                                    <img src="<?php echo htmlspecialchars($item["Image"]); ?>" alt="<?php echo htmlspecialchars($item["item_name"]); ?>">
                                <?php else: ?>
                                    <span>📦</span>
                                <?php endif; ?>
                            </div>

                            <div class="item-row-name">
                                <a href="item_detail.php?item_id=<?php echo urlencode($item["item_id"]); ?>">
                                    <?php echo htmlspecialchars($item["item_name"]); ?>
                                </a>
                                <span class="status-pill status-available">Available</span>
                            </div>

                            <div class="item-row-desc">
                                <?php echo htmlspecialchars($item["description"]); ?>
                            </div>

                            <div class="item-row-price">
                                ৳<?php echo htmlspecialchars(number_format($item["rental_price"], 2)); ?>/day
                                <span class="item-row-deposit">Deposit ৳<?php echo htmlspecialchars(number_format($item["security_deposit"], 2)); ?></span>
                            </div>

                            <form method="post" action="borrower_dashboard.php<?php echo $search !== '' ? '?search=' . urlencode($search) : ''; ?>" class="item-row-rules" id="<?php echo $rowFormId; ?>">
                                <input type="hidden" name="quick_add_item_id" value="<?php echo (int)$item["item_id"]; ?>">
                                <p class="rules-text">
                                    <?php echo !empty($item["rental_rules"]) ? htmlspecialchars($item["rental_rules"]) : "No special rules for this item."; ?>
                                </p>
                                <label class="agree-checkbox">
                                    <input type="checkbox" name="agree_rules" value="1">
                                    I agree to these rules
                                </label>
                                <button type="submit" class="btn-login btn-inline btn-row-add">Add to Cart 🛒</button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>
    </main>
</div>
</body>
</html>