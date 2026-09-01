<?php
// Include this at the top of every Borrower-only page.
// Makes sure someone is logged in AND their role is Borrower (role_id = 2).
session_start();

if (!isset($_SESSION["id"]) || !isset($_SESSION["role_id"])) {
    header("Location: login.php");
    exit();
}

if ($_SESSION["role_id"] != 2) {
    // Logged in, but not a Borrower -> kick back to login
    header("Location: login.php?err=wrongrole");
    exit();
}

$borrower_id = $_SESSION["id"];
?>