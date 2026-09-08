<?php
session_start();
unset($_SESSION['customer_email']);
unset($_SESSION['customer_phone']);
unset($_SESSION['customer_name']);
header("Location: ../login.php");
exit;
?>

