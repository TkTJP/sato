<?php
session_start();

// 商品追加処理（product_detailから）
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
  $id = $_POST['id'];
  if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];

  if (isset($_SESSION['cart'][$id])) {
    $_SESSION['cart'][$id]['quantity'] += 1;
  } else {
    $_SESSION['cart'][$id] = [
      'id' => $id,
      'name' => $_POST['name'],
      'price' => (int)$_POST['price'],
      'image' => $_POST['image'],
      'quantity' => 1
    ];
  }
}

// 数量変更や削除処理
if (isset($_GET['action']) && isset($_GET['id'])) {
  $id = $_GET['id'];
  if ($_GET['action'] === 'plus') {
    $_SESSION['cart'][$id]['quantity']++;
  } elseif ($_GET['action'] === 'minus') {
    if ($_SESSION['cart'][$id]['quantity'] > 1) {
      $_SESSION['cart'][$id]['quantity']--;
    } else {
      unset($_SESSION['cart'][$id]);
    }
  } elseif ($_GET['action'] === 'delete') {
    unset($_SESSION['cart'][$id]);
  }
  header('Location: cart-confirm.php');
  exit;
}

$cart = $_SESSION['cart'] ?? [];
$total = 0;
foreach ($cart as $item) {
  $total += $item['price'] * $item['quantity'];
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <title>カート - SATONOMI</title>
</head>
<body>

<?php require 'header.php'; ?>

<main id="cart">
  <?php if (empty($cart)): ?>
    <p>カートに商品はありません。</p>
  <?php else: ?>
    <?php foreach ($cart as $item): ?>
      <div>
        <img src="<?= htmlspecialchars($item['image']) ?>" alt="<?= htmlspecialchars($item['name']) ?>" width="80">
        <div>
          <p><?= htmlspecialchars($item['name']) ?></p>
          <p>￥<?= number_format($item['price'] * $item['quantity']) ?></p>
        </div>
        <div>
          <!-- ここだけ追加 -->
          <a href="?action=minus&id=<?= $item['id'] ?>">－</a>
          <span><?= $item['quantity'] ?></span>
          <a href="?action=plus&id=<?= $item['id'] ?>">＋</a>
          <a href="?action=delete&id=<?= $item['id'] ?>" style="color:red; margin-left:10px;">削除</a>
        </div>
      </div>
      <hr>
    <?php endforeach; ?>
  <?php endif; ?>
</main>

<footer>
  <p>合計 ￥<span id="total"><?= number_format($total) ?></span></p>
  <button id="confirm">購入確認へ進む</button>
</footer>

<script>
  document.getElementById("confirm").addEventListener("click", () => {
    alert("購入確認画面へ進みます。");
  });
</script>

</body>
</html>
