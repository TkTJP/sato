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
$customer_id   = $_GET['customer_id'] ?? '';
$customer_name = $_GET['customer_name'] ?? '';
$product_name  = $_GET['product_name'] ?? '';
$purchase_date_start = $_GET['purchase_date_start'] ?? '';
$purchase_date_end   = $_GET['purchase_date_end'] ?? '';

/* ▼ SQL作成 ▼ */
$sql = "
    SELECT
        p.purchase_id,
        p.purchase_date,
        p.total,
        c.name AS customer_name,
        pr.name AS product_name,
        pd.quantity,
        pd.box
    FROM purchases p
    INNER JOIN customers c ON p.customer_id = c.customer_id
    INNER JOIN purchase_details pd ON p.purchase_id = pd.purchase_id
    INNER JOIN products pr ON pd.product_id = pr.product_id
    WHERE 1
";

$params = [];

/* 顧客ID */
if ($customer_id !== '') {
    $sql .= " AND c.customer_id = :customer_id";
    $params[':customer_id'] = $customer_id;
}

/* 顧客名 */
if ($customer_name !== '') {
    $sql .= " AND c.name LIKE :customer_name";
    $params[':customer_name'] = "%$customer_name%";
}

/* 商品名 */
if ($product_name !== '') {
    $sql .= " AND pr.name LIKE :product_name";
    $params[':product_name'] = "%$product_name%";
}

/* 日付（範囲） */
if ($purchase_date_start !== '' && $purchase_date_end !== '') {
    $sql .= " AND DATE(p.purchase_date) BETWEEN :start AND :end";
    $params[':start'] = $purchase_date_start;
    $params[':end'] = $purchase_date_end;
} elseif ($purchase_date_start !== '') {
    $sql .= " AND DATE(p.purchase_date) >= :start";
    $params[':start'] = $purchase_date_start;
} elseif ($purchase_date_end !== '') {
    $sql .= " AND DATE(p.purchase_date) <= :end";
    $params[':end'] = $purchase_date_end;
}

$sql .= " ORDER BY p.purchase_date DESC";

/* ▼ SQL実行 ▼ */
try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rawRows = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("データ取得エラー: " . $e->getMessage());
}

/* ▼ purchase_id ごとにまとめ直す ▼ */
$rows = [];
$total_sum = 0;

foreach ($rawRows as $r) {
    $pid = $r['purchase_id'];

    if (!isset($rows[$pid])) {
        $rows[$pid] = [
            'purchase_id'   => $pid,
            'purchase_date' => $r['purchase_date'],
            'customer_name' => $r['customer_name'],
            'total'         => $r['total'],
            'items'         => []
        ];
        $total_sum += $r['total'];
    }

    // ▼ 商品名リンク化（product-manage.php への検索対応）
    $itemText = '';

    // 単品
    if ((int)$r['quantity'] > 0) {
        $itemText .= '<a href="product-manage.php?name=' . urlencode(trim($r['product_name'])) . '">'
                   . htmlspecialchars($r['product_name']) . '</a> × ' . (int)$r['quantity'];
    }

    // 12本セット（box）
    if ((int)$r['box'] > 0) {
        if ($itemText !== '') $itemText .= "<br>";
        $itemText .= '<a href="product-manage.php?name=' . urlencode(trim($r['product_name'])) . '">'
                   . htmlspecialchars($r['product_name']) . '</a>（12本セット） × ' . (int)$r['box'];
    }

    $rows[$pid]['items'][] = $itemText;
}

/* items をまとめる */
foreach ($rows as &$row) {
    $row['product_details'] = implode('<br>', $row['items']);
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<title>注文履歴管理</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<style>
html, body {
    margin: 0;
    padding: 0;
    font-family: sans-serif;
    background: #f9f9f9;
    color: #333;
}
a {text-decoration: none; color: #0078D7;}

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
    color:black;
    text-decoration:none;
}
.manager-header-logo { width: 50px; height: auto; margin-right: 15px; }
.manager-header-title { font-size: 1.2rem; font-weight: bold; }

.title-bar { display:flex; justify-content:center; align-items:center; background:#f0f0f0; padding:10px 0; position:relative; }
.title-bar h1 { margin:0; }
.menu-toggle { position:absolute; left:20px; font-size:1.5rem; cursor:pointer; }

.fullscreen-menu { position:fixed; top:0; left:-100%; width:100%; height:100%; background:#f1e9d6; z-index:50; display:flex; flex-direction:column; justify-content:center; align-items:center; transition:left 0.5s ease; }
.fullscreen-menu.open { left:0; }
.fullscreen-menu ul { list-style:none; padding:0; }
.fullscreen-menu li { margin:20px 0; font-size:1.2rem;}
.menu-close { position:absolute; top:20px; right:30px; font-size:2rem; cursor:pointer; }

.table-wrapper { width: 100%; overflow-x: auto; padding: 0 10px; box-sizing: border-box; }
table { border-collapse: collapse; width: 100%; min-width: 900px; }
th, td { border: 1px solid #ccc; padding: 8px; vertical-align: top; }
th { background: #f0f0f0; }

.product-detail { max-width: 300px; overflow-x: auto; white-space: nowrap; }
.search-form { margin: 10px; }
.search-form input { padding: 5px; margin-right: 5px; }
</style>
</head>
<body>

<div class="manager-header">
    <a href="admin-dashboard.php" class="manager-header-content">
        <img src="img/logo.png" alt="ロゴ" class="manager-header-logo">
        <p class="manager-header-title">SATONOMI</p>
    </a>
</div>

<div class="title-bar">
    <span class="menu-toggle" onclick="toggleMenu()">&#9776;</span>
    <h1>注文履歴管理</h1>
</div>

<div class="fullscreen-menu" id="menu">
    <div class="menu-close" onclick="toggleMenu()">×</div>
    <ul>
        <li><a href="product-manage.php"style="color:black;">商品管理</a></li>
        <li><a href="customer-manage.php"style="color:black;">顧客管理</a></li>
        <li><a href="order-history.php"style="color:black;">注文履歴</a></li>
        <li><a href="admin-logout.php" style="color:red; font-weight:bold;">ログアウト</a></li>
    </ul>
</div>

<!-- 検索フォーム -->
<form method="get" class="search-form">
    <input type="hidden" name="customer_id" value="<?= htmlspecialchars($customer_id) ?>">
    <input type="text" name="customer_name" placeholder="顧客名" value="<?= htmlspecialchars($customer_name) ?>">
    <input type="text" name="product_name" placeholder="商品名" value="<?= htmlspecialchars($product_name) ?>">
    <input type="date" name="purchase_date_start" value="<?= htmlspecialchars($purchase_date_start) ?>"> 〜
    <input type="date" name="purchase_date_end" value="<?= htmlspecialchars($purchase_date_end) ?>">
    <button type="submit">検索</button>
</form>

<div style="margin: 10px; font-size: 18px;">
    <strong>検索結果の合計金額： <?= number_format($total_sum) ?> 円</strong>
</div>

<div class="table-wrapper">
<table>
<tr>
    <th>No</th>
    <th>購入日</th>
    <th>顧客名</th>
    <th>合計金額</th>
    <th>商品詳細</th>
</tr>

<?php $index = 1; foreach ($rows as $row): ?>
<tr>
    <td><?= $index++ ?></td>
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
