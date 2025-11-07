<?php
require 'db-connect.php';
$pdo = new PDO($connect, USER, PASS, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
]);

// 🔹 検索キーワード取得
$keyword = $_GET['keyword'] ?? '';
$params = [];
$sql = "SELECT * FROM products WHERE 1";

// 🔹 検索ワードがある場合
if (!empty($keyword)) {
    $sql .= " AND (name LIKE ? OR description LIKE ?)";
    $params[] = "%{$keyword}%";
    $params[] = "%{$keyword}%";
}

// 🔹 新着順に並べる
$sql .= " ORDER BY created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<title>商品一覧</title>
<link rel="stylesheet" href="style.css">
</head>
<body>

<?php require 'header.php'; ?>

<div class="container">
    <h1>商品一覧</h1>

    <!-- 🔍 検索フォーム -->
    <div class="search-box">
        <form method="get">
            <input type="text" name="keyword" placeholder="商品名または説明で検索" value="<?= htmlspecialchars($keyword) ?>">
            <button type="submit">検索</button>
        </form>
    </div>

    <!-- 🧃 商品一覧 -->
    <div class="products">
        <?php if ($products): ?>
            <?php foreach ($products as $p): ?>
                <div class="product-card">
                    <a href="product_detail.php?id=<?= $p['product_id'] ?>">
                        <img src="img/<?= htmlspecialchars($p['image'] ?: 'noimage.png') ?>" alt="<?= htmlspecialchars($p['name']) ?>">
                    </a>
                    <h3><?= htmlspecialchars($p['name']) ?></h3>
                    <p><?= htmlspecialchars($p['description']) ?></p>
                    <p class="price">¥<?= number_format($p['price']) ?></p>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>該当する商品がありません。</p>
        <?php endif; ?>
    </div>
</div>

</body>
</html>
