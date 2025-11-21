<?php
session_start();
require 'db-connect.php';

try {
    $pdo = new PDO($connect, USER, PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    exit('DB接続エラー: ' . $e->getMessage());
}

if (empty($_SESSION['customer']['customer_id'])) {
    exit('ログイン情報がありません。');
}

$customer_id = $_SESSION['customer']['customer_id'];

$sql = $pdo->prepare('SELECT name, subscr_join, points FROM customers WHERE customer_id = ?');
$sql->execute([$customer_id]);
$customer = $sql->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>マイページ</title>
<link rel="stylesheet" href="style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
.mypage-container { max-width: 600px; margin: 20px auto; padding: 20px; background:#f7f7f7; border-radius:8px; }
.profile img { width:100px; height:100px; border-radius:50%; }
.profile .name { font-size:1.5em; font-weight:bold; margin-top:10px; }
.profile .subscribe, .profile .points { margin-top:5px; }
.menu { margin-top:20px; }
.menu button { display:block; width:100%; padding:10px; margin-bottom:10px; border:none; border-radius:5px; text-align:left; background:#90caf9; color:#fff; font-weight:bold; cursor:pointer; }
.logout { display:block; width:100%; padding:10px; border:none; border-radius:5px; background:#f44336; color:#fff; font-weight:bold; cursor:pointer; }
</style>
</head>
<body class="bodys">

<?php include('header.php'); ?>

<div class="mypage-container">
    <div class="profile">
        <img src="https://via.placeholder.com/100" alt="プロフィール画像">
        <div class="name"><?= htmlspecialchars($customer['name']); ?></div>
        <div class="subscribe">
            <?= $customer['subscr_join'] == 1 ? "サブスク登録中" : "サブスク未登録"; ?>
        </div>
        <div class="points">所持ポイント：<?= number_format($customer['points']) ?>P</div>
    </div>

    <div class="menu">
        <button onclick="location.href='profile-view.php'"><i class="fa-solid fa-user"></i> My情報</button>
        <button onclick="location.href=''"><i class="fa-solid fa-clock"></i> 購入履歴</button>
        <button onclick="location.href='subscribe.php'"><i class="fa-solid fa-star"></i> サブスク登録</button>
        <button onclick="location.href='stamp.php'"><i class="fa-solid fa-face-smile"></i> スタンプカード</button>
    </div>

    <form action="logout.php" method="post">
        <button type="submit" class="logout">ログアウト</button>
    </form>
</div>

</body>
</html>
