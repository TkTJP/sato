<?php
session_start();
require 'db-connect.php';

// DB接続
try {
    $pdo = new PDO($connect, USER, PASS);
} catch (PDOException $e) {
    exit('DB接続エラー: ' . $e->getMessage());
}

// ログイン確認
// if (empty($_SESSION['customer']['customer_id'])) {
//    exit('ログイン情報がありません。');
//}

$customer_id = $_SESSION['customer']['customer_id'];

// customersテーブルから名前とサブスク状態を取得
$sql = $pdo->prepare('SELECT name, subscr_join FROM customers WHERE customer_id = ?');
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
</head>
<body>

  <?php include('header.php'); ?>

  <div class="mypage-container">
      <nav class="nav-bar">
          <button class="back-button" onclick="history.back()">
              <i class="fa-solid fa-arrow-left"></i>
          </button>
          <span class="nav-title">マイページ</span>
      </nav>
    <div class="profile">
      <img src="https://via.placeholder.com/100" alt="プロフィール画像">
      <div class="name"><?= htmlspecialchars($customer['name'] ?? $_SESSION['customer']['name']); ?></div>

      <div class="subscribe">
        <?php if (!empty($customer['subscr_join']) && $customer['subscr_join'] == 1): ?>
          <p>サブスク登録中です</p>
        <?php else: ?>
          <p>サブスク未登録です</p>
        <?php endif; ?>
      </div>

      <div class="points">所持ポイント：1500</div>
    </div>

    <div class="menu">
      <button onclick="location.href='profile-view.php'"><i class="fa-solid fa-user"></i>My情報 <i class="fa-solid fa-angle-right"></i></button>
      <button onclick="location.href=''"><i class="fa-solid fa-clock"></i>購入履歴 <i class="fa-solid fa-angle-right"></i></button>
      <button onclick="location.href='subscribe.php'"><i class="fa-solid fa-star"></i>サブスクに登録する <i class="fa-solid fa-angle-right"></i></button>
      <button onclick="location.href=''"><i class="fa-solid fa-face-smile"></i>スタンプカード <i class="fa-solid fa-angle-right"></i></button>
    </div>

    <form action="logout.php" method="post">
      <button type="submit" class="logout">ログアウト</button>
    </form>
  </div>

</body>
</html>
