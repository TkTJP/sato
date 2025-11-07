<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require 'db-connect.php';
$pdo = new PDO($connect, USER, PASS);

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo '不正なアクセスです。';
    exit;
}

$product_id = (int)$_GET['id'];

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
<link rel="stylesheet" href="style.css">
</head>
<body>

<?php require 'header.php'; ?>

<h2>商品詳細ページ</h2>

<img src="img/<?php echo htmlspecialchars($product['image'] ?: 'noimage.png'); ?>" 
     alt="<?php echo htmlspecialchars($product['name']); ?>" width="250"><br>

<h3><?php echo htmlspecialchars($product['name']); ?></h3>
<p><?php echo htmlspecialchars($product['description']); ?></p>
<p>価格：¥<?php echo number_format($product['price']); ?></p>
<p>在庫数：<?php echo htmlspecialchars($product['stock']); ?></p>

<form action="cart_add.php" method="post">
    <input type="hidden" name="product_id" value="<?php echo $product['product_id']; ?>">
    <input type="number" name="count" value="1" min="1" max="<?php echo $product['stock']; ?>">
    <input type="submit" value="カートに入れる">
</form>

<a href="top.php">← 商品一覧に戻る</a>

</body>
</html>
