<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();

require 'db-connect.php';

// ログイン確認
if (empty($_SESSION['customer']['customer_id'])) {
    exit('ログイン情報がありません。');
}

$customer_id = (int)$_SESSION['customer']['customer_id'];

// DB接続
try {
    $pdo = new PDO($connect, USER, PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    exit('DB接続エラー: ' . $e->getMessage());
}

// 期間指定
$start = $_GET['start'] ?? '';
$end   = $_GET['end'] ?? '';

// 購入履歴取得
$sql = "
SELECT 
    p.purchase_id,
    DATE(p.purchase_date) AS purchase_date,
    p.total AS purchase_total,
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

$sql .= " ORDER BY p.purchase_date DESC, p.purchase_id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

// 日付ごとにまとめる（購入単位で小計、日付合計も計算）
$histories = [];
foreach ($rows as $row) {
    $date = $row['purchase_date'];
    $pid  = $row['purchase_id'];

    if (!isset($histories[$date])) {
        $histories[$date] = [
            'total_sum' => 0,   // 日付合計
            'purchases' => []
        ];
    }

    if (!isset($histories[$date]['purchases'][$pid])) {
        $histories[$date]['purchases'][$pid] = [
            'purchase_total' => $row['purchase_total'],
            'items' => []
        ];
        // 日付合計に購入ごとの合計を加算
        $histories[$date]['total_sum'] += $row['purchase_total'];
    }

    $histories[$date]['purchases'][$pid]['items'][] = $row;
}

// 画像取得関数（JPG・PNG 両方対応）
function getProductImage($filename) {
    $imgFolder = 'img/';
    if (!$filename) {
        return $imgFolder . 'noimage.png';
    }

    if (file_exists($imgFolder . $filename)) {
        return $imgFolder . $filename;
    }

    $nameWithoutExt = pathinfo($filename, PATHINFO_FILENAME);
    if (file_exists($imgFolder . $nameWithoutExt . '.jpg')) {
        return $imgFolder . $nameWithoutExt . '.jpg';
    }
    if (file_exists($imgFolder . $nameWithoutExt . '.png')) {
        return $imgFolder . $nameWithoutExt . '.png';
    }

    return $imgFolder . 'noimage.png';
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
.history-wrapper { max-width: 600px; margin: 20px auto; padding: 10px; }
.search-box { text-align: center; margin-bottom: 20px; }
.search-box input { padding: 6px; border-radius: 5px; border: 1px solid #ccc; }
.search-box button { padding: 6px 12px; border-radius: 5px; border: none; background: #90caf9; color: #fff; font-weight: bold; }
.no-history { text-align: center; color: #666; }
.history-card { background: #fff; border-radius: 12px; padding: 15px; margin-bottom: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.15); }
.history-date { font-weight: bold; margin-bottom: 10px; color: #666; font-size: 1.1em; }
.purchase-card { background: #f9f9f9; border-radius: 8px; padding: 10px; margin-bottom: 10px; }
.item-row { display: flex; text-decoration: none; color: #000; margin-bottom: 8px; }
.item-img { width: 60px; height: 60px; object-fit: contain; border-radius: 6px; border: 1px solid #ddd; margin-right: 10px; background: #fff; }
.item-info { display: flex; flex-direction: column; justify-content: center; }
.item-name { font-weight: bold; margin-bottom: 3px; font-size: 0.95em; }
.item-price { color: #555; font-size: 0.9em; }
.total-row { display: flex; justify-content: space-between; font-weight: bold; margin-top: 8px; border-top: 1px solid #ddd; padding-top: 8px; font-size: 0.95em; }
.date-total { display: flex; justify-content: flex-end; font-weight: bold; margin-top: 5px; font-size: 1em; color: #333; }
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
            <div class="history-date"><?= htmlspecialchars($date) ?></div>

            <?php foreach ($history['purchases'] as $purchase): ?>
                <div class="purchase-card">
                    <?php foreach ($purchase['items'] as $item): ?>
                        <a href="product-detail.php?product_id=<?= (int)$item['product_id'] ?>" class="item-row">
                            <img src="<?= htmlspecialchars(getProductImage($item['image'])) ?>" class="item-img" alt="商品画像">
                            <div class="item-info">
                                <p class="item-name"><?= htmlspecialchars($item['product_name']) ?></p>
                                <p class="item-price">
                                    ¥<?= number_format($item['price']) ?> × <?= (int)$item['quantity'] ?>
                                </p>
                            </div>
                        </a>
                    <?php endforeach; ?>
                    <div class="total-row">
                        <span>購入小計：</span>
                        <span>¥<?= number_format($purchase['purchase_total']) ?></span>
                    </div>
                </div>
            <?php endforeach; ?>

            <div class="date-total">
                <span>日付合計：¥<?= number_format($history['total_sum']) ?></span>
            </div>

        </div>
    <?php endforeach; ?>

</div>

</body>
</html>
