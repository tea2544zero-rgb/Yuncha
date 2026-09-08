<?php
session_start();
if (isset($_GET['lang']) && in_array($_GET['lang'], ['th', 'en'])) {
    $_SESSION['admin_lang'] = $_GET['lang'];
}
header('Location: ' . (isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] :  '../dashboard/index.php'));
exit;
?>
