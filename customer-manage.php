<?php
session_start();
require 'db-connect.php';

/* ▼ ログインチェック（管理者用） ▼ */
if (!isset($_SESSION["admin_id"])) {
    echo "<script>
            alert('ログインしてください');
            window.location.href = 'admin-login.php';
          </script>";
    exit;
}
/* ▲ ログインチェックここまで ▲ */

$searchName    = $_GET['name'] ?? '';
$searchPhone   = $_GET['phone'] ?? '';
$searchAddress = $_GET['address'] ?? '';
$searchEmail   = $_GET['email'] ?? '';

try {
    $pdo = new PDO($connect, USER, PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $sql = "
        SELECT c.customer_id, c.name, c.email, c.subscr_join, c.created_at,
               a.phone_number, CONCAT(a.prefecture, a.city, a.street) AS full_address
        FROM customers c
        LEFT JOIN addresses a ON c.customer_id = a.customer_id
        WHERE 1
    ";
    $params = [];

    if ($searchName)    { $sql .= " AND c.name LIKE :name"; $params[':name'] = "%$searchName%"; }
    if ($searchPhone)   { $sql .= " AND a.phone_number LIKE :phone"; $params[':phone'] = "%$searchPhone%"; }
    if ($searchAddress) { $sql .= " AND CONCAT(a.prefecture, a.city, a.street) LIKE :address"; $params[':address'] = "%$searchAddress%"; }
    if ($searchEmail)   { $sql .= " AND c.email LIKE :email"; $params[':email'] = "%$searchEmail%"; }

    $sql .= " ORDER BY c.customer_id DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("DBエラー: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<title>顧客管理</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<style>
/* ------------------------------
   全体基本設定
------------------------------ */
html, body {
    margin: 0;
    padding: 0;
    font-family: sans-serif;
    background: #f9f9f9;
    color: #333;
}
a { text-decoration: none; color: inherit; }

/* ------------------------------
   タイトルバー・ヘッダー
------------------------------ */
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
.menu-toggle {
    position: absolute;
    left: 20px;
    top: 50%;
    transform: translateY(-50%);
    font-size: 2rem;
    cursor: pointer;
    user-select: none;
}

/* ------------------------------
   フルスクリーンメニュー
------------------------------ */
.fullscreen-menu {
    position: fixed;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: #f1e9d6;
    z-index: 999;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    transition: left 0.3s ease;
}
.fullscreen-menu.open { left: 0; }
.fullscreen-menu ul {
    list-style: none;
    padding: 0;
    margin: 0;
    text-align: center;
}
.fullscreen-menu li {
    margin: 25px 0;
    font-size: 1.5rem;
}
.fullscreen-menu li a {
    color: #333;
    font-weight: bold;
}
.menu-close {
    position: absolute;
    top: 20px;
    right: 20px;
    font-size: 2.5rem;
    cursor: pointer;
}
.logout-btn {
    padding: 12px 25px;
    font-size: 1.2rem;
    border-radius: 5px;
    text-decoration: none;
    color: black;
    border: 2px solid white;
    text-align: center;
    margin-bottom: 30px;
    transition: background 0.3s, color 0.3s;
}
.logout-btn:hover {
    background: white;
    color: #FE9D6B;
}

/* ------------------------------
   メインブロック・テーブル
------------------------------ */
main {
    max-width: 1000px;
    width: 95%;
    margin: 20px auto;
    padding: 15px;
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 3px 8px rgba(0,0,0,0.1);
}

/* ------------------------------
   フォーム
------------------------------ */
form.search-form {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    justify-content: center;
    margin-bottom: 15px;
}
form.search-form input,
form.search-form button {
    flex: 1 1 140px;
    padding: 6px 8px;
    font-size: 0.95rem;
    border-radius: 6px;
    border: 1px solid #ccc;
    min-width: 100px;
    height: 36px;            /* 入力とボタンの高さを統一 */
    box-sizing: border-box;   /* paddingを含める */
}
form.search-form button {
    background: #0078D7;
    color: white;
    border: none;
    font-weight: bold;
    cursor: pointer;
}
form.search-form button:hover {
    background: #005fa3;
}

/* ------------------------------
   テーブル
------------------------------ */
.table-wrapper {width: 100%; overflow-x: auto; padding: 0 10px; box-sizing: border-box;}
table {
    border-collapse: collapse;
    width: 100%;
    min-width: 600px;
}
th, td {
    border: 1px solid #ccc;
    padding: 6px;
    text-align: left;
    vertical-align: middle;
}
th {background: #f0f0f0;}
a.name-link {color: #0078D7; text-decoration: none;}
a.name-link:hover {text-decoration: underline;}

/* ------------------------------
   スマホ対応
------------------------------ */
@media (max-width: 600px) {
    th, td {font-size: 0.85rem; padding: 4px;}
    .title-bar {font-size: 1rem;}
    .fullscreen-menu li {font-size: 1.3rem; margin: 15px 0;}
    .menu-toggle {font-size: 2.2rem; left: 15px;}
    .menu-close {font-size: 2.2rem; top: 15px; right: 15px;}
    form.search-form input,
    form.search-form button {
        width: 100%;
        height: 32px;          /* スマホでも統一 */
        font-size: 0.9rem;
        padding: 4px 6px;
    }
}
</style>
</head>
<body>

<?php require 'manager-header.php'; ?>

<!-- タイトル + ハンバーガー -->
<div class="title-bar">
    <span class="menu-toggle" onclick="toggleMenu()">&#9776;</span>
    <h1>顧客管理</h1>
</div>

<!-- フルスクリーンメニュー -->
<div class="fullscreen-menu" id="menu">
    <div class="menu-close" onclick="toggleMenu()">×</div>
    <ul>
        <li><a href="product-manage.php">商品管理</a></li>
        <li><a href="customer-manage.php">顧客管理</a></li>
        <li><a href="adorder-history.php">注文履歴</a></li>
        <li><a href="admin-logout.php" class="logout-btn">ログアウト</a></li>
    </ul>
</div>

<main>
    <form method="get" class="search-form">
        <input type="text" name="name" placeholder="お名前" value="<?= htmlspecialchars($searchName) ?>">
        <input type="text" name="phone" placeholder="電話番号" value="<?= htmlspecialchars($searchPhone) ?>">
        <input type="text" name="address" placeholder="住所" value="<?= htmlspecialchars($searchAddress) ?>">
        <input type="text" name="email" placeholder="メールアドレス" value="<?= htmlspecialchars($searchEmail) ?>">
        <button type="submit">検索</button>
    </form>

    <div class="table-wrapper">
        <table>
            <tr>
                <th>お名前</th>
                <th>電話番号</th>
                <th>住所</th>
                <th>メールアドレス</th>
                <th>サブスク加入</th>
                <th>登録日時</th>
            </tr>
            <?php if($customers): ?>
                <?php foreach($customers as $cust): ?>
                    <tr>
                        <td><a class="name-link" href="order-history.php?customer_id=<?= $cust['customer_id'] ?>"><?= htmlspecialchars($cust['name']) ?></a></td>
                        <td><?= htmlspecialchars($cust['phone_number'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($cust['full_address'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($cust['email']) ?></td>
                        <td><?= $cust['subscr_join'] ? '加入' : '未加入' ?></td>
                        <td><?= htmlspecialchars($cust['created_at']) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="6" style="text-align:center;">顧客が見つかりません</td></tr>
            <?php endif; ?>
        </table>
    </div>
</main>

<script>
function toggleMenu(){
    document.getElementById('menu').classList.toggle('open');
}
</script>

</body>
</html>
