<?php
session_start();
require_once('db-connect.php');

if (isset($_SESSION['customer_id'])) {
    try {
        $pdo = new PDO($connect, USER, PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // DB側のトークンも削除
        $sql = "UPDATE customers SET remember_token = NULL WHERE customer_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$_SESSION['customer_id']]);
    } catch (PDOException $e) {
        // 無視
    }
}

// Cookie削除
setcookie('remember_token', '', time() - 3600, '/');

// セッション終了
$_SESSION = [];
session_destroy();

header("Location: login.php");
exit;
?>
