<?php
session_start();
include("db.php");

$user_id  = "";
$role_id  = "";
$error    = "";

$roles = [
    1 => "Admin",
    2 => "Borrower",
    3 => "Owner",
    4 => "Verifier"
];

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $user_id  = trim($_POST["user_id"]);
    $password = trim($_POST["password"]);
    $role_id  = trim($_POST["role_id"]);

    if (empty($user_id)) {
        $error = "Enter User ID";
    } elseif (empty($password)) {
        $error = "Enter Password";
    } elseif (empty($role_id) || !isset($roles[$role_id])) {
        $error = "Select a valid Role";
    } else {

        $sql = "SELECT * FROM users WHERE user_id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if (mysqli_num_rows($result) == 1) {

            $row = mysqli_fetch_assoc($result);

            if (password_verify($password, $row["password"])) {

                if ($row["role_id"] != $role_id) {

                    $error = "This account is not registered as " . htmlspecialchars($roles[$role_id]);

                } else {

                    $_SESSION["id"]      = $row["user_id"];
                    $_SESSION["user_id"] = $row["user_id"];
                    $_SESSION["email"]   = $row["email"];
                    $_SESSION["role_id"] = $row["role_id"];

                    switch ($row["role_id"]) {

                        case 1:
                            header("Location: admin_dashboard.php");
                            break;

                        case 2:
                            header("Location: borrower_dashboard.php");
                            break;

                        case 3:
                            header("Location: owner_dashboard.php");
                            break;

                        case 4:
                            header("Location: verifier_dashboard.php");
                            break;

                        default:
                            header("Location: login.php");
                            break;
                    }

                    exit();
                }

            } else {

                $error = "Invalid User ID or Password";
            }

        } else {

            $error = "Invalid User ID or Password";
        }

        mysqli_stmt_close($stmt);
    }
}

if (isset($_GET["err"]) && $_GET["err"] == "wrongrole" && $error == "") {
    $error = "Please log in with the correct role for that page.";
}
?>

<!DOCTYPE html>

<html lang="en">

<head>
<meta charset="UTF-8">
<title>Login | BorrowBee</title>
<link rel="stylesheet" href="style.css">
</head>

<body>

<div class="login-wrapper">

<div class="login-card">

    <div class="art-panel">

        <div class="art-badge">🐝</div>
        <div class="hill"></div>
        <div class="tree tree-back"></div>
        <div class="tree tree-front"></div>

        <svg class="bird bird1" viewBox="0 0 24 24">
            <path d="M2 12c3-3 5-3 7 0 2-3 4-3 7 0 2-3 4-3 6 0"
            stroke="rgba(255,255,255,0.6)"
            stroke-width="1.5"
            fill="none"
            stroke-linecap="round"/>
        </svg>

        <svg class="bird bird2" viewBox="0 0 24 24">
            <path d="M2 12c3-3 5-3 7 0 2-3 4-3 7 0 2-3 4-3 6 0"
            stroke="rgba(255,255,255,0.5)"
            stroke-width="1.5"
            fill="none"
            stroke-linecap="round"/>
        </svg>

        <svg class="bird bird3" viewBox="0 0 24 24">
            <path d="M2 12c3-3 5-3 7 0 2-3 4-3 7 0 2-3 4-3 6 0"
            stroke="rgba(255,255,255,0.4)"
            stroke-width="1.5"
            fill="none"
            stroke-linecap="round"/>
        </svg>

        <div class="art-caption">
            <h2>BorrowBee</h2>
            <p>Borrow what you need, when you need it.</p>
        </div>

    </div>

    <div class="form-panel">

        <h1>USER LOGIN</h1>

        <p class="subtitle">
            Welcome back, please sign in
        </p>

        <form method="post" action="">

            <div class="input-group">

                <span class="icon">🆔</span>

                <input
                    type="number"
                    name="user_id"
                    placeholder="User ID"
                    value="<?php echo htmlspecialchars($user_id); ?>"
                    required>

            </div>

            <div class="input-group">

                <span class="icon">🔒</span>

                <input
                    type="password"
                    name="password"
                    placeholder="Password"
                    required>

            </div>

            <div class="input-group select-group">

                <span class="icon">👥</span>

                <select name="role_id" required>

                    <option
                        value=""
                        disabled
                        <?php echo ($role_id === "") ? "selected" : ""; ?>>
                        Select Role
                    </option>

                    <?php foreach ($roles as $id => $label): ?>

                        <option
                            value="<?php echo $id; ?>"
                            <?php echo ((string)$role_id === (string)$id) ? "selected" : ""; ?>>

                            <?php echo htmlspecialchars($label); ?>

                        </option>

                    <?php endforeach; ?>

                </select>

            </div>

            <?php if ($error): ?>

                <div class="error-msg">
                    <?php echo htmlspecialchars($error); ?>
                </div>

            <?php endif; ?>

            <button type="submit" class="btn-login">
                LOGIN
            </button>

            <a href="registration.php" class="create-account">
                Create Account
            </a>

        </form>

    </div>

</div>
```

</div>

<p class="copyright">
    Copyright &copy; <?php echo date("Y"); ?> BorrowBee
</p>

</body>

</html>
