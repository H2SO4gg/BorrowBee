<?php
if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }
if (!isset($_SESSION['id'], $_SESSION['role_id']) || (int)$_SESSION['role_id'] !== 3) {
    header("Location: login.php?err=wrongrole");
    exit();
}
$owner_id = (int)$_SESSION['id'];
?>
