<?php
function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function full_name($conn, $user_id, $fallback = "User") {
    $stmt = mysqli_prepare($conn, "SELECT full_name FROM user_profiles WHERE user_id = ? LIMIT 1");
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    return ($row && trim($row['full_name']) !== '') ? $row['full_name'] : $fallback;
}

function rental_days($start_date, $end_date) {
    $start = new DateTime($start_date);
    $end = new DateTime($end_date);
    return max(1, $start->diff($end)->days + 1);
}

function rental_total($start_date, $end_date, $daily_price, $deposit) {
    return rental_days($start_date, $end_date) * (float)$daily_price + (float)$deposit;
}

function request_is_paid($conn, $request_id, $borrower_id) {
    $stmt = mysqli_prepare($conn, "SELECT payment_id FROM payments WHERE booking_id = ? AND payer_id = ? AND status = 'completed' LIMIT 1");
    mysqli_stmt_bind_param($stmt, "ii", $request_id, $borrower_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $paid = mysqli_fetch_assoc($result) ? true : false;
    mysqli_stmt_close($stmt);
    return $paid;
}
?>
