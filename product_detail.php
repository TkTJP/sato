<?php
require 'db-connect.php';
$pdo = new PDO($connect, USER, PASS);

// 🔹 GETパラメータ確認
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo '不正なアクセスです。';
    exit;
}

$product_id = (int)$_GET['id'];

// 🔹 いいね機能：データベースからいいね数を取得
$likeStmt = $pdo->prepare('SELECT likes FROM products WHERE product_id = ?');
$likeStmt->execute([$product_id]);
$likeData = $likeStmt->fetch(PDO::FETCH_ASSOC);
$likes = $likeData ? (int)$likeData['likes'] : 0;

// 🔹 該当商品の取得
$stmt = $pdo->prepare('SELECT * FROM products WHERE product_id = ?');
$stmt->execute([$product_id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    echo '指定された商品は存在しません。';
    exit;
}
?>

<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<title><?php echo htmlspecialchars($product['name']); ?>｜商品詳細</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
<style>
/* ====== いいねボタンスタイル ====== */
.like-container {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 1.2rem;
  cursor: pointer;
  user-select: none;
}

.like-container i {
  color: #ccc;
  transition: color 0.2s ease;
}

.like-container.liked i {
  color: #ff6b9f; /* ピンク色 */
}
.like-count {
  font-weight: bold;
  color: #555;
}
</style>
</head>
<body>

<?php require 'header.php'; ?>

<h2>商品詳細ページ</h2>

<!-- 商品画像 -->
<div>
  <img src="img/<?php echo htmlspecialchars($product['image'] ?: 'noimage.png'); ?>" 
       alt="<?php echo htmlspecialchars($product['name']); ?>" 
       width="250">
</div>

<!-- 商品情報 -->
<div>
  <h3 style="display: flex; align-items: center; gap: 10px;">
    <?php echo htmlspecialchars($product['name']); ?>
    <!-- いいねボタン -->
    <div class="like-container" id="likeBtn">
      <i class="fa-solid fa-heart"></i>
      <span class="like-count" id="likeCount"><?php echo $likes; ?></span>
    </div>
  </h3>
  <p><?php echo htmlspecialchars($product['description']); ?></p>
  <p>価格：¥<?php echo number_format($product['price']); ?></p>
  <p>在庫数：<?php echo htmlspecialchars($product['stock']); ?></p>
  <p>登録日：<?php echo htmlspecialchars($product['created_at']); ?></p>
</div>

<!-- カートに追加フォーム -->
<form action="cart-confirm.php" method="post">
  <input type="hidden" name="id" value="<?= $product['id'] ?>">
  <input type="hidden" name="name" value="<?= htmlspecialchars($product['name']) ?>">
  <input type="hidden" name="price" value="<?= $product['price'] ?>">
  <input type="hidden" name="image" value="<?= $product['image'] ?>">
  <button type="submit">カートに入れる</button>
</form>

<hr>

<!-- 戻るボタン -->
<p><a href="top.php">← 商品一覧へ戻る</a></p>

<script>
// ====== いいね機能（フロント側） ======
document.getElementById('likeBtn').addEventListener('click', async function() {
  const likeBtn = this;
  const countElem = document.getElementById('likeCount');
  const productId = <?php echo $product_id; ?>;

  // サーバーに送信
  const response = await fetch('like.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: 'id=' + productId
  });

  const data = await response.json();
  if (data.success) {
    likeBtn.classList.add('liked');
    countElem.textContent = data.likes;
  }
});
</script>

</body>
</html>
