<?php
require 'db-connect.php';
$pdo = new PDO($connect, USER, PASS);

// 🔹 人気商品取得（最大5件）
$favStmt = $pdo->query("
    SELECT p.* 
    FROM products p
    JOIN product_details pd ON p.product_id = pd.product_id
    WHERE pd.product_explain LIKE '%人気%'
    ORDER BY p.created_at DESC
    LIMIT 5
");
$favorites = $favStmt->fetchAll(PDO::FETCH_ASSOC);

// 🔹 検索・絞り込み処理
$keyword = $_GET['keyword'] ?? '';
$filters = $_GET['filter'] ?? [];
if (!is_array($filters)) $filters = [$filters];

$sql = 'SELECT * FROM products WHERE 1';
$params = [];

if ($keyword !== '') {
    $sql .= ' AND (name LIKE ? OR description LIKE ?)';
    $params[] = '%' . $keyword . '%';
    $params[] = '%' . $keyword . '%';
}

if (!empty($filters)) {
    $sql .= ' AND (' . implode(' OR ', array_fill(0, count($filters), 'name LIKE ?')) . ')';
    foreach ($filters as $f) $params[] = '%' . $f . '%';
}

$sql .= ' ORDER BY created_at DESC';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<title>商品一覧</title>
<script>
function toggleFilter() {
    const box = document.getElementById('filterBox');
    box.style.display = (box.style.display === 'block') ? 'none' : 'block';
}
</script>
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
            <img src="img/<?php echo htmlspecialchars($f['image'] ?: 'noimage.png'); ?>" alt="<?php echo htmlspecialchars($f['name']); ?>" width="150"><br>
            <?php echo htmlspecialchars($f['name']); ?><br>
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
                <input type="checkbox" name="filter[]" value="<?php echo $opt; ?>" <?php if(in_array($opt,$filters)) echo 'checked'; ?>>
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
            <img src="img/<?php echo htmlspecialchars($p['image'] ?: 'noimage.png'); ?>" alt="<?php echo htmlspecialchars($p['name']); ?>" width="150"><br>
            <?php echo htmlspecialchars($p['name']); ?><br>
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
