<?php
include("auth_borrower.php");
include("db.php");

$request_id = isset($_GET["request_id"]) ? (int)$_GET["request_id"] : 0;
$method     = isset($_GET["method"]) ? $_GET["method"] : "";
$amount     = isset($_GET["amount"]) ? (float)$_GET["amount"] : 0;

$validMethods = ["bkash", "nagad", "bank"];
if ($request_id <= 0 || !in_array($method, $validMethods) || $amount <= 0) {
    header("Location: my_requests.php");
    exit();
}

// Re-verify the request belongs to this borrower and is approved + unpaid
$stmt = mysqli_prepare($conn, "
    SELECT rr.*, i.item_name
    FROM rental_requests rr
    JOIN items i ON i.item_id = rr.item_id
    WHERE rr.request_id = ? AND rr.borrower_id = ? AND rr.status = 'approved'
");
mysqli_stmt_bind_param($stmt, "ii", $request_id, $borrower_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$req = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$req) {
    header("Location: my_requests.php");
    exit();
}

$stmtPaid = mysqli_prepare($conn, "SELECT * FROM payments WHERE booking_id = ? AND status = 'completed' LIMIT 1");
mysqli_stmt_bind_param($stmtPaid, "i", $request_id);
mysqli_stmt_execute($stmtPaid);
$resPaid = mysqli_stmt_get_result($stmtPaid);
if (mysqli_fetch_assoc($resPaid)) {
    header("Location: my_requests.php?msg=" . urlencode("This request is already paid."));
    exit();
}
mysqli_stmt_close($stmtPaid);

// Handle final confirmation (after fake OTP/PIN steps complete client-side)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["confirm_payment"])) {

    $transactionId = strtoupper($method) . date("YmdHis") . mt_rand(100, 999);

    $sql = "INSERT INTO payments (booking_id, payer_id, amount, payment_type, payment_method, transaction_id, status, paid_at)
            VALUES (?, ?, ?, 'rental', ?, ?, 'completed', NOW())";
    $stmtIns = mysqli_prepare($conn, $sql);

    if ($stmtIns === false) {
        $insertError = "SQL Error: " . mysqli_error($conn);
    } else {
        mysqli_stmt_bind_param($stmtIns, "iidss", $request_id, $borrower_id, $amount, $method, $transactionId);

        if (mysqli_stmt_execute($stmtIns)) {
            mysqli_stmt_close($stmtIns);
            header("Location: payment_success.php?request_id=" . $request_id . "&txn=" . urlencode($transactionId));
            exit();
        } else {
            $insertError = "Could not record payment: " . mysqli_stmt_error($stmtIns);
        }
        mysqli_stmt_close($stmtIns);
    }
}

$methodLabels = ["bkash" => "bKash", "nagad" => "Nagad", "bank" => "Bank Transfer"];
$methodLabel  = $methodLabels[$method];
$current = "requests";
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title><?php echo $methodLabel; ?> Payment | BorrowBee</title>
<link rel="stylesheet" href="style.css">
</head>
<body class="app-body">
<div class="app-layout">
    <?php include("borrower_sidebar.php"); ?>

    <main class="main-content">
        <header class="topbar">
            <div>
                <h1><?php echo $methodLabel; ?> Payment</h1>
                <p class="topbar-sub"><a href="payment.php?request_id=<?php echo $request_id; ?>" class="panel-link">← Change payment method</a></p>
            </div>
        </header>

        <?php if (isset($insertError)): ?>
            <div class="error-msg"><?php echo htmlspecialchars($insertError); ?></div>
        <?php endif; ?>

        <section class="panel panel-narrow gateway-panel gateway-<?php echo htmlspecialchars($method); ?>">

            <div class="gateway-header">
                <div class="gateway-logo"><?php echo $method === "bank" ? "🏦" : $methodLabel; ?></div>
                <div class="gateway-amount">৳<?php echo number_format($amount, 2); ?></div>
                <div class="gateway-sub">Paying for <?php echo htmlspecialchars($req["item_name"]); ?></div>
            </div>

            <!-- STEP 1: Phone / Account number -->
            <div class="gateway-step" id="stepPhone">
                <label><?php echo $method === "bank" ? "Account Number" : "Mobile Number"; ?></label>
                <div class="input-group">
                    <span class="icon"><?php echo $method === "bank" ? "🏦" : "📱"; ?></span>
                    <input type="text" id="phoneInput" placeholder="<?php echo $method === "bank" ? "e.g. 2039485712" : "e.g. 01712345678"; ?>" maxlength="20">
                </div>
                <button type="button" class="btn-login" onclick="goToStep('stepOtp')">CONTINUE</button>
            </div>

            <!-- STEP 2: OTP -->
            <div class="gateway-step" id="stepOtp" style="display:none;">
                <p class="gateway-info">An OTP has been sent to your <?php echo $method === "bank" ? "registered mobile number" : "mobile number"; ?> (demo: enter any 6 digits).</p>
                <label>Enter OTP</label>
                <div class="input-group">
                    <span class="icon">🔢</span>
                    <input type="text" id="otpInput" placeholder="------" maxlength="6" inputmode="numeric">
                </div>
                <button type="button" class="btn-login" onclick="verifyOtp()">VERIFY OTP</button>
            </div>

            <!-- STEP 3: PIN -->
            <div class="gateway-step" id="stepPin" style="display:none;">
                <label><?php echo $method === "bank" ? "Enter Card/Transaction PIN" : "Enter " . $methodLabel . " PIN"; ?></label>
                <div class="input-group">
                    <span class="icon">🔒</span>
                    <input type="password" id="pinInput" placeholder="----" maxlength="6" inputmode="numeric">
                </div>
                <button type="button" class="btn-login" onclick="submitPin()">CONFIRM PAYMENT</button>
            </div>

            <!-- STEP 4: Processing spinner -->
            <div class="gateway-step gateway-processing" id="stepProcessing" style="display:none;">
                <div class="spinner"></div>
                <p>Processing your payment...</p>
            </div>

            <form method="post" action="payment_process.php?request_id=<?php echo $request_id; ?>&method=<?php echo $method; ?>&amount=<?php echo $amount; ?>" id="confirmForm">
                <input type="hidden" name="confirm_payment" value="1">
            </form>

            <p class="sandbox-note">⚠️ Demo simulation only — no real transaction occurs.</p>
        </section>
    </main>
</div>

<script>
function goToStep(stepId) {
    var phone = document.getElementById('phoneInput').value.trim();
    if (phone.length < 6) {
        alert('Please enter a valid number.');
        return;
    }
    document.getElementById('stepPhone').style.display = 'none';
    document.getElementById(stepId).style.display = 'block';
}

function verifyOtp() {
    var otp = document.getElementById('otpInput').value.trim();
    if (otp.length !== 6 || !/^\d+$/.test(otp)) {
        alert('Enter a 6-digit OTP (any digits work in this demo).');
        return;
    }
    document.getElementById('stepOtp').style.display = 'none';
    document.getElementById('stepPin').style.display = 'block';
}

function submitPin() {
    var pin = document.getElementById('pinInput').value.trim();
    if (pin.length < 4) {
        alert('Enter your PIN (any digits work in this demo).');
        return;
    }
    document.getElementById('stepPin').style.display = 'none';
    document.getElementById('stepProcessing').style.display = 'block';

    setTimeout(function() {
        document.getElementById('confirmForm').submit();
    }, 2200);
}
</script>
</body>
</html>