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
$email         = $_GET['email'] ?? '';
$from_date     = $_GET['from_date'] ?? '';
$to_date       = $_GET['to_date'] ?? '';

/* ▼ SQL作成 ▼ */
$sql = "SELECT customer_id, name, email, created_at FROM customers WHERE 1";
$params = [];

if ($customer_name !== '') {
    $sql .= " AND name LIKE :name";
    $params[':name'] = "%$customer_name%";
}
if ($email !== '') {
    $sql .= " AND email LIKE :email";
    $params[':email'] = "%$email%";
}
if ($from_date !== '') {
    $sql .= " AND created_at >= :from_date";
    $params[':from_date'] = $from_date . " 00:00:00";
}
if ($to_date !== '') {
    $sql .= " AND created_at <= :to_date";
    $params[':to_date'] = $to_date . " 23:59:59";
}

$sql .= " ORDER BY created_at DESC";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("取得エラー: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<title>顧客管理</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<style>
html, body { margin:0; padding:0; font-family:sans-serif; background:#f9f9f9; }

/* ヘッダー */
.manager-header { background-color: #99EACA; width:100%; padding:10px 0; box-sizing:border-box; }
.manager-header-content { display:flex; justify-content:center; align-items:center; }
.manager-header-logo { width:50px; margin-right:15px; }
.manager-header-title { font-size:1.2rem; font-weight:bold; }

/* タイトル + ハンバーガー */
.title-bar { display:flex; justify-content:center; align-items:center; background:#f0f0f0; padding:10px 0; position:relative; }
.title-bar h1 { margin:0; }
.menu-toggle { position:absolute; left:20px; font-size:1.5rem; cursor:pointer; }

/* フルスクリーンメニュー */
.fullscreen-menu { position:fixed; top:0; left:-100%; width:100%; height:100%; background:#f1e9d6; z-index:50; display:flex; flex-direction:column; justify-content:center; align-items:center; transition:left 0.5s ease; }
.fullscreen-menu.open { left:0; }
.fullscreen-menu ul { list-style:none; padding:0; }
.fullscreen-menu li { margin:20px 0; font-size:1.2rem; }
.menu-close { position:absolute; top:20px; right:30px; font-size:2rem; cursor:pointer; }

/* テーブル */
.table-wrapper { width:100%; overflow-x:auto; padding:10px; }
table { width:100%; border-collapse:collapse; min-width:800px; }
th, td { border:1px solid #ccc; padding:8px; background:white; }
th { background:#f0f0f0; }

/* 検索フォーム */
.search-form { padding:10px; display:flex; flex-wrap:wrap; gap:10px; align-items:center; }
.search-form input { padding:5px; }
a {color:black;text-decoration:none;}
/* テーブル内リンク */
.table-wrapper a { color:#007bff; text-decoration:underline; font-weight:normal; }
.table-wrapper a:hover { color:#0056b3; text-decoration:underline; }

</style>
</head>
<body>

<div class="manager-header">
    <a href="admin-dashboard.php" class="manager-header-content">
        <img src="img/logo.png" class="manager-header-logo">
        <div class="manager-header-title">SATONOMI</div>
    </a>
</div>

<div class="title-bar">
    <span class="menu-toggle" onclick="toggleMenu()">&#9776;</span>
    <h1>顧客管理</h1>
</div>

<div class="fullscreen-menu" id="menu">
    <div class="menu-close" onclick="toggleMenu()">×</div>
    <ul>
        <li><a href="product-manage.php">商品管理</a></li>
        <li><a href="customer-manage.php">顧客管理</a></li>
        <li><a href="adorder-history.php">注文履歴</a></li>
        <li><a href="admin-logout.php" style="color:red; font-weight:bold;">ログアウト</a></li>
    </ul>
</div>

<form method="get" class="search-form">
    <input type="text" name="customer_name" placeholder="顧客名" value="<?= htmlspecialchars($customer_name) ?>">
    <input type="text" name="email" placeholder="メール" value="<?= htmlspecialchars($email) ?>">
    <label>登録日：</label>
    <input type="date" name="from_date" value="<?= htmlspecialchars($from_date) ?>">
    <span>〜</span>
    <input type="date" name="to_date" value="<?= htmlspecialchars($to_date) ?>">
    <button type="submit">検索</button>
</form>

<div class="table-wrapper">
<table>
<tr>
    <th>No</th>
    <th>顧客名</th>
    <th>メール</th>
    <th>登録日</th>
</tr>
<?php foreach ($rows as $i => $row): ?>
<tr>
    <td><?= $i + 1 ?></td>
    <td>
        <a href="adorder-history.php?customer_id=<?= $row['customer_id'] ?>">
            <?= htmlspecialchars($row['name']) ?>
        </a>
    </td>
    <td><?= htmlspecialchars($row['email']) ?></td>
    <td><?= htmlspecialchars($row['created_at']) ?></td>
</tr>
<?php endforeach; ?>
</table>
</div>

<script>
function toggleMenu() {
    document.getElementById('menu').classList.toggle('open');
}
</script>

</body>
</html>
