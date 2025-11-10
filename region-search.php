<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
date_default_timezone_set('Asia/Tokyo');

require_once __DIR__ . '/db-connect.php';

try {
    $pdo = new PDO($connect, USER, PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    exit('DB接続エラー: ' . htmlspecialchars($e->getMessage()));
}

// -----------------------------
// URLパラメータで地域取得
// -----------------------------
$prefecture = $_GET['prefecture'] ?? '';
if ($prefecture === '') {
    exit('都道府県が指定されていません。');
}

// -----------------------------
// 指定地域の商品を取得
// -----------------------------
$sql = "
    SELECT p.*
    FROM products p
    WHERE p.region = ?
    ORDER BY p.created_at DESC
";
try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$prefecture]);
    $products = $stmt->fetchAll();
} catch (PDOException $e) {
    exit('商品取得エラー: ' . htmlspecialchars($e->getMessage()));
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($prefecture); ?>エリア | SATONOMI</title>
    <style>
        body {
            font-family: "Hiragino Kaku Gothic ProN", sans-serif;
            margin: 0;
            background-color: #fafafa;
        }
        .header-bar {
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            padding: 20px 0;
            border-bottom: 1px solid #ddd;
            background: #fff;
        }
        .back-arrow {
            position: absolute;
            left: 20px;
            font-size: 1.5em;
            text-decoration: none;
            color: #0078d7;
        }
        .back-arrow:hover {
            text-decoration: underline;
        }
        .area-title {
            font-size: 1.4em;
            color: #333;
            text-align: center;
            margin: 0;
        }
        .product-list {
            text-align: center;
            margin: 30px auto;
            max-width: 800px;
        }
        .product-item {
            margin-bottom: 25px;
        }
        .product-item img {
            width: 200px;
            height: 150px;
            object-fit: contain;
        }
    </style>
</head>
<body>

<?php require 'header.php'; ?>

<!-- 🔹 上部タイトルバー -->
<div class="header-bar">
    <a href="map.php" class="back-arrow">←</a>
    <h1 class="area-title"><?php echo htmlspecialchars($prefecture); ?>エリア</h1>
</div>

<!-- 🛒 商品一覧 -->
<div class="product-list">
<?php if (!empty($products)): ?>
    <?php foreach ($products as $p): ?>
        <div class="product-item">
            <a href="product_detail.php?id=<?php echo urlencode($p['product_id']); ?>">
                <img src="img/<?php echo htmlspecialchars($p['image'] ?: 'noimage.png'); ?>" 
                     alt="<?php echo htmlspecialchars($p['name']); ?>"><br>
                <?php echo htmlspecialchars($p['name']); ?><br>
                ¥<?php echo number_format($p['price']); ?>
            </a>
        </div>
        <hr style="max-width: 300px; margin: 10px auto;">
    <?php endforeach; ?>
<?php else: ?>
    <p>該当する商品がありません。</p>
<?php endif; ?>
</div>

</body>
</html>
