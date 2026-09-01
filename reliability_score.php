<?php
include("auth_borrower.php");
include("db.php");

// ---------- Recalculate this borrower's reliability score from their request history ----------

// Completed on-time: status = 'completed' AND (returned_at IS NULL OR DATE(returned_at) <= end_date)
// Comparing DATE(returned_at) instead of the raw timestamp means returning any time up to
// and including the due date counts as on-time — not just before midnight of that day.
$stmtOnTime = mysqli_prepare($conn, "
    SELECT COUNT(*) AS c FROM rental_requests
    WHERE borrower_id = ? AND status = 'completed'
    AND (returned_at IS NULL OR DATE(returned_at) <= end_date)
");
mysqli_stmt_bind_param($stmtOnTime, "i", $borrower_id);
mysqli_stmt_execute($stmtOnTime);
$resOnTime = mysqli_stmt_get_result($stmtOnTime);
$onTimeCount = (int)mysqli_fetch_assoc($resOnTime)["c"];
mysqli_stmt_close($stmtOnTime);

// Late returns: status = 'completed' AND DATE(returned_at) > end_date
$stmtLate = mysqli_prepare($conn, "
    SELECT COUNT(*) AS c FROM rental_requests
    WHERE borrower_id = ? AND status = 'completed'
    AND returned_at IS NOT NULL AND DATE(returned_at) > end_date
");
mysqli_stmt_bind_param($stmtLate, "i", $borrower_id);
mysqli_stmt_execute($stmtLate);
$resLate = mysqli_stmt_get_result($stmtLate);
$lateCount = (int)mysqli_fetch_assoc($resLate)["c"];
mysqli_stmt_close($stmtLate);

// Cancelled AFTER approval: we only have one 'cancelled' status and can't distinguish
// pre/post approval retroactively, so cancellations here means status = 'cancelled'
// (i.e. the borrower cancelled a request they'd made).
$stmtCancel = mysqli_prepare($conn, "
    SELECT COUNT(*) AS c FROM rental_requests
    WHERE borrower_id = ? AND status = 'cancelled'
");
mysqli_stmt_bind_param($stmtCancel, "i", $borrower_id);
mysqli_stmt_execute($stmtCancel);
$resCancel = mysqli_stmt_get_result($stmtCancel);
$cancelCount = (int)mysqli_fetch_assoc($resCancel)["c"];
mysqli_stmt_close($stmtCancel);

// Disputes: count of damage_reports filed against this borrower's requests, if that table is linked.
// If your damage_reports table doesn't yet have a borrower-facing link, this safely returns 0.
$disputeCount = 0;
$checkTable = mysqli_query($conn, "SHOW TABLES LIKE 'damage_reports'");
if ($checkTable && mysqli_num_rows($checkTable) > 0) {
    $checkCol = mysqli_query($conn, "SHOW COLUMNS FROM damage_reports LIKE 'request_id'");
    if ($checkCol && mysqli_num_rows($checkCol) > 0) {
        $stmtDispute = mysqli_prepare($conn, "
            SELECT COUNT(*) AS c FROM damage_reports dr
            JOIN rental_requests rr ON rr.request_id = dr.request_id
            WHERE rr.borrower_id = ?
        ");
        mysqli_stmt_bind_param($stmtDispute, "i", $borrower_id);
        mysqli_stmt_execute($stmtDispute);
        $resDispute = mysqli_stmt_get_result($stmtDispute);
        $disputeCount = (int)mysqli_fetch_assoc($resDispute)["c"];
        mysqli_stmt_close($stmtDispute);
    }
}

// Apply the formula: start at 100, -10 per late return, -20 per cancellation.
// On-time completions do NOT add bonus points — they simply cost nothing,
// so a borrower with a perfect record stays at 100.
$calculatedScore = 100 - ($lateCount * 10) - ($cancelCount * 20);
$calculatedScore = max(0, min(100, $calculatedScore)); // clamp to 0-100

// Upsert into reliability_scores
$stmtCheck = mysqli_prepare($conn, "SELECT score_id FROM reliability_scores WHERE user_id = ?");
mysqli_stmt_bind_param($stmtCheck, "i", $borrower_id);
mysqli_stmt_execute($stmtCheck);
$resCheck = mysqli_stmt_get_result($stmtCheck);
$existing = mysqli_fetch_assoc($resCheck);
mysqli_stmt_close($stmtCheck);

if ($existing) {
    $stmtUpdate = mysqli_prepare($conn, "
        UPDATE reliability_scores
        SET score = ?, successful_rentals = ?, late_returns = ?, cancellations = ?, disputes = ?, updated_at = NOW()
        WHERE user_id = ?
    ");
    mysqli_stmt_bind_param($stmtUpdate, "iiiiii", $calculatedScore, $onTimeCount, $lateCount, $cancelCount, $disputeCount, $borrower_id);
    mysqli_stmt_execute($stmtUpdate);
    mysqli_stmt_close($stmtUpdate);
} else {
    $stmtInsert = mysqli_prepare($conn, "
        INSERT INTO reliability_scores (user_id, score, successful_rentals, late_returns, cancellations, disputes, updated_at)
        VALUES (?, ?, ?, ?, ?, ?, NOW())
    ");
    mysqli_stmt_bind_param($stmtInsert, "iiiiii", $borrower_id, $calculatedScore, $onTimeCount, $lateCount, $cancelCount, $disputeCount);
    mysqli_stmt_execute($stmtInsert);
    mysqli_stmt_close($stmtInsert);
}

// Tier label based on score
if ($calculatedScore >= 90) {
    $tierLabel = "Excellent"; $tierClass = "tier-excellent";
} elseif ($calculatedScore >= 70) {
    $tierLabel = "Good"; $tierClass = "tier-good";
} elseif ($calculatedScore >= 50) {
    $tierLabel = "Fair"; $tierClass = "tier-fair";
} else {
    $tierLabel = "Needs Improvement"; $tierClass = "tier-poor";
}

$current = "reliability";
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>My Reliability Score | BorrowBee</title>
<link rel="stylesheet" href="style.css">
</head>
<body class="app-body">
<div class="app-layout">
    <?php include("borrower_sidebar.php"); ?>

    <main class="main-content">
        <header class="topbar">
            <div>
                <h1>My Reliability Score</h1>
                <p class="topbar-sub">See how you look to item owners based on your borrowing history.</p>
            </div>
        </header>

        <section class="panel panel-narrow score-panel">
            <div class="score-ring-wrap">
                <svg class="score-ring" viewBox="0 0 120 120">
                    <circle cx="60" cy="60" r="52" class="score-ring-bg"></circle>
                    <circle cx="60" cy="60" r="52" class="score-ring-fill <?php echo $tierClass; ?>"
                        style="stroke-dasharray: <?php echo round(2 * M_PI * 52); ?>; stroke-dashoffset: <?php echo round(2 * M_PI * 52 * (1 - $calculatedScore / 100)); ?>;">
                    </circle>
                </svg>
                <div class="score-ring-value">
                    <div class="score-number"><?php echo $calculatedScore; ?></div>
                    <div class="score-out-of">/ 100</div>
                </div>
            </div>
            <div class="score-tier-badge <?php echo $tierClass; ?>"><?php echo $tierLabel; ?></div>
        </section>

        <section class="panel panel-narrow">
            <h2 class="panel-title-inline">Score Breakdown</h2>
            <table class="data-table">
                <tbody>
                    <tr>
                        <td>✅ On-time completed rentals</td>
                        <td class="score-neutral"><?php echo $onTimeCount; ?> (no penalty)</td>
                    </tr>
                    <tr>
                        <td>⏰ Late returns</td>
                        <td class="score-negative">-<?php echo $lateCount * 10; ?> (<?php echo $lateCount; ?> × 10)</td>
                    </tr>
                    <tr>
                        <td>❌ Cancelled requests</td>
                        <td class="score-negative">-<?php echo $cancelCount * 20; ?> (<?php echo $cancelCount; ?> × 20)</td>
                    </tr>
                    <?php if ($disputeCount > 0): ?>
                    <tr>
                        <td>⚠️ Disputes / damage reports</td>
                        <td class="score-negative"><?php echo $disputeCount; ?> on file</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
            <p class="score-tip">💡 Tip: Complete rentals on time to boost your score. Cancelling after approval hurts more than not requesting at all — only request what you're sure you'll follow through on.</p>
        </section>
    </main>
</div>
</body>
</html>