<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

// ===== ① ファイル到達確認 =====
echo "<h2>① purchase-history.php 到達OK</h2>";

// ===== ② セッション開始確認 =====
session_start();
echo "<h2>② session_start() OK</h2>";

// ===== ③ セッション中身確認 =====
echo "<h3>SESSION 中身</h3>";
echo "<pre>";
var_dump($_SESSION);
echo "</pre>";

// ===== ④ DB接続確認 =====
require 'db-connect.php';

try {
    $pdo = new PDO($connect, USER, PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    echo "<h2>④ DB接続 OK</h2>";
} catch (PDOException $e) {
    echo "<h2 style='color:red;'>④ DB接続 失敗</h2>";
    echo $e->getMessage();
    exit;
}

// ===== ⑤ ログイン確認 =====
if (empty($_SESSION['customer']['customer_id'])) {
    echo "<h2 style='color:red;'>⑤ ログイン情報が存在しません</h2>";
    exit;
}

$customer_id = (int)$_SESSION['customer']['customer_id'];
echo "<h2>⑤ ログイン確認 OK（customer_id = {$customer_id}）</h2>";

// ===== ⑥ 購入履歴SQL確認 =====
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
ORDER BY p.purchase_date DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute(['customer_id' => $customer_id]);
$rows = $stmt->fetchAll();

echo "<h2>⑥ SQL実行 OK</h2>";
echo "<h3>取得件数：" . count($rows) . " 件</h3>";

echo "<pre>";
var_dump($rows);
echo "</pre>";

echo "<h1 style='color:green;'>✅ ここまで全て表示されれば「遷移・セッション・DB・SQL」は完全に正常です</h1>";
exit;
