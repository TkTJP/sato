<?php
require_once __DIR__ . '/db-connect.php';
$pdo = new PDO($connect, USER, PASS, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);

$prefecture = $_GET['prefecture'] ?? '';
if ($prefecture === '') {
    exit('都道府県が指定されていません。');
}

if ($prefecture === '北海道以外') {
    $stmt = $pdo->query("SELECT * FROM products WHERE prefecture != '北海道' ORDER BY product_id DESC");
} else {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE prefecture = ? ORDER BY product_id DESC");
    $stmt->execute([$prefecture]);
}
$products = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($prefecture) ?>エリア | SATONOMI</title>
    <style>
        body {font-family: "Hiragino Kaku Gothic ProN", sans-serif; background: #fafafa; margin: 0;}
        .header-bar {display:flex; align-items:center; justify-content:center; padding:20px 0; background:#fff; border-bottom:1px solid #ddd; position:relative;}
        .back-arrow {position:absolute; left:20px; text-decoration:none; color:#0078d7; font-size:1.2em;}
        .product-list {text-align:center; margin:30px auto; max-width:800px;}
        .product-item {margin-bottom:25px;}
        .product-item img {width:200px; height:150px; object-fit:contain;}
    </style>
</head>
<body>
    <?php require 'header.php'; ?>
<div class="header-bar">
    <a href="map.php" class="back-arrow">← 戻る</a>
    <h1><?= htmlspecialchars($prefecture) ?>エリア</h1>
</div>

<div class="product-list">
<?php if (!empty($products)): ?>
    <?php foreach ($products as $p): ?>
        <div class="product-item">
            <a href="product_detail.php?id=<?= urlencode($p['product_id']) ?>">
                <img src="img/<?= htmlspecialchars($p['image'] ?: 'noimage.png') ?>" alt="<?= htmlspecialchars($p['name']) ?>"><br>
                <?= htmlspecialchars($p['name']) ?><br>
                ¥<?= number_format($p['price']) ?>
            </a>
        </div>
        <hr style="max-width:300px;margin:10px auto;">
    <?php endforeach; ?>
<?php else: ?>
    <p>該当する商品がありません。</p>
<?php endif; ?>
</div>
</body>
</html>
