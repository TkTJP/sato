<?php
require 'db-connect.php';
$pdo = new PDO($connect, USER, PASS);

// -----------------------------
// 更新・削除処理
// -----------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = $_POST['product_id'] ?? null;
    $command = $_POST['command'] ?? '';

    if ($product_id) {
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
            $pdo->prepare("DELETE FROM products WHERE product_id = ?")->execute([$product_id]);
            $pdo->prepare("DELETE FROM product_details WHERE product_id = ?")->execute([$product_id]);
        }
    }

    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// -----------------------------
// 商品一覧の取得
// -----------------------------
$sql = $pdo->query("
    SELECT p.*, d.is_subscribe, d.product_explain 
    FROM products p 
    LEFT JOIN product_details d ON p.product_id = d.product_id 
    ORDER BY p.product_id DESC
");
$products = $sql->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<title>商品管理</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<script src="https://unpkg.com/vue@3/dist/vue.global.prod.js"></script>
<style>
body {font-family: sans-serif; margin:0; padding:0; background-color:#f9f9f9;}
/* ヘッダー */
header {display:flex; align-items:center; padding:10px 20px; background:white; position:relative; z-index:2;}
.menu-toggle {font-size:1.8rem; cursor:pointer; margin-right:10px;}
.header-title {font-weight:bold; font-size:1.4rem;}
/* フルスクリーンメニュー */
.fullscreen-menu {
    position:fixed; top:0; left:-100%; width:100%; height:100%;
    background:#F1E9D6; z-index:5; display:flex; flex-direction:column; justify-content:space-between;
    transition:left 0.5s ease; padding:60px 20px 40px 20px; box-sizing:border-box;
}
.fullscreen-menu.open {left:0;}
.fullscreen-menu ul {list-style:none; padding:0; margin:0; flex-grow:1; display:flex; flex-direction:column; justify-content:center; align-items:center;}
.fullscreen-menu li {margin:20px 0; font-size:1.5rem;}
.fullscreen-menu li a {color:black; text-decoration:none; font-weight:bold;}
.menu-close {position:absolute; top:20px; right:20px; font-size:2rem; cursor:pointer;}
.logout-btn {padding:12px 25px; font-size:1.2rem; border-radius:5px; text-decoration:none; color:black; border:2px solid white; text-align:center;}
.logout-btn:hover {background:white; color:#FE9D6B;}

/* テーブル */
.table-container {overflow-x:auto; background:white; border-radius:8px; box-shadow:0 0 10px rgba(0,0,0,0.1); margin:20px;}
table {border-collapse:collapse; width:100%; min-width:1100px;}
th, td {border:1px solid #ccc; padding:8px; text-align:left; vertical-align:middle;}
th {background:#f0f0f0;}
img {width:80px; height:80px; object-fit:cover; border-radius:6px; display:block; margin:0 auto 5px;}
textarea,input[type="text"],input[type="number"],select {width:100%; box-sizing:border-box; font-size:0.9rem;}
.btn {display:inline-block; padding:6px 10px; border:none; border-radius:5px; cursor:pointer; font-size:0.85rem; color:white;}
.btn-update {background-color:#4CAF50;}
.btn-delete {background-color:#E74C3C;}
.btn-insert {background-color:#3498DB; display:block; margin:20px auto; text-align:center; text-decoration:none; padding:10px 20px; width:200px; border-radius:6px;}
</style>
</head>
<body>

<div id="app">
    <header>
        <div class="menu-toggle" @click="toggleMenu">&#9776;</div>
        <div class="header-title">商品管理</div>
    </header>

    <div class="fullscreen-menu" :class="{ open: menuOpen }">
        <div class="menu-close" @click="closeMenu">×</div>
        <ul>
            <li><a href="product-manage.php" @click="closeMenu">商品管理</a></li>
            <li><a href="customer-manage.php" @click="closeMenu">顧客管理</a></li>
            <li><a href="order-history.php" @click="closeMenu">注文履歴</a></li>
        </ul>
        <a href="logout.php" class="logout-btn" @click="closeMenu">ログアウト</a>
    </div>

    <h1 style="text-align:center; margin-top:20px;">商品一覧</h1>

    <div class="table-container">
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
                <th>商品詳細説明</th>
                <th>操作</th>
            </tr>

            <?php foreach ($products as $index => $row): ?>
            <tr>
                <form action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>" method="post" enctype="multipart/form-data">
                    <input type="hidden" name="product_id" value="<?= htmlspecialchars($row['product_id']) ?>">
                    <td><?= $index + 1 ?></td>
                    <td style="text-align:center;">
                        <?php if (!empty($row['image'])): ?>
                        <img src="img/<?= htmlspecialchars($row['image']) ?>" alt="商品画像">
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
                        <select name="region" class="region-select">
                            <option value="">選択してください</option>
                            <option value="北海道" <?= $row['region']=='北海道'?'selected':'' ?>>北海道</option>
                            <option value="東北" <?= $row['region']=='東北'?'selected':'' ?>>東北</option>
                            <option value="関東" <?= $row['region']=='関東'?'selected':'' ?>>関東</option>
                            <option value="中部" <?= $row['region']=='中部'?'selected':'' ?>>中部</option>
                            <option value="近畿" <?= $row['region']=='近畿'?'selected':'' ?>>近畿</option>
                            <option value="中国" <?= $row['region']=='中国'?'selected':'' ?>>中国</option>
                            <option value="四国" <?= $row['region']=='四国'?'selected':'' ?>>四国</option>
                            <option value="九州" <?= $row['region']=='九州'?'selected':'' ?>>九州</option>
                        </select>
                    </td>
                    <td>
                        <select name="prefecture" class="prefecture-select">
                            <option value="<?= htmlspecialchars($row['prefecture']) ?>"><?= htmlspecialchars($row['prefecture'] ?: '選択してください') ?></option>
                        </select>
                    </td>
                    <td><textarea name="product_explain"><?= htmlspecialchars($row['product_explain']) ?></textarea></td>
                    <td>
                        <button type="submit" name="command" value="update" class="btn btn-update">更新</button><br>
                        <button type="submit" name="command" value="delete" class="btn btn-delete" onclick="return confirm('本当に削除しますか？');">削除</button>
                    </td>
                </form>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>

    <a href="product-insert.php" class="btn btn-insert">＋ 商品を追加する</a>
</div>

<script>
const { createApp, ref } = Vue;

createApp({
    setup() {
        const menuOpen = ref(false);
        const toggleMenu = () => menuOpen.value = !menuOpen.value;
        const closeMenu = () => menuOpen.value = false;
        return { menuOpen, toggleMenu, closeMenu };
    }
}).mount('#app');

// 地方 → 都道府県連動
const prefectures = {
    "北海道": ["北海道"],
    "東北": ["青森県","岩手県","宮城県","秋田県","山形県","福島県"],
    "関東": ["茨城県","栃木県","群馬県","埼玉県","千葉県","東京都","神奈川県"],
    "中部": ["新潟県","富山県","石川県","福井県","山梨県","長野県","岐阜県","静岡県","愛知県"],
    "近畿": ["三重県","滋賀県","京都府","大阪府","兵庫県","奈良県","和歌山県"],
    "中国": ["鳥取県","島根県","岡山県","広島県","山口県"],
    "四国": ["徳島県","香川県","愛媛県","高知県"],
    "九州": ["福岡県","佐賀県","長崎県","熊本県","大分県","宮崎県","鹿児島県","沖縄県"]
};
document.querySelectorAll(".region-select").forEach((regionSelect, i) => {
    const prefSelect = document.querySelectorAll(".prefecture-select")[i];
    regionSelect.addEventListener("change", () => {
        const region = regionSelect.value;
        prefSelect.innerHTML = '<option value="">選択してください</option>';
        if (prefectures[region]) {
            prefectures[region].forEach(p => {
                const opt = document.createElement("option");
                opt.value = p;
                opt.textContent = p;
                prefSelect.appendChild(opt);
            });
        }
    });
});
</script>

</body>
</html>
