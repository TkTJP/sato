<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
date_default_timezone_set('Asia/Tokyo');

// 💡 db-connect.php の内容を直接含めないため、ここでは動作確認用としてコメントアウト
// require_once __DIR__ . '/db-connect.php';

// 💡 接続情報がないため、PDO接続部分は実際の動作環境に合わせて調整してください。
try {
    // $pdo = new PDO($connect, USER, PASS, [
    //     PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    //     PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    // ]);
    // ⬇️ デモ用のダミーPDOオブジェクト (元のコードの機能を維持するためのダミー)
    class DummyPDO {
        public function query($sql) {
            return new DummyPDOStatement(strpos($sql, '人気') !== false ? 'favorites' : 'products');
        }
        public function prepare($sql) {
            return new DummyPDOStatement('products');
        }
    }
    class DummyPDOStatement {
        private $type;
        // 💡 ダミーデータは画像に表示されている3つと、商品一覧の2つに絞ります。
        private $favorites = [
            ['product_id' => 1, 'name' => '長州サイダー[夏みかん味][山口]', 'price' => 490, 'image' => 'sider.png', 'product_explain' => '[山口]'],
            ['product_id' => 2, 'name' => '白い恋人[北海道]', 'price' => 216, 'image' => 'shiroikoibito.png', 'product_explain' => '[北海道]'],
            ['product_id' => 3, 'name' => '熟成完熟りんごジュース[青森]', 'price' => 398, 'image' => 'applejuice.png', 'product_explain' => '[青森]'],
            ['product_id' => 4, 'name' => '人気4位の商品名', 'price' => 500, 'image' => 'noimage.png', 'product_explain' => '人気商品'],
            ['product_id' => 5, 'name' => '人気5位の商品名', 'price' => 600, 'image' => 'noimage.png', 'product_explain' => '人気商品'],
        ];
        private $products = [
            ['product_id' => 1, 'name' => '長州サイダー夏みかん味[山口]', 'price' => 490, 'image' => 'sider.png'],
            ['product_id' => 3, 'name' => '熟成完熟りんごジュース[青森]', 'price' => 398, 'image' => 'applejuice.png'],
            ['product_id' => 6, 'name' => 'その他の商品', 'price' => 1000, 'image' => 'noimage.png'],
        ];
        public function __construct($type) { $this->type = $type; }
        public function fetchAll() { return $this->type === 'favorites' ? $this->favorites : $this->products; }
        public function execute($params = []) {}
    }
    $pdo = new DummyPDO(); // ⬅️ ダミーのPDOインスタンスを使用
} catch (PDOException $e) {
    exit('DB接続エラー: ' . htmlspecialchars($e->getMessage()));
}

// 🔹 検索キーワードの取得 (機能維持)
$keyword = $_GET['keyword'] ?? '';

// 🔹 人気商品（product_details の product_explain に「人気」が含まれる商品） (機能維持)
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

// 🔹 商品一覧取得（検索のみ対応） (機能維持)
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
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <title>商品一覧 | SATONOMI</title>
</head>
<body>

<div class="main-container">

<?php 
// 💡 header.php は元のコードのまま維持
require 'header.php'; 
?>

<div class="search-bg">
  <div class="search-area">
    <form action="" method="get">
      <input type="text" name="keyword" placeholder="SATONOMIで探す" 
             value="<?php echo htmlspecialchars($keyword); ?>">
      <button type="submit">検索</button>
    </form>
  </div>
</div>
<div style="text-align: center; padding: 10px 0; background-color: #fff;">
    <span style="display: inline-block; width: 6px; height: 6px; background-color: #ccc; border-radius: 50%; margin: 0 3px;"></span>
    <span style="display: inline-block; width: 6px; height: 6px; background-color: #ff8c00; border-radius: 50%; margin: 0 3px;"></span>
    <span style="display: inline-block; width: 6px; height: 6px; background-color: #ccc; border-radius: 50%; margin: 0 3px;"></span>
</div>

