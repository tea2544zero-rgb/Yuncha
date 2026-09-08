<?php
session_start();

// ล้างข้อมูล Session ทั้งหมด
$_SESSION = array();
session_unset();
session_destroy();

// เด้งกลับไปหน้า login
header("Location: ../login.php");
exit();
?>
