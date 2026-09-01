<?php
session_start();

if (!isset($_SESSION["id"]) || !isset($_SESSION["role_id"])) {
    header("Location: login.php");
    exit();
}

if ($_SESSION["role_id"] != 1) {
    header("Location: login.php?err=wrongrole");
    exit();
}

$admin_id = $_SESSION["id"];
?>