<?php
session_start();

// --- product_detail から追加されたとき ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
  $id = (string)$_POST['id'];
  if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];

  if (isset($_SESSION['cart'][$id])) {
    $_SESSION['cart'][$id]['quantity']++;
  } else {
    $_SESSION['cart'][$id] = [
      'id' => $id,
      'name' => $_POST['name'],
      'price' => (int)$_POST['price'],
      'image' => $_POST['image'],
      'quantity' => 1
    ];
  }
  // 二重追加防止（POST→GETリダイレクト）
  header('Location: cart-confirm.php');
  exit;
}

// --- カート内で数量変更や削除 ---
if (isset($_GET['action']) && isset($_GET['id'])) {
  $id = (string)$_GET['id'];

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

// --- 表示処理 ---
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
          <a href="?action=minus&id=<?= urlencode($item['id']) ?>">－</a>
          <span><?= $item['quantity'] ?></span>
          <a href="?action=plus&id=<?= urlencode($item['id']) ?>">＋</a>
          <a href="?action=delete&id=<?= urlencode($item['id']) ?>" style="color:red; margin-left:10px;">削除</a>
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
