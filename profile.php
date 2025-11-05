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
    <div class="mypage-header">マイページ</div>

    <div class="profile">
      <img src="https://via.placeholder.com/100" alt="プロフィール画像">
      <div class="name">里野　美里</div>
      <div class="points">所持ポイント：1500</div>
    </div>

    <div class="menu">
      <button><i class="fa-solid fa-user"></i>My情報 <i class="fa-solid fa-angle-right"></i></button>
      <button><i class="fa-solid fa-clock"></i>購入履歴 <i class="fa-solid fa-angle-right"></i></button>
      <button><i class="fa-solid fa-star"></i>サブスクに登録する <i class="fa-solid fa-angle-right"></i></button>
      <button><i class="fa-solid fa-face-smile"></i>スタンプカード <i class="fa-solid fa-angle-right"></i></button>
    </div>

    <div class="logout">ログアウト</div>
  </div>

</body>
</html>
