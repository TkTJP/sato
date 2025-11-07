<?php
require 'db-connect.php';
session_start();

if (!isset($_GET['product_id'])) {
    echo "商品が指定されていません。";
    exit;
}

$product_id = $_GET['product_id'];

$sql = $pdo->prepare('SELECT * FROM product WHERE product_id = ?');
$sql->execute([$product_id]);
$product = $sql->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    echo "該当する商品が見つかりません。";
    exit;
}
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($product['name']); ?></title>
</head>
<body>
    <h1><?php echo htmlspecialchars($product['name']); ?></h1>
    <p>商品説明：<?php echo htmlspecialchars($product['description']); ?></p>
    <p>価格：<?php echo htmlspecialchars($product['price']); ?>円</p>
    <p>在庫数：<?php echo htmlspecialchars($product['stock']); ?></p>
    <img src="<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" width="300">

    <form action="cart-insert.php" method="post">
        <input type="hidden" name="product_id" value="<?php echo $product['product_id']; ?>">
        <label>数量：<input type="number" name="count" value="1" min="1" max="<?php echo $product['stock']; ?>"></label>
        <input type="submit" value="カートに入れる">
    </form>

    <p><a href="index.php">一覧に戻る</a></p>
</body>
</html>
