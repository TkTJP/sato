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

<?php require 'header.php'; ?>

<!-- 検索フォーム -->
<div>
  <form action="" method="get">
    <input type="text" name="keyword" placeholder="商品名または説明で検索" value="<?php echo htmlspecialchars($keyword); ?>">
    <button type="submit">検索</button>
  </form>
</div>

<!-- 人気ランキング -->
<h3>人気ランキング</h3>
<div>
<?php if (!empty($favorites)): ?>
    <?php $rank = 1; foreach ($favorites as $f): ?>
        <div>
            <strong><?php echo $rank; ?>位</strong><br>
            <!-- 🔹 リンク先修正：product_detail.php -->
            <a href="product_detail.php?id=<?php echo urlencode($f['product_id']); ?>">
                <img src="img/<?php echo htmlspecialchars($f['image'] ?: 'noimage.png'); ?>" 
                     alt="<?php echo htmlspecialchars($f['name']); ?>" width="150"><br>
                <?php echo htmlspecialchars($f['name']); ?>
            </a><br>
            ¥<?php echo number_format($f['price']); ?>
        </div>
        <hr>
    <?php $rank++; endforeach; ?>
<?php else: ?>
    <p>人気商品はありません</p>
<?php endif; ?>
</div>

<!-- 名産マップボタン -->
<div>
    <a href="map.php">名産マップを見てみよう！</a>
</div>

<!-- 商品一覧タイトル -->
<h2>商品一覧</h2>

<!-- 絞り込みボタン -->
<div onclick="toggleFilter()" style="cursor:pointer;">絞り込み ▼</div>

<!-- 絞り込みフォーム -->
<div id="filterBox" style="display:none;">
    <form action="" method="get">
        <input type="hidden" name="keyword" value="<?php echo htmlspecialchars($keyword); ?>">
        <?php
        $filterOptions = ['ソフトドリンク','炭酸飲料','ノーラベル','地方'];
        foreach($filterOptions as $opt):
        ?>
            <label>
                <input type="checkbox" name="filter[]" value="<?php echo $opt; ?>" 
                       <?php if(in_array($opt,$filters)) echo 'checked'; ?>>
                <?php echo $opt; ?>
            </label>
        <?php endforeach; ?>
        <button type="submit">絞り込み</button>
    </form>
</div>

<!-- 商品一覧 -->
<div>
<?php if (!empty($products)): ?>
    <?php foreach ($products as $p): ?>
        <div>
            <!-- 🔹 リンク先修正：確実にproduct_detail.phpに接続 -->
            <a href="product_detail.php?id=<?php echo urlencode($p['product_id']); ?>">
                <img src="img/<?php echo htmlspecialchars($p['image'] ?: 'noimage.png'); ?>" 
                     alt="<?php echo htmlspecialchars($p['name']); ?>" width="150"><br>
                <?php echo htmlspecialchars($p['name']); ?>
            </a><br>
            ¥<?php echo number_format($p['price']); ?>
        </div>
        <hr>
    <?php endforeach; ?>
<?php else: ?>
    <p>該当する商品がありません。</p>
<?php endif; ?>
</div>

</body>
</html>
