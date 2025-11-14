<?php
session_start();
require 'db-connect.php';
$pdo = new PDO($connect, USER, PASS);

// GETパラメータ確認
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo '不正なアクセスです。';
    exit;
}

$product_id = (int)$_GET['id'];

// 商品取得
$stmt = $pdo->prepare('SELECT * FROM products WHERE product_id = ?');
$stmt->execute([$product_id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$product) {
    echo '指定された商品は存在しません。';
    exit;
}

// 総いいね数の取得
$countStmt = $pdo->prepare('SELECT COUNT(*) FROM likes WHERE product_id = ?');
$countStmt->execute([$product_id]);
$totalLikes = $countStmt->fetchColumn();

// ログイン中のユーザーがいいね済みか確認
$customer_id = $_SESSION['customer']['id'] ?? null;
$isLiked = false;
if ($customer_id) {
    $checkStmt = $pdo->prepare('SELECT 1 FROM likes WHERE product_id = ? AND customer_id = ?');
    $checkStmt->execute([$product_id, $customer_id]);
    $isLiked = (bool)$checkStmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<title><?php echo htmlspecialchars($product['name']); ?>｜商品詳細</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
<style>
.like-container {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 1.4rem;
  cursor: pointer;
  user-select: none;
}
.like-container i {
  color: #ccc;
  transition: color 0.2s ease;
}
.like-container.liked i {
  color: #ff6b9f;
}
.like-count {
  font-weight: bold;
  color: #555;
}
</style>
</head>
<body>

<?php require 'header.php'; ?>
<p><a href="top.php">←</a></p>

<div>
  <img src="img/<?php echo htmlspecialchars($product['image'] ?: 'noimage.png'); ?>" width="250">
</div>

<div>
  <h3 style="display:flex;align-items:center;gap:10px;">
    <?php echo htmlspecialchars($product['name']); ?>
    <div class="like-container <?php echo $isLiked ? 'liked' : ''; ?>" id="likeBtn">
      <i class="fa-solid fa-heart"></i>
      <span class="like-count" id="likeCount"><?php echo $totalLikes; ?></span>
    </div>
  </h3>

  <p>価格：¥<?php echo number_format($product['price']); ?></p>

  <!-- ▼ 個数変更（＋ / −） -->
  <div style="display:flex;align-items:center;gap:15px;margin:15px 0;">
      <button type="button" id="decrease">－</button>
      <span id="quantityDisplay">1</span>
      <button type="button" id="increase">＋</button>
  </div>
</div>

<form action="cart-confirm.php" method="post">
  <input type="hidden" name="id" value="<?php echo $product['product_id']; ?>">
  <input type="hidden" name="name" value="<?php echo htmlspecialchars($product['name']); ?>">
  <input type="hidden" name="price" value="<?php echo (int)$product['price']; ?>">
  <input type="hidden" name="image" value="<?php echo htmlspecialchars($product['image']); ?>">

  <!-- ここを quantityInput に変更 -->
  <input type="hidden" id="quantityInput" name="quantity" value="1">

  <button type="submit">カートに入れる</button>
</form>

<hr>
<button id="descToggle">商品説明 ▼</button>
<div id="descContent" style="display:none;">
  <p><?php echo nl2br(htmlspecialchars($product['description'] ?? '説明が登録されていません。')); ?></p>
</div>

<script>
// ======= 個数変更スクリプト =======
const increaseBtn = document.getElementById('increase');
const decreaseBtn = document.getElementById('decrease');
const quantityDisplay = document.getElementById('quantityDisplay');
const quantityInput = document.getElementById('quantityInput');

// 商品の在庫最大数（なければ 999 にしてもOK）
const maxStock = <?php echo isset($product['stock']) ? (int)$product['stock'] : 999; ?>;

let quantity = 1;

// ＋ボタン
increaseBtn.addEventListener('click', () => {
  if (quantity < maxStock) {
    quantity++;
    updateDisplay();
  }
});

// －ボタン
decreaseBtn.addEventListener('click', () => {
  if (quantity > 1) {
    quantity--;
    updateDisplay();
  }
});

function updateDisplay() {
  quantityDisplay.textContent = quantity;
  quantityInput.value = quantity;
}

// ======= いいね機能 =======
document.getElementById('likeBtn').addEventListener('click', async function() {
  const likeBtn = this;
  const countElem = document.getElementById('likeCount');
  const productId = <?php echo $product_id; ?>;

  const response = await fetch('like_toggle.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: 'id=' + productId
  });

  const data = await response.json();
  if (data.success) {
    countElem.textContent = data.likes;
    likeBtn.classList.toggle('liked', data.liked);
  } else {
    alert(data.message);
  }
});

// ======= 商品説明の開閉 =======
const descToggle = document.getElementById('descToggle');
const descContent = document.getElementById('descContent');

descToggle.addEventListener('click', () => {
  if (descContent.style.display === 'none') {
    descContent.style.display = 'block';
    descToggle.textContent = '商品説明 ▲';
  } else {
    descContent.style.display = 'none';
    descToggle.textContent = '商品説明 ▼';
  }
});
</script>

</body>
</html>
