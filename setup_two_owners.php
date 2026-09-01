<?php
include("db.php");
include("functions.php");

$message = "";
$error = "";

$owners = [
    [
        'user_id' => 3001,
        'password' => 'Owner123',
        'email' => 'owner1@borrowbee.local',
        'phone' => '01700003001',
        'nid' => '3001000001',
        'full_name' => 'Demo Owner One',
        'address' => 'Dhaka, Bangladesh',
        'date_of_birth' => '2000-01-01'
    ],
    [
        'user_id' => 3002,
        'password' => 'Owner456',
        'email' => 'owner2@borrowbee.local',
        'phone' => '01700003002',
        'nid' => '3002000002',
        'full_name' => 'Demo Owner Two',
        'address' => 'Dhaka, Bangladesh',
        'date_of_birth' => '2000-02-02'
    ]
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        foreach ($owners as $owner) {
            mysqli_begin_transaction($conn);

            $user_id = $owner['user_id'];
            $role_id = 3;
            $password_hash = password_hash($owner['password'], PASSWORD_DEFAULT);
            $account_status = 'active';

            $stmtCheck = mysqli_prepare($conn, "SELECT user_id FROM users WHERE user_id = ? LIMIT 1");
            mysqli_stmt_bind_param($stmtCheck, "i", $user_id);
            mysqli_stmt_execute($stmtCheck);
            $existingUser = mysqli_fetch_assoc(mysqli_stmt_get_result($stmtCheck));
            mysqli_stmt_close($stmtCheck);

            if ($existingUser) {
                $stmtUser = mysqli_prepare($conn,
                    "UPDATE users
                     SET role_id = ?, email = ?, phone = ?, password = ?, account_status = ?, NID = ?
                     WHERE user_id = ?"
                );
                mysqli_stmt_bind_param(
                    $stmtUser,
                    "isssssi",
                    $role_id,
                    $owner['email'],
                    $owner['phone'],
                    $password_hash,
                    $account_status,
                    $owner['nid'],
                    $user_id
                );
            } else {
                $stmtUser = mysqli_prepare($conn,
                    "INSERT INTO users
                     (user_id, role_id, email, phone, password, account_status, created_at, NID)
                     VALUES (?, ?, ?, ?, ?, ?, NOW(), ?)"
                );
                mysqli_stmt_bind_param(
                    $stmtUser,
                    "iisssss",
                    $user_id,
                    $role_id,
                    $owner['email'],
                    $owner['phone'],
                    $password_hash,
                    $account_status,
                    $owner['nid']
                );
            }
            mysqli_stmt_execute($stmtUser);
            mysqli_stmt_close($stmtUser);

            $stmtProfileCheck = mysqli_prepare($conn, "SELECT profile_id FROM user_profiles WHERE user_id = ? LIMIT 1");
            mysqli_stmt_bind_param($stmtProfileCheck, "i", $user_id);
            mysqli_stmt_execute($stmtProfileCheck);
            $existingProfile = mysqli_fetch_assoc(mysqli_stmt_get_result($stmtProfileCheck));
            mysqli_stmt_close($stmtProfileCheck);

            if ($existingProfile) {
                $stmtProfile = mysqli_prepare($conn,
                    "UPDATE user_profiles
                     SET full_name = ?, address = ?, date_of_birth = ?
                     WHERE user_id = ?"
                );
                mysqli_stmt_bind_param(
                    $stmtProfile,
                    "sssi",
                    $owner['full_name'],
                    $owner['address'],
                    $owner['date_of_birth'],
                    $user_id
                );
            } else {
                // Original SQL does not use AUTO_INCREMENT for profile_id.
                $resultId = mysqli_query($conn, "SELECT MAX(profile_id) AS max_id FROM user_profiles FOR UPDATE");
                $rowId = mysqli_fetch_assoc($resultId);
                $profile_id = ($rowId['max_id'] === null) ? 0 : ((int)$rowId['max_id'] + 1);

                $stmtProfile = mysqli_prepare($conn,
                    "INSERT INTO user_profiles
                     (profile_id, user_id, full_name, address, date_of_birth)
                     VALUES (?, ?, ?, ?, ?)"
                );
                mysqli_stmt_bind_param(
                    $stmtProfile,
                    "iisss",
                    $profile_id,
                    $user_id,
                    $owner['full_name'],
                    $owner['address'],
                    $owner['date_of_birth']
                );
            }
            mysqli_stmt_execute($stmtProfile);
            mysqli_stmt_close($stmtProfile);

            mysqli_commit($conn);
        }

        $message = "Two Owner demo accounts are ready. You can now log in as Owner 3001 or Owner 3002.";
    } catch (Throwable $ex) {
        mysqli_rollback($conn);
        $error = "Could not create the Owner accounts: " . $ex->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Setup Two Owners | BorrowBee</title>
<link rel="stylesheet" href="style.css">
</head>
<body class="auth-body">
<div class="register-card">
    <div class="form-panel register-panel">
        <h1>SETUP TWO OWNERS</h1>
        <p class="subtitle">This page inserts two demo Owner accounts without changing your SQL file or table structure.</p>

        <?php if ($message): ?><div class="success-msg"><?php echo e($message); ?></div><?php endif; ?>
        <?php if ($error): ?><div class="error-msg"><?php echo e($error); ?></div><?php endif; ?>

        <div class="panel" style="margin-bottom:18px;">
            <p><strong>Owner Account 1</strong></p>
            <p>User ID: <strong>3001</strong></p>
            <p>Password: <strong>Owner123</strong></p>
            <br>
            <p><strong>Owner Account 2</strong></p>
            <p>User ID: <strong>3002</strong></p>
            <p>Password: <strong>Owner456</strong></p>
        </div>

        <form method="post">
            <button type="submit" class="btn-login">CREATE / RESET TWO OWNER ACCOUNTS</button>
        </form>

        <a class="auth-link" href="login.php">← Go to Login</a>
        <p class="subtitle" style="margin-top:16px;">For a real deployment, delete this setup file after creating the demo accounts.</p>
    </div>
</div>
</body>
</html>
