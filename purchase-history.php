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
   ⑤ 購入履歴取得
=========================== */
$sql = "
SELECT 
    p.purchase_id,
    p.purchase_date,
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
ORDER BY p.purchase_date DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute(['customer_id' => $customer_id]);
$histories = $stmt->fetchAll();

echo "<h2>⑤ SQL実行OK / 取得件数：" . count($histories) . " 件</h2>";
echo "<pre>";
var_dump($histories);
echo "</pre>";
?>

<!-- ===========================
     ここから通常の画面表示
=========================== -->

<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<title>購入履歴</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="style.css">
</head>
<body>

<?php include 'header.php'; ?>

<h1>購入履歴</h1>

<?php if (empty($histories)): ?>
    <p>購入履歴はありません。</p>
<?php else: ?>
    <?php foreach ($histories as $item): ?>
        <div style="border:1px solid #ccc; padding:10px; margin:10px;">
            <p>購入日：<?= htmlspecialchars($item['purchase_date']) ?></p>
            <p>商品名：<?= htmlspecialchars($item['product_name']) ?></p>
            <p>数量：<?= (int)$item['quantity'] ?></p>
            <p>価格：<?= number_format($item['price']) ?> 円</p>
            <p>合計：<?= number_format($item['total']) ?> 円</p>

            <?php if (!empty($item['image'])): ?>
                <img src="img/<?= htmlspecialchars($item['image']) ?>" width="100">
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

</body>
</html>
