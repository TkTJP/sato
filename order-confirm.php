<?php
session_start();
require 'db-connect.php';
$pdo = new PDO($connect, USER, PASS);

if (!isset($_SESSION['customer'])) {
  echo 'ログインしてください。';
  exit;
}

$customer_id = $_SESSION['customer']['customer_id'];

// カートの内容を取得
$sql = '
SELECT p.product_id, p.name, p.price, p.image, c.quantity
FROM carts c
JOIN products p ON c.product_id = p.product_id
WHERE c.customer_id = ?
';
$stmt = $pdo->prepare($sql);
$stmt->execute([$customer_id]);
$cart = $stmt->fetchAll(PDO::FETCH_ASSOC);

$total = 0;
foreach ($cart as $item) {
  $total += $item['price'] * $item['quantity'];
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<title>注文内容の確認 - SATONOMI</title>
</head>
<body>

<?php require 'header.php'; ?>

<h2>注文内容の確認</h2>

<?php if (empty($cart)): ?>
  <p>カートが空です。</p>
<?php else: ?>
  <?php foreach ($cart as $item): ?>
    <div>
      <img src="img/<?= htmlspecialchars($item['image']) ?>" alt="<?= htmlspecialchars($item['name']) ?>" width="80">
      <p><?= htmlspecialchars($item['name']) ?> × <?= $item['quantity'] ?></p>
      <p>¥<?= number_format($item['price'] * $item['quantity']) ?></p>
    </div>
  <?php endforeach; ?>

  <h3>合計金額：¥<?= number_format($total) ?></h3>

  <form action="order-complete.php" method="post">
    <input type="hidden" name="total" value="<?= $total ?>">
    <label><input type="radio" name="payment" value="card" required> クレジットカード</label><br>
    <label><input type="radio" name="payment" value="conveni"> コンビニ決済</label><br>
    <label><input type="radio" name="payment" value="paypay"> PayPay</label><br>
    <button type="submit">注文を確定する</button>
  </form>
<?php endif; ?>

</body>
</html>
