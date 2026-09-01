<?php
include("auth_borrower.php");
include("db.php");

$error   = "";
$success = "";

$pwError   = "";
$pwSuccess = "";

// Load current user + profile data
$stmt = mysqli_prepare($conn, "
    SELECT u.user_id, u.email, u.phone, u.password, u.created_at, p.full_name
    FROM users u
    LEFT JOIN user_profiles p ON p.user_id = u.user_id
    WHERE u.user_id = ?
");
mysqli_stmt_bind_param($stmt, "i", $borrower_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$profile = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

// ---------- Update profile info (name / email / phone) ----------
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["update_profile"])) {

    $full_name = trim($_POST["full_name"]);
    $phone     = trim($_POST["phone"]);
    $email     = trim($_POST["email"]);

    if (empty($full_name)) {
        $error = "Full name cannot be empty.";
    } elseif (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Enter a valid email.";
    } elseif (empty($phone)) {
        $error = "Phone number cannot be empty.";
    } else {

        // Make sure email isn't already used by someone else
        $stmtCheck = mysqli_prepare($conn, "SELECT user_id FROM users WHERE email = ? AND user_id != ?");
        mysqli_stmt_bind_param($stmtCheck, "si", $email, $borrower_id);
        mysqli_stmt_execute($stmtCheck);
        $resCheck = mysqli_stmt_get_result($stmtCheck);

        if (mysqli_num_rows($resCheck) > 0) {
            $error = "That email is already used by another account.";
        } else {

            mysqli_begin_transaction($conn);

            $stmtUser = mysqli_prepare($conn, "UPDATE users SET email = ?, phone = ? WHERE user_id = ?");
            mysqli_stmt_bind_param($stmtUser, "ssi", $email, $phone, $borrower_id);
            $ok1 = mysqli_stmt_execute($stmtUser);
            mysqli_stmt_close($stmtUser);

            $stmtProfile = mysqli_prepare($conn, "UPDATE user_profiles SET full_name = ? WHERE user_id = ?");
            mysqli_stmt_bind_param($stmtProfile, "si", $full_name, $borrower_id);
            $ok2 = mysqli_stmt_execute($stmtProfile);
            mysqli_stmt_close($stmtProfile);

            if ($ok1 && $ok2) {
                mysqli_commit($conn);
                $success = "Profile updated successfully.";
                $profile["full_name"] = $full_name;
                $profile["email"]     = $email;
                $profile["phone"]     = $phone;
            } else {
                mysqli_rollback($conn);
                $error = "Could not update profile.";
            }
        }
        mysqli_stmt_close($stmtCheck);
    }
}

// ---------- Change password ----------
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["change_password"])) {

    $current_password = trim($_POST["current_password"]);
    $new_password      = trim($_POST["new_password"]);
    $confirm_password  = trim($_POST["confirm_password"]);

    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $pwError = "Please fill in all password fields.";
    } elseif (!password_verify($current_password, $profile["password"])) {
        $pwError = "Current password is incorrect.";
    } elseif (strlen($new_password) < 6) {
        $pwError = "New password must be at least 6 characters.";
    } elseif ($new_password !== $confirm_password) {
        $pwError = "New password and confirm password do not match.";
    } elseif (password_verify($new_password, $profile["password"])) {
        $pwError = "New password must be different from the current password.";
    } else {

        $newHash = password_hash($new_password, PASSWORD_DEFAULT);

        $stmtPw = mysqli_prepare($conn, "UPDATE users SET password = ? WHERE user_id = ?");
        mysqli_stmt_bind_param($stmtPw, "si", $newHash, $borrower_id);

        if (mysqli_stmt_execute($stmtPw)) {
            $pwSuccess = "Password changed successfully.";
            $profile["password"] = $newHash; // keep in sync for this request
        } else {
            $pwError = "Could not update password: " . mysqli_stmt_error($stmtPw);
        }
        mysqli_stmt_close($stmtPw);
    }
}

$current = "profile";
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>My Profile | BorrowBee</title>
<link rel="stylesheet" href="style.css">
</head>
<body class="app-body">
<div class="app-layout">
    <?php include("borrower_sidebar.php"); ?>

    <main class="main-content">
        <header class="topbar">
            <div>
                <h1>My Profile</h1>
                <p class="topbar-sub">Manage your personal information.</p>
            </div>
        </header>

        <section class="panel panel-narrow">
            <div class="profile-avatar-row">
                <div class="profile-avatar">🐝</div>
                <div>
                    <h3><?php echo htmlspecialchars($profile["full_name"] ?? "Borrower"); ?></h3>
                    <p class="topbar-sub">User ID: <?php echo htmlspecialchars($profile["user_id"]); ?> · Member since <?php echo htmlspecialchars(date("M Y", strtotime($profile["created_at"]))); ?></p>
                </div>
            </div>

            <form method="post" action="borrower_profile.php" class="stacked-form">
                <input type="hidden" name="update_profile" value="1">

                <label>Full Name</label>
                <div class="input-group">
                    <span class="icon">👤</span>
                    <input type="text" name="full_name" value="<?php echo htmlspecialchars($profile["full_name"] ?? ""); ?>" required>
                </div>

                <label>Email</label>
                <div class="input-group">
                    <span class="icon">✉️</span>
                    <input type="email" name="email" value="<?php echo htmlspecialchars($profile["email"]); ?>" required>
                </div>

                <label>Phone</label>
                <div class="input-group">
                    <span class="icon">📱</span>
                    <input type="text" name="phone" value="<?php echo htmlspecialchars($profile["phone"]); ?>" required>
                </div>

                <?php if ($error): ?>
                    <div class="error-msg"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                <?php if ($success): ?>
                    <div class="success-msg"><?php echo htmlspecialchars($success); ?></div>
                <?php endif; ?>

                <button type="submit" class="btn-login">SAVE CHANGES</button>
            </form>
        </section>

        <section class="panel panel-narrow">
            <h2 class="panel-title-inline">🔒 Change Password</h2>

            <form method="post" action="borrower_profile.php" class="stacked-form">
                <input type="hidden" name="change_password" value="1">

                <label>Current Password</label>
                <div class="input-group">
                    <span class="icon">🔑</span>
                    <input type="password" name="current_password" placeholder="Enter current password" required>
                </div>

                <label>New Password</label>
                <div class="input-group">
                    <span class="icon">🔒</span>
                    <input type="password" name="new_password" placeholder="At least 6 characters" required minlength="6">
                </div>

                <label>Confirm New Password</label>
                <div class="input-group">
                    <span class="icon">🔒</span>
                    <input type="password" name="confirm_password" placeholder="Re-enter new password" required minlength="6">
                </div>

                <?php if ($pwError): ?>
                    <div class="error-msg"><?php echo htmlspecialchars($pwError); ?></div>
                <?php endif; ?>
                <?php if ($pwSuccess): ?>
                    <div class="success-msg"><?php echo htmlspecialchars($pwSuccess); ?></div>
                <?php endif; ?>

                <button type="submit" class="btn-login">CHANGE PASSWORD</button>
            </form>
        </section>
    </main>
</div>
</body>
</html>