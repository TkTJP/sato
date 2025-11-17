<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
date_default_timezone_set('Asia/Tokyo');

// Font Awesomeのアイコンを使用するため、
// CSSかheader.phpでFont Awesomeの読み込みが必要です。

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
    // LIMITを増やして、ランキング表示で横にスクロールできる余裕を持たせる（例: 8件）
    $stmt = $pdo->query("
        SELECT p.*, d.product_explain 
        FROM products p
        JOIN product_details d ON p.product_id = d.product_id
        WHERE d.product_explain LIKE '%人気%'
        ORDER BY p.created_at DESC
        LIMIT 8
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
    <title>商品一覧 | SATONOMI</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>

<div class="app-container">

<?php require 'header.php'; ?>

<div class="nav-bar">
    <form action="" method="get" class="search-form-flex">
        <i class="fas fa-search" style="color: #666; margin-right: 10px;"></i>
        <input type="text" name="keyword" placeholder="SATONOMIで探す" 
               value="<?php echo htmlspecialchars($keyword); ?>"
               class="search-input-clear">
        </form>
    <i class="fas fa-sliders-h" style="font-size: 20px; color: #333;"></i>
</div>

<div style="padding-top: 15px;">
    <div class="ranking-scroll">
    <?php if (!empty($favorites)): ?>
        <?php $rank = 1; foreach ($favorites as $f): ?>
            <div class="ranking-item">
                <div class="ranking-badge"><?php echo $rank; ?></div>
                
                <a href="product_detail.php?id=<?php echo urlencode($f['product_id']); ?>" style="text-decoration: none; color: #333;">
                    <img src="img/<?php echo htmlspecialchars($f['image'] ?: 'noimage.png'); ?>" 
                         alt="<?php echo htmlspecialchars($f['name']); ?>" 
                         width="80" style="height: 100px; object-fit: contain; display: block; margin: 5px auto;"><br>
                    
                    <small style="font-size: 10px; display: block; overflow: hidden; white-space: nowrap; text-overflow: ellipsis;"><?php echo htmlspecialchars($f['name']); ?></small>
                </a>
                <span style="font-size: 12px; font-weight: bold;">¥<?php echo number_format($f['price']); ?></span>
            </div>
        <?php $rank++; endforeach; ?>
    <?php else: ?>
        <p style="text-align: center;">人気商品はありません。</p>
    <?php endif; ?>
    </div>
    
    <div style="text-align: center; margin: 10px 0;">
        <span style="display: inline-block; width: 6px; height: 6px; background-color: #ccc; border-radius: 50%; margin: 0 3px;"></span>
        <span style="display: inline-block; width: 6px; height: 6px; background-color: #999; border-radius: 50%; margin: 0 3px;"></span>
        <span style="display: inline-block; width: 6px; height: 6px; background-color: #ccc; border-radius: 50%; margin: 0 3px;"></span>
    </div>
</div>

<hr style="border: none; border-top: 1px solid #eee; margin: 0;">

<div style="text-align: center; padding: 20px 0;">
    <a href="nihonntizu.php" 
       style="display: inline-block; padding: 10px 30px; border: 1px solid #ff9900; border-radius: 25px; background-color: #fff; color: #ff9900; text-decoration: none; font-weight: bold; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
        名産マップを見てみよう！ <i class="fas fa-chevron-right" style="font-size: 12px; margin-left: 5px;"></i>
    </a>
</div>

<hr style="border: none; border-top: 1px solid #eee; margin: 0 0 10px;">

<div>
<?php if (!empty($products)): ?>
    <?php foreach ($products as $p): ?>
        <div class="product-card">
            <a href="product_detail.php?id=<?php echo urlencode($p['product_id']); ?>" style="display: flex; align-items: center; text-decoration: none; color: #333; width: 100%;">
                
                <img src="img/<?php echo htmlspecialchars($p['image'] ?: 'noimage.png'); ?>" 
                     alt="<?php echo htmlspecialchars($p['name']); ?>" 
                     class="item-image" style="width: 100px; height: 120px; object-fit: contain;">
                
                <div class="item-info" style="margin-left: 15px; flex-grow: 1;">
                    <div class="item-name"><?php echo htmlspecialchars($p['name']); ?></div>
                    
                    <div style="text-align: right; margin-top: 10px;">
                        <span class="price">¥<?php echo number_format($p['price']); ?></span>
                        <span style="font-size: 16px; color: #333;">/本</span>
                    </div>
                </div>
            </a>
        </div>
    <?php endforeach; ?>
<?php else: ?>
    <p style="text-align: center;">該当する商品がありません。</p>
<?php endif; ?>
</div>

</div> 
</body>
</html>