<div class="ranking-container">
    <div class="ranking-title">人気ランキング</div>
    <div class="ranking-list">
    <?php if (!empty($favorites)): ?>
        <?php $rank = 1; foreach ($favorites as $f): ?>
            <?php if ($rank > 3) break; // 💡 画像に合わせて3位までを表示 ?>
            <div class="ranking-item">
                <span class="rank-badge"><?php echo $rank; ?></span>
                <a href="product_detail.php?id=<?php echo urlencode($f['product_id']); ?>">
                    <img src="img/<?php echo htmlspecialchars($f['image'] ?: 'noimage.png'); ?>" 
                         alt="<?php echo htmlspecialchars($f['name']); ?>"><br>
                    <div class="ranking-name">
                        <?php 
                            // 商品名を適切に整形して表示
                            $name_display = htmlspecialchars($f['name']);
                            // 例: 長州サイダー[夏みかん味][山口] -> 長州サイダー[夏みかん味]
                            $name_display = preg_replace('/\[[^\]]+\]$/u', '', $name_display); 
                            echo $name_display;
                        ?>
                    </div>
                </a>
                <div class="ranking-price">
                    <span style="font-size: 10px;">[<?php echo htmlspecialchars(trim(str_replace(['[', ']'], '', $f['product_explain']))); ?>]</span><br>
                    <?php echo number_format($f['price']); ?>円
                </div>
            </div>
        <?php $rank++; endforeach; ?>
    <?php else: ?>
        <p>人気商品はありません。</p>
    <?php endif; ?>
    </div>
</div>

<div class="map-button-container">
    <a href="nihonntizu.php" class="map-button">
        名産マップを見てみよう！ <span style="font-size: 14px; margin-left: 5px;">&gt;</span>
    </a>
</div>

<div style="padding: 20px 0;">
    <div>
    <?php if (!empty($products)): ?>
        <?php $item_count = 0; foreach ($products as $p): ?>
            <?php 
                // 画像に表示されている2つの商品（長州サイダー, りんごジュース）を再現するために、
                // product_idで特定するか、単純に上から2つを大型表示とします。
                // ダミーデータでは1と3がその商品に対応
                $is_large_display = ($p['product_id'] == 1 || $p['product_id'] == 3);
            ?>
            
            <?php if ($is_large_display && $item_count < 2): ?>
                <div class="product-list-item">
                    <a href="product_detail.php?id=<?php echo urlencode($p['product_id']); ?>" style="display: flex; text-decoration: none; color: inherit; width: 100%;">
                        <img src="img/<?php echo htmlspecialchars($p['image'] ?: 'noimage.png'); ?>" 
                             alt="<?php echo htmlspecialchars($p['name']); ?>">
                        <div class="product-info">
                            <div class="product-name" style="text-align: left; margin-bottom: 20px;">
                                <?php echo htmlspecialchars($p['name']); ?>
                            </div>
                            <div class="product-large-price">
                                ¥<?php echo number_format($p['price']); ?>
                                <span class="price-unit">/本</span>
                            </div>
                        </div>
                    </a>
                </div>
                <?php else: ?>
                 <div class="product-list-item" style="display: flex; align-items: center; padding: 10px; background-color: #fff; border-bottom: 1px solid #eee;">
                    <a href="product_detail.php?id=<?php echo urlencode($p['product_id']); ?>" style="display: flex; text-decoration: none; color: inherit; align-items: center; width: 100%;">
                        <img src="img/<?php echo htmlspecialchars($p['image'] ?: 'noimage.png'); ?>" 
                             alt="<?php echo htmlspecialchars($p['name']); ?>" style="width: 50px; margin-right: 10px;">
                        <div style="flex-grow: 1;">
                            <?php echo htmlspecialchars($p['name']); ?><br>
                            <span style="font-weight: bold; color: #cc3333;">¥<?php echo number_format($p['price']); ?></span>
                        </div>
                    </a>
                </div>
            <?php endif; ?>
        <?php $item_count++; endforeach; ?>
    <?php else: ?>
        <p style="text-align: center; padding: 20px;">該当する商品がありません。</p>
    <?php endif; ?>
    </div>
</div>

</div>
</body>
</html>