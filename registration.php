<?php

include("db.php");

$user_id   = "";
$full_name = "";
$email     = "";
$phone     = "";
$nid       = "";

$idError       = "";
$nameError     = "";
$emailError    = "";
$phoneError    = "";
$nidError      = "";
$passwordError = "";
$message       = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $user_id   = trim($_POST["user_id"]);
    $full_name = trim($_POST["full_name"]);
    $email     = trim($_POST["email"]);
    $phone     = trim($_POST["phone"]);
    $nid       = trim($_POST["nid"]);
    $password  = trim($_POST["password"]);
    $confirm   = trim($_POST["confirm_password"]);

    if (empty($user_id))   { $idError = "Enter User ID"; }
    elseif (!ctype_digit($user_id)) { $idError = "User ID must be a number"; }
    if (empty($full_name)) { $nameError = "Enter Full Name"; }
    if (empty($email))     { $emailError = "Enter Email"; }
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) { $emailError = "Enter a valid Email"; }
    if (empty($phone))     { $phoneError = "Enter Phone Number"; }
    if (empty($nid))       { $nidError = "Enter NID Number"; }
    if (empty($password))  { $passwordError = "Enter Password"; }
    elseif ($password !== $confirm) { $passwordError = "Passwords do not match"; }

    if ($idError == "" && $nameError == "" && $emailError == "" && $phoneError == "" && $nidError == "" && $passwordError == "") {

        // Check if User ID already exists
        $checkId = "SELECT * FROM users WHERE user_id = ?";
        $stmtId = mysqli_prepare($conn, $checkId);
        mysqli_stmt_bind_param($stmtId, "i", $user_id);
        mysqli_stmt_execute($stmtId);
        $resultId = mysqli_stmt_get_result($stmtId);

        if (mysqli_num_rows($resultId) > 0) {
            $idError = "User ID Already Taken";
        }

        mysqli_stmt_close($stmtId);
    }

    if ($idError == "" && $nameError == "" && $emailError == "" && $phoneError == "" && $nidError == "" && $passwordError == "") {

        // Check if email already exists
        $check = "SELECT * FROM users WHERE email = ?";
        $stmt = mysqli_prepare($conn, $check);
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if (mysqli_num_rows($result) > 0) {
            $message = "Email Already Registered";
        } else {

            // Hash the password before storing it
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $roleId         = 2; // 2 = Borrower (self-registration is always as a Borrower)
            $newUserId      = $user_id;

            mysqli_begin_transaction($conn);

            // Insert into users
            $sql = "INSERT INTO users (user_id, role_id, email, phone, password, created_at, NID)
                    VALUES (?, ?, ?, ?, ?, NOW(), ?)";
            $insertStmt = mysqli_prepare($conn, $sql);

            if ($insertStmt === false) {
                $message = "SQL Error (users prepare): " . mysqli_error($conn);
            } else {

                mysqli_stmt_bind_param($insertStmt, "iissss", $newUserId, $roleId, $email, $phone, $hashedPassword, $nid);

                if (!mysqli_stmt_execute($insertStmt)) {
                    mysqli_rollback($conn);
                    $message = "SQL Error (users insert): " . mysqli_stmt_error($insertStmt);
                } else {

                    // Manually generate the next profile_id, since this table has no AUTO_INCREMENT
                    $maxProfileResult = mysqli_query($conn, "SELECT MAX(profile_id) AS max_id FROM user_profiles FOR UPDATE");
                    $maxProfileRow    = mysqli_fetch_assoc($maxProfileResult);
                    $newProfileId     = ($maxProfileRow["max_id"] === null) ? 1 : $maxProfileRow["max_id"] + 1;

                    // Insert into user_profiles
                    $sqlProfile = "INSERT INTO user_profiles (profile_id, user_id, full_name) VALUES (?, ?, ?)";
                    $profileStmt = mysqli_prepare($conn, $sqlProfile);

                    if ($profileStmt === false) {
                        mysqli_rollback($conn);
                        $message = "SQL Error (user_profiles prepare): " . mysqli_error($conn);
                    } else {

                        mysqli_stmt_bind_param($profileStmt, "iis", $newProfileId, $newUserId, $full_name);

                        if (!mysqli_stmt_execute($profileStmt)) {
                            mysqli_rollback($conn);
                            $message = "SQL Error (user_profiles insert): " . mysqli_stmt_error($profileStmt);
                        } else {

                            mysqli_commit($conn);
                            $message = "Registration Successful! Use your User ID (" . $newUserId . ") to log in.";
                            $user_id = $full_name = $email = $phone = $nid = "";
                        }

                        mysqli_stmt_close($profileStmt);
                    }
                }

                mysqli_stmt_close($insertStmt);
            }
        }

        mysqli_stmt_close($stmt);
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Registration | BorrowBee</title>
<link rel="stylesheet" href="style.css">
</head>
<body>

<div class="login-wrapper">

    <div class="login-card">

        <!-- Left illustrated panel -->
        <div class="art-panel">
            <div class="art-badge">🐝</div>
            <div class="hill"></div>
            <div class="tree tree-back"></div>
            <div class="tree tree-front"></div>
            <svg class="bird bird1" viewBox="0 0 24 24"><path d="M2 12c3-3 5-3 7 0 2-3 4-3 7 0 2-3 4-3 6 0" stroke="rgba(255,255,255,0.6)" stroke-width="1.5" fill="none" stroke-linecap="round"/></svg>
            <svg class="bird bird2" viewBox="0 0 24 24"><path d="M2 12c3-3 5-3 7 0 2-3 4-3 7 0 2-3 4-3 6 0" stroke="rgba(255,255,255,0.5)" stroke-width="1.5" fill="none" stroke-linecap="round"/></svg>
            <svg class="bird bird3" viewBox="0 0 24 24"><path d="M2 12c3-3 5-3 7 0 2-3 4-3 7 0 2-3 4-3 6 0" stroke="rgba(255,255,255,0.4)" stroke-width="1.5" fill="none" stroke-linecap="round"/></svg>
            <div class="art-caption">
                <h2>BorrowBee</h2>
                <p>Join the community in a few seconds.</p>
            </div>
        </div>

        <!-- Right form panel -->
        <div class="form-panel">

            <h1>REGISTRATION</h1>
            <p class="subtitle">Create your BorrowBee account</p>

            <form method="post" action="">

                <div class="input-group">
                    <span class="icon">🆔</span>
                    <input
                        type="number"
                        name="user_id"
                        placeholder="Choose a User ID"
                        value="<?php echo htmlspecialchars($user_id); ?>">
                </div>
                <?php if ($idError): ?><div class="field-error"><?php echo htmlspecialchars($idError); ?></div><?php endif; ?>

                <div class="input-group">
                    <span class="icon">👤</span>
                    <input
                        type="text"
                        name="full_name"
                        placeholder="Full Name"
                        value="<?php echo htmlspecialchars($full_name); ?>">
                </div>
                <?php if ($nameError): ?><div class="field-error"><?php echo htmlspecialchars($nameError); ?></div><?php endif; ?>

                <div class="input-group">
                    <span class="icon">✉️</span>
                    <input
                        type="email"
                        name="email"
                        placeholder="Email"
                        value="<?php echo htmlspecialchars($email); ?>">
                </div>
                <?php if ($emailError): ?><div class="field-error"><?php echo htmlspecialchars($emailError); ?></div><?php endif; ?>

                <div class="input-group">
                    <span class="icon">📱</span>
                    <input
                        type="text"
                        name="phone"
                        placeholder="Phone Number"
                        value="<?php echo htmlspecialchars($phone); ?>">
                </div>
                <?php if ($phoneError): ?><div class="field-error"><?php echo htmlspecialchars($phoneError); ?></div><?php endif; ?>

                <div class="input-group">
                    <span class="icon">🪪</span>
                    <input
                        type="text"
                        name="nid"
                        placeholder="NID Number"
                        value="<?php echo htmlspecialchars($nid); ?>">
                </div>
                <?php if ($nidError): ?><div class="field-error"><?php echo htmlspecialchars($nidError); ?></div><?php endif; ?>

                <div class="input-group">
                    <span class="icon">🔒</span>
                    <input
                        type="password"
                        name="password"
                        placeholder="Password">
                </div>

                <div class="input-group">
                    <span class="icon">🔒</span>
                    <input
                        type="password"
                        name="confirm_password"
                        placeholder="Confirm Password">
                </div>
                <?php if ($passwordError): ?><div class="field-error"><?php echo htmlspecialchars($passwordError); ?></div><?php endif; ?>

                <?php if ($message): ?>
                    <div class="<?php echo (strpos($message, 'Successful') !== false) ? 'success-msg' : 'error-msg'; ?>">
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php endif; ?>

                <button type="submit" class="btn-login">REGISTER</button>

                <a href="login.php" class="create-account">Already have an account? Login</a>

            </form>

        </div>

    </div>

</div>

<p class="copyright">Copyright &copy; <?php echo date("Y"); ?> BorrowBee</p>

</body>
</html>