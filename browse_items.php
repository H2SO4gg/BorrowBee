<?php
include("auth_borrower.php");
include("db.php");

$search = isset($_GET["search"]) ? trim($_GET["search"]) : "";

if ($search !== "") {
    // Search by item name OR exact item_id (covers "search by name or ID/serial number")
    if (ctype_digit($search)) {
        $sql = "SELECT * FROM items WHERE status = 'available' AND (item_name LIKE ? OR item_id = ?) ORDER BY created_at DESC";
        $stmt = mysqli_prepare($conn, $sql);
        $like = "%" . $search . "%";
        $idVal = (int)$search;
        mysqli_stmt_bind_param($stmt, "si", $like, $idVal);
    } else {
        $sql = "SELECT * FROM items WHERE status = 'available' AND item_name LIKE ? ORDER BY created_at DESC";
        $stmt = mysqli_prepare($conn, $sql);
        $like = "%" . $search . "%";
        mysqli_stmt_bind_param($stmt, "s", $like);
    }
} else {
    $sql = "SELECT * FROM items WHERE status = 'available' ORDER BY created_at DESC";
    $stmt = mysqli_prepare($conn, $sql);
}
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$items = [];
while ($row = mysqli_fetch_assoc($result)) { $items[] = $row; }
mysqli_stmt_close($stmt);

$current = "browse";
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Browse Items | BorrowBee</title>
<link rel="stylesheet" href="style.css">
</head>
<body class="app-body">
<div class="app-layout">
    <?php include("borrower_sidebar.php"); ?>

    <main class="main-content">
        <header class="topbar">
            <div>
                <h1>Browse Items</h1>
                <p class="topbar-sub">Find something to borrow from your community.</p>
            </div>
            <form method="get" action="" class="search-bar search-bar-top">
                <span class="icon search-icon">🔎</span>
                <input type="text" name="search" placeholder="Search by name or item ID..." value="<?php echo htmlspecialchars($search); ?>">
                <button type="submit" class="btn-login btn-inline">Search</button>
                <?php if ($search !== ""): ?>
                    <a href="browse_items.php" class="clear-search">Clear</a>
                <?php endif; ?>
            </form>
        </header>

        <?php if (isset($_GET["msg"])): ?>
            <div class="success-msg"><?php echo htmlspecialchars($_GET["msg"]); ?></div>
        <?php endif; ?>

        <?php if (count($items) === 0): ?>
            <div class="empty-state">
                <p>No available items found<?php echo $search !== "" ? " for \"" . htmlspecialchars($search) . "\"" : ""; ?>.</p>
            </div>
        <?php else: ?>
            <section class="item-grid">
                <?php foreach ($items as $item): ?>
                    <a href="item_detail.php?item_id=<?php echo urlencode($item["item_id"]); ?>" class="item-card item-card-link">
                        <div class="item-card-pic">
                            <?php if (!empty($item["Image"])): ?>
                                <img src="<?php echo htmlspecialchars($item["Image"]); ?>" alt="<?php echo htmlspecialchars($item["item_name"]); ?>">
                            <?php else: ?>
                                <span>📦</span>
                            <?php endif; ?>
                        </div>
                        <div class="item-card-body">
                            <div class="item-card-top">
                                <h3><?php echo htmlspecialchars($item["item_name"]); ?></h3>
                                <span class="status-pill status-available">Available</span>
                            </div>
                            <p class="item-id-tag">ID #<?php echo htmlspecialchars($item["item_id"]); ?></p>
                            <div class="item-meta">
                                <span>💰 ৳<?php echo htmlspecialchars(number_format($item["rental_price"], 2)); ?> / day</span>
                                <span>🔒 ৳<?php echo htmlspecialchars(number_format($item["security_deposit"], 2)); ?> deposit</span>
                            </div>
                            <div class="item-meta">
                                <span>📍 <?php echo htmlspecialchars($item["location"]); ?></span>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </section>
        <?php endif; ?>
    </main>
</div>
</body>
</html>