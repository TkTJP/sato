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

// 商品一覧取得
try {
    $stmt = $pdo->query("SELECT * FROM products ORDER BY created_at DESC");
    $products = $stmt->fetchAll();
} catch (PDOException $e) {
    exit('商品取得エラー: ' . htmlspecialchars($e->getMessage()));
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>商品一覧 | SATONOMI</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<!-- ヘッダー -->
<?php include 'header.php'; ?>

<main class="mypage-container">
    <div class="mypage-header">
        <button class="back-button" onclick="history.back()">←</button>
        商品一覧
    </div>

    <section class="profile">
        <h2>全国のご当地ドリンク</h2>
        <p>各地のユニークな飲み物をお楽しみください！</p>
    </section>

    <section class="menu">
        <?php if ($products): ?>
            <?php foreach ($products as $p): ?>
                <div class="product-card" style="width:80%; background:#fff; border-radius:10px; padding:15px; margin:10px 0; box-shadow:0 2px 4px rgba(0,0,0,0.1);">
                    <a href="product_detail.php?id=<?php echo htmlspecialchars($p['product_id']); ?>" style="text-decoration:none; color:#000;">
                        <div style="text-align:center;">
                            <img src="img/<?php echo htmlspecialchars($p['image'] ?: 'noimage.png'); ?>" alt="" style="width:100%; max-width:200px; border-radius:10px;">
                        </div>
                        <h3 style="margin-top:10px; font-size:18px;"><?php echo htmlspecialchars($p['name']); ?></h3>
                        <p style="color:#555;"><?php echo htmlspecialchars($p['description']); ?></p>
                        <p style="color:#e60033; font-weight:bold;">¥<?php echo number_format($p['price']); ?></p>
                    </a>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>現在、商品は登録されていません。</p>
        <?php endif; ?>
    </section>
</main>

</body>
</html>
