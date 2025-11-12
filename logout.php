<?php
session_start();
require_once('db-connect.php');

// DBのトークン削除
if (isset($_SESSION['customer_id'])) {
    try {
        $pdo = new PDO($connect, USER, PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $sql = "UPDATE customers SET remember_token = NULL WHERE customer_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$_SESSION['customer_id']]);
    } catch (PDOException $e) {
        // エラー時は無視
    }
}

// Cookie完全削除
setcookie('remember_token', '', time() - 3600, '/');
unset($_COOKIE['remember_token']);

// セッション破棄
$_SESSION = [];
session_destroy();

// ログインページへ戻る
header("Location: login.php");
exit;
?>
