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

/* ▼ 検索条件取得 ▼ */
$customer_name = $_GET['customer_name'] ?? '';
$product_name  = $_GET['product_name'] ?? '';
$purchase_date = $_GET['purchase_date'] ?? '';

/* ▼ SQL作成 ▼ */
$sql = "
    SELECT p.purchase_id, p.purchase_date, p.total, c.name AS customer_name,
           GROUP_CONCAT(CONCAT(pr.name, '×', pd.quantity) SEPARATOR '<br>') AS product_details
    FROM purchases p
    INNER JOIN customers c ON p.customer_id = c.customer_id
    INNER JOIN purchase_details pd ON p.purchase_id = pd.purchase_id
    INNER JOIN products pr ON pd.product_id = pr.product_id
    WHERE 1
";
$params = [];

/* 検索条件を追加 */
if ($customer_name !== '') {
    $sql .= " AND c.name LIKE :customer_name";
    $params[':customer_name'] = "%$customer_name%";
}
if ($product_name !== '') {
    $sql .= " AND pr.name LIKE :product_name";
    $params[':product_name'] = "%$product_name%";
}
if ($purchase_date !== '') {
    $sql .= " AND DATE(p.purchase_date) = :purchase_date";
    $params[':purchase_date'] = $purchase_date;
}

$sql .= " GROUP BY p.purchase_id ORDER BY p.purchase_date DESC";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("データ取得エラー: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<title>注文履歴管理</title>
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

/* ヘッダー */
.manager-header {
    background-color: #99EACA;
    width: 100%;
    padding: 10px 0;
    box-sizing: border-box;
}
.manager-header-content {
    display: flex;
    justify-content: center;
    align-items: center;
    text-decoration: none;
    color: inherit;
}
.manager-header-logo { width: 50px; height: auto; margin-right: 15px; }
.manager-header-title { font-size: 1.2rem; font-weight: bold; color: #333; }

/* タイトル + ハンバーガー */
.title-bar {
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
    background: #f0f0f0;
    padding: 10px 0;
    font-size: 1.2rem;
}
.title-bar h1 { margin: 0; }
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
.fullscreen-menu.open { left: 0; }
.fullscreen-menu ul { list-style: none; padding: 0; margin: 0; text-align: center; }
.fullscreen-menu li { margin: 20px 0; font-size: 1.2rem; }
.fullscreen-menu li a { color: #333; font-weight: bold; }
.menu-close { position: absolute; top: 20px; right: 20px; font-size: 2rem; cursor: pointer; }

/* テーブルラッパー */
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
th { background: #f0f0f0; }

/* 商品詳細横スクロール */
.product-detail {
    max-width: 300px;
    overflow-x: auto;
    white-space: nowrap;
}

/* 検索フォーム */
.search-form {
    margin: 10px;
}
.search-form input { padding: 5px; margin-right: 5px; }

/* レスポンシブ対応 */
@media (max-width: 600px) {
    th, td { font-size: 0.85rem; padding: 6px; }
    .title-bar { font-size: 1rem; }
    .fullscreen-menu li { font-size: 1rem; }
}
</style>
</head>
<body>

<!-- ヘッダー -->
<div class="manager-header">
    <a href="admin-dashboard.php" class="manager-header-content">
        <img src="img/logo.png" alt="ロゴ" class="manager-header-logo">
        <p class="manager-header-title">SATONOMI</p>
    </a>
</div>

<!-- タイトル + ハンバーガー -->
<div class="title-bar">
    <span class="menu-toggle" onclick="toggleMenu()">&#9776;</span>
    <h1>注文履歴管理</h1>
</div>

<!-- フルスクリーンメニュー -->
<div class="fullscreen-menu" id="menu">
    <div class="menu-close" onclick="toggleMenu()">×</div>
    <ul>
        <li><a href="product-manage.php">商品管理</a></li>
        <li><a href="customer-manage.php">顧客管理</a></li>
        <li><a href="purchase-history.php">注文履歴</a></li>
        <li><a href="admin-logout.php" style="color:red; font-weight:bold;">ログアウト</a></li>
    </ul>
</div>

<!-- 検索フォーム -->
<form method="get" class="search-form">
    <input type="text" name="customer_name" placeholder="顧客名" value="<?= htmlspecialchars($customer_name) ?>">
    <input type="text" name="product_name" placeholder="商品名" value="<?= htmlspecialchars($product_name) ?>">
    <input type="date" name="purchase_date" value="<?= htmlspecialchars($purchase_date) ?>">
    <button type="submit">検索</button>
</form>

<!-- テーブル -->
<div class="table-wrapper">
<table>
<tr>
    <th>No</th>
    <th>購入日</th>
    <th>顧客名</th>
    <th>合計金額</th>
    <th>商品詳細</th>
</tr>
<?php foreach ($rows as $index => $row): ?>
<tr>
    <td><?= $index + 1 ?></td>
    <td><?= htmlspecialchars($row['purchase_date']) ?></td>
    <td><?= htmlspecialchars($row['customer_name']) ?></td>
    <td><?= number_format($row['total']) ?>円</td>
    <td class="product-detail"><?= $row['product_details'] ?></td>
</tr>
<?php endforeach; ?>
</table>
</div>
<script>
function toggleMenu(){
    document.getElementById('menu').classList.toggle('open');
}
</script>

</body>
</html>
