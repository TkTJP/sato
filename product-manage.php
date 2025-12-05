<?php
session_start();
require 'db-connect.php';

/* ▼ ログインチェック ▼ */
if (!isset($_SESSION["admin_id"])) {
    echo "<script>
            alert('ログインしてください');
            window.location.href = 'admin-login.php';
          </script>";
    exit;
}

/* ▼ DB接続 ▼ */
try {
    $pdo = new PDO($connect, USER, PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("DB接続失敗: " . $e->getMessage());
}

/* -----------------------------
   商品名リンクからの検索対応
----------------------------- */
$search_name = $_GET['name'] ?? '';

/* -----------------------------
   更新・削除処理
----------------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = $_POST['product_id'] ?? null;
    $command = $_POST['command'] ?? '';

    if ($product_id) {
        try {
            // 画像アップロード処理
            $image_name = null;
            if (!empty($_FILES['image']['name'])) {
                $image_name = basename($_FILES['image']['name']);
                $target = __DIR__ . "/img/" . $image_name;
                move_uploaded_file($_FILES['image']['tmp_name'], $target);
            }

            if ($command === 'update') {
                $sql = $pdo->prepare("
                    UPDATE products 
                    SET name = ?, price = ?, description = ?, stock = ?, 
                        image = COALESCE(?, image), region = ?, prefecture = ?
                    WHERE product_id = ?
                ");
                $sql->execute([
                    $_POST['name'],
                    $_POST['price'],
                    $_POST['description'],
                    $_POST['stock'],
                    $image_name,
                    $_POST['region'] ?? null,
                    $_POST['prefecture'] ?? null,
                    $product_id
                ]);

                $sql2 = $pdo->prepare("
                    UPDATE product_details
                    SET is_subscribe = ?, product_explain = ?
                    WHERE product_id = ?
                ");
                $sql2->execute([
                    $_POST['is_subscribe'],
                    $_POST['product_explain'],
                    $product_id
                ]);
            }

            if ($command === 'delete') {
                $pdo->prepare("DELETE FROM product_details WHERE product_id = ?")->execute([$product_id]);
                $pdo->prepare("DELETE FROM products WHERE product_id = ?")->execute([$product_id]);
            }

            header("Location: " . $_SERVER['PHP_SELF']);
            exit;

        } catch (PDOException $e) {
            echo "<p style='color:red;'>エラー: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
    }
}

/* -----------------------------
   商品一覧取得（商品名検索対応）
   ※TRIM対応で空白除去検索
----------------------------- */
try {
    $sql = $pdo->prepare("
        SELECT p.*, d.is_subscribe, d.product_explain 
        FROM products p 
        LEFT JOIN product_details d ON p.product_id = d.product_id 
        WHERE (:name = '' OR TRIM(p.name) LIKE :name_like)
        ORDER BY p.product_id DESC
    ");

    $sql->execute([
        ':name' => $search_name,
        ':name_like' => "%" . trim($search_name) . "%"
    ]);

    $products = $sql->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("商品取得エラー: " . $e->getMessage());
}

/* -----------------------------
   地方ごとの都道府県
----------------------------- */
$prefectures = [
    "北海道" => ["北海道"],
    "東北" => ["青森","岩手","秋田","宮城","山形","福島"],
    "関東" => ["茨城","栃木","群馬","埼玉","千葉","東京","神奈川"],
    "中部" => ["新潟","富山","石川","福井","山梨","長野","岐阜","静岡","愛知"],
    "近畿" => ["三重","滋賀","京都","大阪","兵庫","奈良","和歌山"],
    "中国" => ["鳥取","島根","岡山","広島","山口"],
    "四国" => ["徳島","香川","愛媛","高知"],
    "九州" => ["福岡","佐賀","長崎","熊本","大分","宮崎","鹿児島","沖縄"]
];
$regions = array_keys($prefectures);
?>

<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<title>商品管理</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<style>
/* 基本設定 */
html, body {
    margin: 0;
    padding: 0;
    font-family: sans-serif;
    background: #f9f9f9;
    color: #333;
}
a {text-decoration: none; color: inherit;}

/* タイトルバー */
.title-bar {
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
    background: #f0f0f0;
    padding: 10px 0;
    font-size: 1.2rem;
}
.title-bar h1 {margin: 0;}
.title-bar .menu-toggle {
    position: absolute;
    left: 20px;
    top: 50%;
    transform: translateY(-50%);
    font-size: 1.5rem;
    cursor: pointer;
}

/* フルスクリーンメニュー */
.fullscreen-menu {
    position: fixed;
    top: 0; left: -100%;
    width: 100%; height: 100%;
    background: #f1e9d6;
    z-index: 5;
    display: flex; flex-direction: column; justify-content: center; align-items: center;
    transition: left 0.5s ease;
}
.fullscreen-menu.open {left:0;}
.fullscreen-menu ul {list-style: none; padding: 0; margin: 0; text-align: center;}
.fullscreen-menu li {margin: 20px 0; font-size: 1.2rem;}
.fullscreen-menu li a {color: #333; font-weight: bold;}
.menu-close {position: absolute; top: 20px; right: 20px; font-size: 2rem; cursor: pointer;}

/* テーブルラッパー（横スクロール対応） */
.table-wrapper {
    width: 100%;
    overflow-x: auto;
    padding: 0 10px;
    box-sizing: border-box;
}
table {
    border-collapse: collapse;
    width: 100%;
    min-width: 900px;
}
th, td {
    border: 1px solid #ccc;
    padding: 8px;
    text-align: left;
    vertical-align: top;
}
th {background: #f0f0f0;}
img {width: 80px; height: 80px; object-fit: cover;}
input, textarea, select {width: 100%; box-sizing: border-box;}
textarea {resize: none; height: 80px;}
button {
    padding: 5px 10px;
    margin: 2px;
    cursor: pointer;
    background: #fff;
    border: 1px solid #ccc;
    border-radius: 4px;
}

/* 商品追加ボタン */
.add-product {
    text-align: center;
    padding: 15px 10px;
}
.add-product button {
    padding: 12px 20px;
    font-size: 1.1rem;
    background-color: aquamarine;
    border: 1px solid #ccc;
    border-radius: 4px;
    cursor: pointer;
    width:90%;
    color:black;
}
.add-product button:hover {
    background-color:lawngreen;
    color:black;
}
</style
</head>
<body>

<?php include 'manager-header.php'; ?>

<!-- タイトル + ハンバーガー -->
<div class="title-bar">
    <span class="menu-toggle" onclick="toggleMenu()">&#9776;</span>
    <h1>商品管理</h1>
</div>

<!-- フルスクリーンメニュー -->
<div class="fullscreen-menu" id="menu">
    <div class="menu-close" onclick="toggleMenu()">×</div>
    <ul>
        <li><a href="product-manage.php">商品管理</a></li>
        <li><a href="customer-manage.php">顧客管理</a></li>
        <li><a href="order-history.php">注文履歴</a></li>
        <li><a href="admin-logout.php" style="color:red; font-weight:bold;">ログアウト</a></li>
    </ul>
</div>

<!-- 商品追加ボタン -->
<div class="add-product">
    <a href="product-insert.php"><button>商品追加</button></a>
</div>

<!-- 商品名検索フォーム -->
<div style="padding: 10px; text-align:center;">
    <form method="get" action="product-manage.php" style="display:flex; justify-content:center; gap:10px;">
        <input type="text" name="name" placeholder="商品名で検索" 
               value="<?= htmlspecialchars($search_name, ENT_QUOTES) ?>"
               style="padding:6px; width:200px;">
        <button type="submit" style="padding:6px 15px;">検索</button>
    </form>
</div>

<div class="table-wrapper">
<table>
<tr>
    <th>No</th>
    <th>画像</th>
    <th>商品名</th>
    <th>価格</th>
    <th>商品説明</th>
    <th>在庫</th>
    <th>販売タイプ</th>
    <th>地方</th>
    <th>都道府県</th>
    <th>検索カテゴリ</th>
    <th>操作</th>
</tr>

<?php foreach ($products as $index => $row): ?>
<tr>
<form action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>" method="post" enctype="multipart/form-data">
    <input type="hidden" name="product_id" value="<?= htmlspecialchars($row['product_id']) ?>">

    <td><?= $index + 1 ?></td>

    <td>
        <?php if (!empty($row['image'])): ?>
            <img src="img/<?= htmlspecialchars($row['image']) ?>">
        <?php endif; ?>
        <input type="file" name="image">
    </td>

    <td><input type="text" name="name" value="<?= htmlspecialchars($row['name']) ?>"></td>
    <td><input type="number" name="price" value="<?= htmlspecialchars($row['price']) ?>"></td>
    <td><textarea name="description"><?= htmlspecialchars($row['description']) ?></textarea></td>
    <td><input type="number" name="stock" value="<?= htmlspecialchars($row['stock']) ?>"></td>

    <td>
        <select name="is_subscribe">
            <option value="0" <?= $row['is_subscribe']==0?'selected':'' ?>>通常販売</option>
            <option value="1" <?= $row['is_subscribe']==1?'selected':'' ?>>サブスク</option>
        </select>
    </td>

    <td>
        <select name="region" class="region-select" data-target="prefecture-<?= $index ?>">
            <option value="">選択してください</option>
            <?php foreach($prefectures as $region_name => $prefs): 
                $sel = $row['region']==$region_name?'selected':''; ?>
                <option value="<?= htmlspecialchars($region_name) ?>" <?= $sel ?>><?= htmlspecialchars($region_name) ?></option>
            <?php endforeach; ?>
        </select>
    </td>

    <td>
        <select name="prefecture" id="prefecture-<?= $index ?>">
            <option value="">選択してください</option>
            <?php 
            if (!empty($row['region']) && isset($prefectures[$row['region']])) {
                foreach($prefectures[$row['region']] as $pref){
                    $sel = $row['prefecture']==$pref ? 'selected' : '';
                    echo "<option value='".htmlspecialchars($pref)."' $sel>$pref</option>";
                }
            }
            ?>
        </select>
    </td>

    <td><textarea name="product_explain"><?= htmlspecialchars($row['product_explain']) ?></textarea></td>

    <td>
        <button type="submit" name="command" value="update">更新</button>
        <button type="submit" name="command" value="delete" onclick="return confirm('削除しますか？');">削除</button>
    </td>
</form>
</tr>
<?php endforeach; ?>
</table>
</div>

<script>
function toggleMenu(){
    document.getElementById('menu').classList.toggle('open');
}

// 地方選択で都道府県更新
document.querySelectorAll('.region-select').forEach(function(regionSelect){
    regionSelect.addEventListener('change', function(){
        const targetId = this.dataset.target;
        const prefSelect = document.getElementById(targetId);
        const prefData = <?= json_encode($prefectures) ?>;
        const region = this.value;

        prefSelect.innerHTML = '<option value="">選択してください</option>';
        if(prefData[region]){
            prefData[region].forEach(function(pref){
                const opt = document.createElement('option');
                opt.value = pref;
                opt.textContent = pref;
                prefSelect.appendChild(opt);
            });
        }
    });
});
</script>

</body>
</html>
