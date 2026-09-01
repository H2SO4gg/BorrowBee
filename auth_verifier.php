<?php
session_start();

if (!isset($_SESSION["user_id"]) || $_SESSION["role_id"] != 4) {
    header("Location: login.php");
    exit();
}

$verifier_id = $_SESSION["user_id"];
?>