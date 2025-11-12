<?php
session_start();

// セッションを破棄
$_SESSION = [];
session_destroy();

header("Location: login.php");
exit;
?>
