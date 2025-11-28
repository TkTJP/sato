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

// 🔹 検索キーワードの取得
$keyword = $_GET['keyword'] ?? '';

try {
    $stmt = $pdo->query("
        SELECT p.*, d.product_explain, 
            COUNT(l.product_id) AS like_count
        FROM products p
        LEFT JOIN product_details d 
            ON p.product_id = d.product_id
        LEFT JOIN likes l
            ON p.product_id = l.product_id
        GROUP BY p.product_id
        ORDER BY like_count DESC
        LIMIT 3
    ");
    $favorites = $stmt->fetchAll();
} catch (PDOException $e) {
    exit('人気商品取得エラー: ' . htmlspecialchars($e->getMessage()));
}

// 🔹 商品一覧取得（検索のみ対応）
$sql = "
    SELECT p.*, d.product_explain
    FROM products p
    LEFT JOIN product_details d ON p.product_id = d.product_id
    WHERE 1
";

$params = [];

if (!empty($keyword)) {
    $sql .= " AND (p.name LIKE ? OR d.product_explain LIKE ?)";
    $params[] = "%$keyword%";
    $params[] = "%$keyword%";
}

$sql .= " ORDER BY p.created_at DESC";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $products = $stmt->fetchAll();
} catch (PDOException $e) {
    exit('商品取得エラー: ' . htmlspecialchars($e->getMessage()));
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>TOP | SATONOMI</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="product_list.css">
</head>
<body class="page-with-nav">

<?php require 'header.php'; ?>

<!-- 🔍 検索フォーム -->
<div class="search-box">
  <form action="" method="get">
    <input type="text" name="keyword" placeholder="SATONOMIで探す"
           value="<?php echo htmlspecialchars($keyword); ?>">
    <button type="submit">検索</button>
  </form>
</div>


<!-- ⭐ 人気ランキング -->
<div class="ranking-area">
    <h3 class="ranking-title">人気ランキング</h3>

    <div class="ranking-list">

    <?php if (!empty($favorites)): ?>
        <?php $rank = 1; foreach ($favorites as $f): ?>
        <div class="ranking-card">
            <div class="rank-badge"><?php echo $rank; ?></div>

            <a href="product_detail.php?id=<?php echo urlencode($f['product_id']); ?>">
                <img src="img/<?php echo htmlspecialchars($f['image'] ?: 'noimage.png'); ?>"
                     alt="<?php echo htmlspecialchars($f['name']); ?>" width="100">
            </a>

            <div class="ranking-name">
                <?php echo htmlspecialchars($f['name']); ?>
            </div>

            <div class="ranking-price">
                ¥<?php echo number_format($f['price']); ?>
            </div>

            <div class="ranking-explain">
                <?php echo htmlspecialchars($f['product_explain']); ?>
            </div>
        </div>

        <?php $rank++; endforeach; ?>

    <?php else: ?>
        <p>人気商品はありません。</p>
    <?php endif; ?>

    </div>
</div>


<!-- 🗺️ 名産マップ -->
<div class="map-area">
    <a href="nihonntizu.php" class="map-btn">名産マップを見てみよう！</a>
</div>


<!-- 🛒 商品一覧 -->
<h2 class="section-title">商品一覧</h2>

<div class="product-list">

<?php if (!empty($products)): ?>
    <?php foreach ($products as $p): ?>

    <div class="product-card">

        <a href="product_detail.php?id=<?php echo urlencode($p['product_id']); ?>">
            <img src="img/<?php echo htmlspecialchars($p['image'] ?: 'noimage.png'); ?>"
                 alt="<?php echo htmlspecialchars($p['name']); ?>" width="200">
        </a>

        <div class="product-name">
            <?php echo htmlspecialchars($p['name']); ?>
        </div>

        <div class="product-price">
            ¥<?php echo number_format($p['price']); ?>
        </div>

    </div>

    <?php endforeach; ?>

<?php else: ?>
    <p class="no-result">該当する商品がありません。</p>
<?php endif; ?>

</div>

</body>
</html>