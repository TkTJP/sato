<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

/* ===========================
   ① ファイル到達確認
=========================== */
echo "<h2>① purchase-history.php 到達OK</h2>";

session_start();

/* ===========================
   ② セッション確認
=========================== */
echo "<h2>② session_start() OK</h2>";
echo "<h3>SESSION 中身</h3>";
echo "<pre>";
var_dump($_SESSION);
echo "</pre>";

require 'db-connect.php';

/* ===========================
   ③ DB接続確認
=========================== */
try {
    $pdo = new PDO($connect, USER, PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    echo "<h2>③ DB接続 OK</h2>";
} catch (PDOException $e) {
    echo "<h2 style='color:red;'>③ DB接続 失敗</h2>";
    echo $e->getMessage();
    exit;
}

/* ===========================
   ④ ログイン確認
=========================== */
if (empty($_SESSION['customer']['customer_id'])) {
    echo "<h2 style='color:red;'>④ ログイン情報がありません</h2>";
    exit;
}

$customer_id = (int)$_SESSION['customer']['customer_id'];
echo "<h2>④ ログイン確認 OK（customer_id = {$customer_id}）</h2>";

/* ===========================
   ⑤ 期間指定
=========================== */
$start = $_GET['start'] ?? '';
$end   = $_GET['end'] ?? '';

/* ===========================
   ⑥ 購入履歴取得
=========================== */
$sql = "
SELECT 
    p.purchase_id,
    DATE(p.purchase_date) AS purchase_date,
    p.total,
    d.product_id,
    d.quantity,
    d.price,
    pr.name AS product_name,
    pr.image
FROM purchases p
JOIN purchase_details d ON p.purchase_id = d.purchase_id
JOIN products pr ON d.product_id = pr.product_id
WHERE p.customer_id = :customer_id
";

$params = ['customer_id' => $customer_id];

if (!empty($start) && !empty($end)) {
    $sql .= " AND p.purchase_date BETWEEN :start AND :end";
    $params['start'] = $start;
    $params['end']   = $end;
}

$sql .= " ORDER BY p.purchase_date DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

echo "<h2>⑤ SQL実行OK / 取得件数：" . count($rows) . " 件</h2>";
echo "<pre>";
var_dump($rows);
echo "</pre>";

// 日付ごとにまとめる
$histories = [];
foreach ($rows as $row) {
    $date = $row['purchase_date'];
    $histories[$date]['total'] = $row['total'];
    $histories[$date]['items'][] = $row;
}

?>

<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>購入履歴</title>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style>
/* ===== 購入履歴 本体CSS ===== */
.history-wrapper {
    max-width: 600px;
    margin: 20px auto;
    padding: 10px;
}
.search-box {
    text-align: center;
    margin-bottom: 20px;
}
.search-box input {
    padding: 6px;
    border-radius: 5px;
    border: 1px solid #ccc;
}
.search-box button {
    padding: 6px 12px;
    border-radius: 5px;
    border: none;
    background: #90caf9;
    color: #fff;
    font-weight: bold;
}
.no-history {
    text-align: center;
    color: #666;
}
.history-card {
    background: #fff;
    border-radius: 12px;
    padding: 15px;
    margin-bottom: 20px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.15);
}
.history-date {
    font-weight: bold;
    margin-bottom: 10px;
    color: #666;
}
.item-row {
    display: flex;
    text-decoration: none;
    color: #000;
    margin-bottom: 12px;
}
.item-img {
    width: 70px;
    height: 70px;
    object-fit: contain;
    border-radius: 10px;
    border: 1px solid #ddd;
    margin-right: 10px;
    background: #fff;
}
.item-info {
    display: flex;
    flex-direction: column;
    justify-content: center;
}
.item-name {
    font-weight: bold;
    margin-bottom: 5px;
}
.item-price {
    color: #555;
}
.total-row {
    display: flex;
    justify-content: space-between;
    font-weight: bold;
    margin-top: 10px;
    border-top: 1px solid #ddd;
    padding-top: 10px;
}
</style>
</head>
<body>

<?php include('header.php'); ?>

<nav class="nav-bar">
    <button class="back-button" onclick="history.back()">
        <i class="fa-solid fa-arrow-left"></i>
    </button>
    <span class="nav-title">購入履歴</span>
</nav>

<div class="history-wrapper">

    <!-- 期間検索 -->
    <form method="get" class="search-box">
        <input type="date" name="start" value="<?= htmlspecialchars($start) ?>">
        〜
        <input type="date" name="end" value="<?= htmlspecialchars($end) ?>">
        <button type="submit">検索</button>
    </form>

    <?php if (empty($histories)): ?>
        <p class="no-history">購入履歴はまだありません。</p>
    <?php endif; ?>

    <?php foreach ($histories as $date => $history): ?>
        <div class="history-card">

            <div class="history-date">
                <?= htmlspecialchars($date) ?>
            </div>

            <?php foreach ($history['items'] as $item): ?>
                <a href="product-detail.php?product_id=<?= (int)$item['product_id'] ?>" class="item-row">

                    <img src="img/<?= htmlspecialchars($item['image'] ?: 'noimage.png') ?>" class="item-img" alt="商品画像">

                    <div class="item-info">
                        <p class="item-name"><?= htmlspecialchars($item['product_name']) ?></p>
                        <p class="item-price">
                            ¥<?= number_format($item['price']) ?>（税込） × <?= (int)$item['quantity'] ?>
                        </p>
                    </div>

                </a>
            <?php endforeach; ?>

            <div class="total-row">
                <span>お支払い金額：</span>
                <span>¥<?= number_format($history['total']) ?></span>
            </div>

        </div>
    <?php endforeach; ?>

</div>

</body>
</html>