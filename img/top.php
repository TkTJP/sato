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
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bulma@0.9.3/css/bulma.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<style>
body { font-family: sans-serif; margin:0; padding:0; }

/* 🔹 検索フォーム */
.search-section { background-color: #99EACA; padding: 20px 0; }
.search-box { display:flex; justify-content:center; align-items:center; flex-wrap:wrap; }
.search-box input[type="text"] {
    width: 500px; padding: 8px 12px; font-size: 16px;
    border-radius:5px 0 0 5px; border:1px solid #ccc; outline:none;
}
.search-box button {
    padding: 8px 12px; font-size:16px; border:1px solid #ccc;
    border-left:none; border-radius:0 5px 5px 0; background-color:white; cursor:pointer;
}
.search-box button:hover { background-color:#f0f0f0; }

/* 🔹 人気ランキング（横スクロール） */
.favorites-section {
    padding: 20px;
    background-color: #ffeeba;
    margin-top: 20px;
}
.favorites-section h3 { text-align:center; margin-bottom:15px; }
.favorites-list {
    display:flex;
    overflow-x:auto;
    gap:15px;
    padding-bottom:10px;
}
.favorites-list::-webkit-scrollbar { height:8px; }
.favorites-list::-webkit-scrollbar-thumb { background:#ccc; border-radius:4px; }
.favorites-list .fav-item {
    position: relative;
    flex: 0 0 200px; 
    text-align:center;
    border:1px solid #ccc; border-radius:8px; padding:5px; background:#fff;
}
.favorites-list .fav-item img {
    width: 100%;
    height: 200px;
    object-fit: contain;
    background-color: #f5f5f5;
}
.rank-badge {
    position: absolute;
    top: 8px;
    left: 8px;
    background: #ff4757;
    color: #fff;
    font-weight: bold;
    padding: 4px 6px;
    border-radius: 4px;
    font-size: 14px;
    z-index: 10;
}

/* 🔹 名産マップボタン */
.map-button-container {
    display:flex;
    justify-content:center;
    margin: 20px 0;
}
.map-button {
    display:inline-block;
    width:60%;
    padding:12px 0;
    text-align:center;
    font-size:18px;
    color:#FF7F50;
    border:2px solid #FF7F50;
    background-color:#fff;
    border-radius:25px;
    text-decoration:none;
    transition:0.3s;
}
.map-button:hover {
    background-color:#FF7F50;
    color:#fff;
}

/* 🔹 商品一覧タイトル */
.section-title { font-size:22px; text-align:center; margin-top:30px; }

/* 🔹 絞り込みアイコン（3本線＋丸） */
.filter-toggle {
    display:flex;
    justify-content:center;
    align-items:center;
    flex-direction:column;
    cursor:pointer;
    margin-top:10px;
}
.filter-toggle div {
    width:25px;
    height:3px;
    background:black;
    margin:3px 0;
    border-radius:2px;
    position:relative;
}
.filter-toggle div::after {
    content:"";
    position:absolute;
    right:-8px;
    top:-3px;
    width:8px;
    height:8px;
    border-radius:50%;
    background:black;
}

/* 🔹 絞り込みフォーム */
.filter-box {
    display:none;
    justify-content:center;
    gap:20px;
    flex-wrap:wrap;
    margin:15px 0;
}
.filter-box form {
    display:flex;
    justify-content:center;
    align-items:center;
    flex-wrap:wrap;
    gap:20px;
}
.filter-box label { font-size:15px; display:flex; align-items:center; gap:5px; }
.filter-box button {
    padding:5px 12px; border:none; border-radius:5px; background:#99EACA;
    cursor:pointer; font-size:15px;
}
.filter-box button:hover { background:#7fc8ba; }

/* 🔹 商品一覧 */
.container { display:flex; flex-wrap:wrap; justify-content:center; margin-top:10px; }
.product {
    width:30%; box-sizing:border-box; border:1px solid #ccc;
    margin:10px; padding:10px; text-align:center; border-radius:10px;
    box-shadow:0 2px 4px rgba(0,0,0,0.1); background-color:#fff;
    display:flex; flex-direction:column; align-items:center;
}
.product img { width:100%; height:200px; object-fit:contain; background-color:#f5f5f5; }
.product-text { display:flex; flex-direction:column; justify-content:space-between; min-height:80px; width:100%; }
.product-name { font-size:18px; margin-top:10px; text-align:center; }
.price { color:#e60000; font-weight:bold; margin-bottom:5px; }

/* 🔹 レスポンシブ対応 */
@media screen and (max-width: 1024px) {
    .product { width:45%; }
    .search-box input[type="text"] { width:70%; }
}
@media screen and (max-width: 768px) {
    .product { width:80%; }
    .search-box input[type="text"] { width:80%; }
    .map-button { width:90%; font-size:16px; }
}
@media screen and (max-width: 480px) {
    .product { width:95%; }
    .search-box input[type="text"] { width:90%; }
    .map-button { width:95%; font-size:14px; padding:10px 0; }
}
</style>
<script>
function toggleFilter() {
    const box = document.getElementById('filterBox');
    box.style.display = (box.style.display === 'flex') ? 'none' : 'flex';
}
</script>
</head>
<body>

<?php require 'header.php'; ?>

<!-- 🔹 検索フォーム -->
<div class="search-section">
  <div class="search-box">
    <form action="" method="get">
      <input type="text" name="keyword" placeholder="商品名または説明で検索" value="<?php echo htmlspecialchars($keyword); ?>">
      <button type="submit"><i class="fas fa-magnifying-glass"></i></button>
    </form>
  </div>
</div>

<!-- 🔹 人気ランキング -->
<div class="favorites-section">
    <h3>人気ランキング</h3>
    <div class="favorites-list">
    <?php if (!empty($favorites)): ?>
        <?php $rank = 1; foreach ($favorites as $f): ?>
            <div class="fav-item">
                <div class="rank-badge"><?php echo $rank; ?>位</div>
                <img src="img/<?php echo htmlspecialchars($f['image'] ?: 'noimage.png'); ?>" alt="<?php echo htmlspecialchars($f['name']); ?>">
                <div style="font-size:14px; margin-top:5px;"><?php echo htmlspecialchars($f['name']); ?></div>
                <div style="color:#e60000; font-weight:bold;">¥<?php echo number_format($f['price']); ?></div>
            </div>
        <?php $rank++; endforeach; ?>
    <?php else: ?>
        <p style="text-align:center; width:100%;">人気商品はありません</p>
    <?php endif; ?>
    </div>
</div>

<!-- 🔹 名産マップボタン -->
<div class="map-button-container">
    <a href="map.php" class="map-button">名産マップを見てみよう！</a>
</div>

<!-- 🔹 商品一覧タイトル -->
<h2 class="section-title">商品一覧</h2>

<!-- 🔹 絞り込みボタン -->
<div class="filter-toggle" onclick="toggleFilter()">
    <div></div>
    <div></div>
    <div></div>
</div>

<!-- 🔹 絞り込みフォーム -->
<div class="filter-box" id="filterBox">
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

<!-- 🔹 商品一覧 -->
<div class="container">
<?php if (!empty($products)): ?>
    <?php foreach ($products as $p): ?>
        <div class="product">
            <img src="img/<?php echo htmlspecialchars($p['image'] ?: 'noimage.png'); ?>" alt="<?php echo htmlspecialchars($p['name']); ?>">
            <div class="product-text">
                <div class="product-name"><?php echo htmlspecialchars($p['name']); ?></div>
                <div class="price">¥<?php echo number_format($p['price']); ?></div>
            </div>
        </div>
    <?php endforeach; ?>
<?php else: ?>
    <p style="text-align:center; width:100%;">該当する商品がありません。</p>
<?php endif; ?>
</div>

</body>
</html>
