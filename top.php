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

// 🔹 検索キーワードと絞り込み条件の取得
$keyword = $_GET['keyword'] ?? '';
$filters = $_GET['filter'] ?? [];

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

// 🔹 商品一覧取得（絞り込み + 検索対応）
$sql = "
    SELECT p.*, d.product_explain
    FROM products p
    LEFT JOIN product_details d ON p.product_id = d.product_id
    WHERE 1
";

$params = [];

if (!empty($keyword)) {
    $sql .= " AND (p.name LIKE ? OR p.description LIKE ? OR d.product_explain LIKE ?)";
    $params[] = "%$keyword%";
    $params[] = "%$keyword%";
    $params[] = "%$keyword%";
}

if (!empty($filters)) {
    foreach ($filters as $f) {
        $sql .= " AND d.product_explain LIKE ?";
        $params[] = "%$f%";
    }
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
    <title>商品一覧 | SATONOMI</title>
    <script>
    function toggleFilter() {
        const box = document.getElementById('filterBox');
        box.style.display = (box.style.display === 'none') ? 'block' : 'none';
    }
    </script>
</head>
<body>

<?php require 'header.php'; ?>

<!-- 🔍 検索フォーム -->
<div>
  <form action="" method="get">
    <input type="text" name="keyword" placeholder="商品名または説明で検索" value="<?php echo htmlspecialchars($keyword); ?>">
    <button type="submit">検索</button>
  </form>
</div>

<hr>

<!-- ⭐ 人気ランキング -->
<h3>人気ランキング</h3>
<div>
<?php if (!empty($favorites)): ?>
    <?php $rank = 1; foreach ($favorites as $f): ?>
        <div>
            <strong><?php echo $rank; ?>位</strong><br>
            <a href="product_detail.php?id=<?php echo urlencode($f['product_id']); ?>">
                <img src="img/<?php echo htmlspecialchars($f['image'] ?: 'noimage.png'); ?>" 
                     alt="<?php echo htmlspecialchars($f['name']); ?>" width="150"><br>
                <?php echo htmlspecialchars($f['name']); ?>
            </a><br>
            ¥<?php echo number_format($f['price']); ?><br>
            <small><?php echo htmlspecialchars($f['product_explain']); ?></small>
        </div>
        <hr>
    <?php $rank++; endforeach; ?>
<?php else: ?>
    <p>人気商品はありません。</p>
<?php endif; ?>
</div>

<hr>

<!-- 🗺️ 名産マップ -->
<div>
    <a href="map.php">名産マップを見てみよう！</a>
</div>

<hr>

<!-- 🛒 商品一覧 -->
<h2>商品一覧</h2>

<!-- 🔽 絞り込みボタン -->
<div onclick="toggleFilter()" style="cursor:pointer;">絞り込み ▼</div>

<!-- フィルタフォーム -->
<div id="filterBox" style="display:none;">
    <form action="" method="get">
        <input type="hidden" name="keyword" value="<?php echo htmlspecialchars($keyword); ?>">
        <?php
        $filterOptions = ['ソフトドリンク','炭酸飲料','ノーラベル','地方'];
        foreach($filterOptions as $opt):
        ?>
            <label>
                <input type="checkbox" name="filter[]" value="<?php echo $opt; ?>" 
                       <?php if(in_array($opt, $filters)) echo 'checked'; ?>>
                <?php echo $opt; ?>
            </label>
        <?php endforeach; ?>
        <button type="submit">絞り込み</button>
    </form>
</div>

<hr>

<!-- 商品一覧 -->
<div>
<?php if (!empty($products)): ?>
    <?php foreach ($products as $p): ?>
        <div>
            <a href="product_detail.php?id=<?php echo urlencode($p['product_id']); ?>">
                <img src="img/<?php echo htmlspecialchars($p['image'] ?: 'noimage.png'); ?>" 
                     alt="<?php echo htmlspecialchars($p['name']); ?>" width="150"><br>
                <?php echo htmlspecialchars($p['name']); ?>
            </a><br>
            ¥<?php echo number_format($p['price']); ?><br>
            <small><?php echo htmlspecialchars($p['product_explain']); ?></small>
        </div>
        <hr>
    <?php endforeach; ?>
<?php else: ?>
    <p>該当する商品がありません。</p>
<?php endif; ?>
</div>

</body>
</html>
