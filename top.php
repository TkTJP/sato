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

// 🔹 人気商品（product_details の product_explain に「人気」が含まれる商品）
try {
    $stmt = $pdo->query("
        SELECT p.*, d.product_explain 
        FROM products p
        JOIN product_details d ON p.product_id = d.product_id
        WHERE d.product_explain LIKE '%人気%'
        ORDER BY p.created_at DESC
        LIMIT 5
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>トップページ</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>

<?php include 'header.php'; ?>

<div id="app" class="page-container">

    <main>
        <section class="ranking-section">
            <div class="ranking-container">
                <h2 class="section-title">人気ランキング</h2>
                <ul class="ranking-list">
                    <?php foreach ($ranking_products as $rank => $product): ?>
                        <li class="ranking-item">
                            <a href="product_detail.php?id=<?= htmlspecialchars($product['id']) ?>" class="product-link">
                                <div class="ranking-number">No.<?= $rank + 1 ?></div>
                                <img src="img/<?= htmlspecialchars($product['image']) ?>" alt="<?= htmlspecialchars($product['name']) ?>">
                                <div class="product-info">
                                    <h3><?= htmlspecialchars($product['name']) ?></h3>
                                    <p class="price">¥<?= number_format($product['price']) ?></p>
                                </div>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </section>

        <!-- 🔍 検索フォーム -->
        <section class="search-section">
            <h2 class="section-title">商品検索</h2>

            <form action="" method="get" class="search-form">
                <input type="text" name="keyword" placeholder="商品名で検索" class="search-input"
                       value="<?= htmlspecialchars($search_keyword ?? '') ?>">
                <button type="submit" class="search-button">検索</button>
            </form>

            <!-- 検索結果表示 -->
            <?php if (!empty($search_keyword)): ?>
                <p class="search-result">
                    「<?= htmlspecialchars($search_keyword) ?>」の検索結果：<?= count($products) ?>件
                </p>
            <?php endif; ?>
        </section>

        <!-- 🛍 商品一覧 -->
        <section class="product-section">
            <h2 class="section-title">商品一覧</h2>

            <div class="products-container">
                <?php foreach ($products as $product): ?>
                    <div class="product-card">
                        <a href="product_detail.php?id=<?= htmlspecialchars($product['id']) ?>" class="product-link">
                            <img src="img/<?= htmlspecialchars($product['image']) ?>" alt="<?= htmlspecialchars($product['name']) ?>">
                            <div class="product-info">
                                <h3><?= htmlspecialchars($product['name']) ?></h3>
                                <p class="kubun">区分：<?= htmlspecialchars($product['kubun']) ?></p>
                                <p class="price">¥<?= number_format($product['price']) ?></p>
                            </div>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>

        </section>

    </main>

</div>

</body>
</html>
