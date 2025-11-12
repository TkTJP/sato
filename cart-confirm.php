<?php
session_start();
require 'db-connect.php';
$pdo = new PDO($connect, USER, PASS);

// --- 共通: ログイン状態を確認 ---
$logged_in = isset($_SESSION['customer']);
$customer_id = $logged_in ? $_SESSION['customer']['customer_id'] : null;

// --- 商品追加処理（共通） ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
  $product_id = (int)$_POST['id'];
  $name = $_POST['name'] ?? '';
  $price = (int)($_POST['price'] ?? 0);
  $image = $_POST['image'] ?? '';

  if ($logged_in) {
    // DBカート
    $check = $pdo->prepare('SELECT quantity FROM carts WHERE customer_id = ? AND product_id = ?');
    $check->execute([$customer_id, $product_id]);
    $existing = $check->fetch();

    if ($existing) {
      $pdo->prepare('UPDATE carts SET quantity = quantity + 1 WHERE customer_id = ? AND product_id = ?')
          ->execute([$customer_id, $product_id]);
    } else {
      $pdo->prepare('INSERT INTO carts (customer_id, product_id, quantity, added_at) VALUES (?, ?, 1, NOW())')
          ->execute([$customer_id, $product_id]);
    }
  } else {
    // セッションカート
    if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];
    if (isset($_SESSION['cart'][$product_id])) {
      $_SESSION['cart'][$product_id]['quantity']++;
    } else {
      $_SESSION['cart'][$product_id] = [
        'id' => $product_id,
        'name' => $name,
        'price' => $price,
        'image' => $image,
        'quantity' => 1
      ];
    }
  }

  header('Location: cart-confirm.php');
  exit;
}

// --- 数量変更や削除 ---
if (isset($_GET['action'], $_GET['id'])) {
  $product_id = (int)$_GET['id'];

  if ($logged_in) {
    if ($_GET['action'] === 'plus') {
      $pdo->prepare('UPDATE carts SET quantity = quantity + 1 WHERE customer_id = ? AND product_id = ?')
          ->execute([$customer_id, $product_id]);
    } elseif ($_GET['action'] === 'minus') {
      $stmt = $pdo->prepare('SELECT quantity FROM carts WHERE customer_id = ? AND product_id = ?');
      $stmt->execute([$customer_id, $product_id]);
      $qty = $stmt->fetchColumn();
      if ($qty > 1) {
        $pdo->prepare('UPDATE carts SET quantity = quantity - 1 WHERE customer_id = ? AND product_id = ?')
            ->execute([$customer_id, $product_id]);
      } else {
        $pdo->prepare('DELETE FROM carts WHERE customer_id = ? AND product_id = ?')
            ->execute([$customer_id, $product_id]);
      }
    } elseif ($_GET['action'] === 'delete') {
      $pdo->prepare('DELETE FROM carts WHERE customer_id = ? AND product_id = ?')
          ->execute([$customer_id, $product_id]);
    }
  } else {
    if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];
    if ($_GET['action'] === 'plus') {
      $_SESSION['cart'][$product_id]['quantity']++;
    } elseif ($_GET['action'] === 'minus') {
      if ($_SESSION['cart'][$product_id]['quantity'] > 1) {
        $_SESSION['cart'][$product_id]['quantity']--;
      } else {
        unset($_SESSION['cart'][$product_id]);
      }
    } elseif ($_GET['action'] === 'delete') {
      unset($_SESSION['cart'][$product_id]);
    }
  }

  header('Location: cart-confirm.php');
  exit;
}

// --- 表示用データ取得 ---
if ($logged_in) {
  $sql = '
    SELECT p.product_id, p.name, p.price, p.image, c.quantity
    FROM carts c
    JOIN products p ON c.product_id = p.product_id
    WHERE c.customer_id = ?
  ';
  $stmt = $pdo->prepare($sql);
  $stmt->execute([$customer_id]);
  $cart = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
  $cart = $_SESSION['cart'] ?? [];
}

// --- 合計計算 ---
$total = 0;
foreach ($cart as $item) {
  $price = $logged_in ? $item['price'] : $item['price'];
  $qty = $logged_in ? $item['quantity'] : $item['quantity'];
  $total += $price * $qty;
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

<h2>カート</h2>

<?php if (empty($cart)): ?>
  <p>カートに商品はありません。</p>
<?php else: ?>
  <?php foreach ($cart as $item): ?>
    <?php
      $id = $logged_in ? $item['product_id'] : $item['id'];
      $name = htmlspecialchars($item['name']);
      $img = htmlspecialchars($item['image']);
      $qty = $item['quantity'];
      $subtotal = $item['price'] * $qty;
    ?>
    <div>
      <img src="img/<?= $img ?>" alt="<?= $name ?>" width="80">
      <p><?= $name ?></p>
      <p>¥<?= number_format($subtotal) ?></p>
      <a href="?action=minus&id=<?= urlencode($id) ?>">－</a>
      <span><?= $qty ?></span>
      <a href="?action=plus&id=<?= urlencode($id) ?>">＋</a>
      <a href="?action=delete&id=<?= urlencode($id) ?>" style="color:red;">削除</a>
      <hr>
    </div>
  <?php endforeach; ?>

  <p>合計 ¥<?= number_format($total) ?></p>

  <?php if ($logged_in): ?>
    <form action="order-confirm.php" method="post">
      <input type="hidden" name="total" value="<?= $total ?>">
      <button type="submit">購入確認へ進む</button>
    </form>
  <?php else: ?>
    <p><a href="login.php">ログインして購入に進む</a></p>
  <?php endif; ?>
<?php endif; ?>

</body>
</html>
