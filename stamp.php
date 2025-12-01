<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
require 'db-connect.php';

try {
    $pdo = new PDO($connect, USER, PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    exit('DB接続エラー: ' . $e->getMessage());
}

// ▼ ログイン確認
if (empty($_SESSION['customer']['customer_id'])) {
    exit('ログイン情報がありません。');
}

$customer_id = (int)$_SESSION['customer']['customer_id'];
$MAX_STAMPS = 8;

/* -------------------------------------------
    景品リスト
------------------------------------------- */
$prize_list = [
    1 => ['name' => '30%offクーポン', 'required' => 5, 'is_coupon' => true,  'coupon_id' => 1, 'point' => 0],
    2 => ['name' => '500ポイント',    'required' => 5, 'is_coupon' => false, 'coupon_id' => null, 'point' => 500],
    3 => ['name' => '10%offクーポン', 'required' => 3, 'is_coupon' => true,  'coupon_id' => 2, 'point' => 0],
    4 => ['name' => '300ポイント',    'required' => 3, 'is_coupon' => false, 'coupon_id' => null, 'point' => 300],
];

/* -------------------------------------------
    stamp_cards 取得
------------------------------------------- */
$stmt = $pdo->prepare("SELECT stamp_count FROM stamp_cards WHERE customer_id = ?");
$stmt->execute([$customer_id]);
$row = $stmt->fetch();
$current_stamps = $row ? (int)$row["stamp_count"] : 0;

if (!$row) {
    $stmtIns = $pdo->prepare("
        INSERT INTO stamp_cards 
        (customer_id, stamp_count, created_at, updated_at) 
        VALUES (?, 0, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
    ");
    $stmtIns->execute([$customer_id]);
}

/* -------------------------------------------
    POST処理（交換）
------------------------------------------- */
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["exchange_prize_id"])) {

    $prize_id = (int)$_POST["exchange_prize_id"];
    $message = "";

    if (!isset($prize_list[$prize_id])) {
        $_SESSION['stamp_message'] = "不正な操作です。";
        header("Location: stamp.php");
        exit;
    }

    $prize = $prize_list[$prize_id];

    /* ✅ クーポンのみ「未使用が既にあるか」チェック */
    $already = 0;
    if ($prize['is_coupon']) {
        $stmt = $pdo->prepare("
            SELECT 1 FROM customer_coupons
            WHERE customer_id = ?
              AND coupon_id = ?
              AND is_used = 0
            LIMIT 1
        ");
        $stmt->execute([$customer_id, $prize['coupon_id']]);
        $already = $stmt->fetchColumn();
    }

    if ($already) {
        $message = "このクーポンは未使用のものがあるため、使用後に再度交換できます。";
    } elseif ($current_stamps < $prize['required']) {
        $message = "スタンプが不足しています。（必要: {$prize['required']}）";
    } else {

        try {
            $pdo->beginTransaction();

            // ✅ スタンプ減算
            $new_stamps = max(0, $current_stamps - $prize['required']);
            $stmtUpdate = $pdo->prepare("
                UPDATE stamp_cards 
                SET stamp_count = ?, updated_at = CURRENT_TIMESTAMP 
                WHERE customer_id = ?
            ");
            $stmtUpdate->execute([$new_stamps, $customer_id]);

            // ✅ クーポン or ポイント付与
            if ($prize['is_coupon']) {
                $stmtCoupon = $pdo->prepare("
                    INSERT INTO customer_coupons 
                    (customer_id, coupon_id, is_used, acquired_at)
                    VALUES (?, ?, 0, CURRENT_TIMESTAMP)
                ");
                $stmtCoupon->execute([$customer_id, $prize['coupon_id']]);
            } elseif ($prize['point'] > 0) {
                $stmtPoint = $pdo->prepare("
                    UPDATE customers 
                    SET points = points + ? 
                    WHERE customer_id = ?
                ");
                $stmtPoint->execute([$prize['point'], $customer_id]);
            }

            $pdo->commit();
            $current_stamps = $new_stamps;
            $message = "景品『{$prize['name']}』を交換しました！（スタンプ {$prize['required']} 個消費）";

        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $message = "交換中にエラーが発生しました。" . $e->getMessage();
        }
    }

    $_SESSION['stamp_message'] = $message;
    header("Location: stamp.php");
    exit;
}

/* -------------------------------------------
    メッセージ表示
------------------------------------------- */
$message = $_SESSION['stamp_message'] ?? '';
unset($_SESSION['stamp_message']);

?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<title>スタンプカード</title>
<meta name="viewport" content="width=device-width,initial-scale=1">
<link rel="stylesheet" href="style.css">
<style>
body { font-family: Arial, sans-serif; background:#fafafa; color:#333; }
.stamp-card-container { max-width: 700px; margin: 20px auto; padding: 20px; }
.stamp-card-area { background: #f7f7e8; padding: 20px; border-radius: 10px; }
.stamp-grid { display: grid; grid-template-columns: repeat(4,1fr); gap: 10px; }
.stamp-cell { aspect-ratio:1/1; border:1px solid #ccc; display:flex; align-items:center; justify-content:center; }
.stamp-cell img { width:100%; object-fit:contain; }
.exchange-button { padding:8px 15px; border-radius:5px; border:none; font-weight:bold; }
.exchange-available { background:#69f0ae; }
.exchange-disabled { background:#eee; color:#888; }
.alert-message { background:#fff3cd; padding:10px; margin-bottom:12px; border-radius:6px; }
</style>
</head>
<body>

<?php include('header.php'); ?>

<div class="stamp-card-container">

<?php if ($message): ?>
<div class="alert-message"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<div class="stamp-card-area">
<div class="stamp-grid">
<?php for ($i = 0; $i < $MAX_STAMPS; $i++): ?>
<div class="stamp-cell">
<?php if ($i < $current_stamps): ?>
<img src="img/stamp.png">
<?php endif; ?>
</div>
<?php endfor; ?>
</div>

<p style="text-align:right;margin-top:10px;">
現在のスタンプ数：<?= $current_stamps ?> / <?= $MAX_STAMPS ?>
</p>
</div>

<h3>景品一覧</h3>

<?php foreach ($prize_list as $id => $info): ?>

<?php
if ($info['is_coupon']) {
    $stmt = $pdo->prepare("
        SELECT 1 FROM customer_coupons
        WHERE customer_id = ? AND coupon_id = ? AND is_used = 0
        LIMIT 1
    ");
    $stmt->execute([$customer_id, $info['coupon_id']]);
    $is_exchanged = (bool)$stmt->fetchColumn();
} else {
    $is_exchanged = false;
}

$can_exchange = !$is_exchanged && $current_stamps >= $info['required'];
$button_class = $can_exchange ? "exchange-available" : "exchange-disabled";
?>

<div class="prize-item">
    <div><?= htmlspecialchars($info['name']) ?>（<?= $info['required'] ?>個）</div>
    <form method="POST">
        <input type="hidden" name="exchange_prize_id" value="<?= $id ?>">
        <button class="exchange-button <?= $button_class ?>" <?= $can_exchange ? "" : "disabled" ?>>
            <?= $can_exchange ? "交換する" : ($is_exchanged ? "使用後に再交換可" : "スタンプ不足") ?>
        </button>
    </form>
</div>

<?php endforeach; ?>

</div>
</body>
</html>
