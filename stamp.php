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

if (empty($_SESSION['customer']['customer_id'])) {
    exit('ログイン情報がありません。');
}

$customer_id = (int)$_SESSION['customer']['customer_id'];
$MAX_STAMPS = 8;

/* -------------------------------------------
    景品リスト
------------------------------------------- */
$prize_list = [
    1 => ['name' => '30%offクーポン', 'required' => 3, 'is_coupon' => true,  'coupon_id' => 1, 'point' => 0],
    2 => ['name' => '500ポイント',    'required' => 5, 'is_coupon' => false, 'coupon_id' => null, 'point' => 500],
    3 => ['name' => '10%offクーポン', 'required' => 5, 'is_coupon' => true,  'coupon_id' => 2, 'point' => 0],
    4 => ['name' => '300ポイント',    'required' => 3, 'is_coupon' => false, 'coupon_id' => null, 'point' => 300],
];

/* -------------------------------------------
    stamp_cards取得
------------------------------------------- */
$stmt = $pdo->prepare("SELECT stamp_count FROM stamp_cards WHERE customer_id = ?");
$stmt->execute([$customer_id]);
$row = $stmt->fetch();
$current_stamps = $row ? (int)$row["stamp_count"] : 0;

/* stamp_cardsが無ければ作成 */
if (!$row) {
    $stmtIns = $pdo->prepare("INSERT INTO stamp_cards (customer_id, stamp_count) VALUES (?, 0)");
    $stmtIns->execute([$customer_id]);
}

/* -------------------------------------------
    POST処理（景品交換＆スタンプ操作）
------------------------------------------- */
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $message = "";

    // 景品交換処理
    if (isset($_POST["exchange_prize_id"])) {
        $prize_id = (int)$_POST["exchange_prize_id"];

        if (isset($prize_list[$prize_id])) {
            $prize = $prize_list[$prize_id];

            $stmt = $pdo->prepare("SELECT 1 FROM exchange_history WHERE customer_id = ? AND prize_id = ?");
            $stmt->execute([$customer_id, $prize_id]);
            $already = $stmt->fetchColumn();

            if ($already) {
                $message = "この景品は既に交換済みです。";
            } elseif ($current_stamps < $prize['required']) {
                $message = "スタンプが不足しています。（必要: {$prize['required']}）";
            } else {
                try {
                    $pdo->beginTransaction();

                    // スタンプ減算
                    $new_stamps = max(0, $current_stamps - $prize['required']);
                    $stmtUpdate = $pdo->prepare("UPDATE stamp_cards SET stamp_count = ?, updated_at = CURRENT_TIMESTAMP WHERE customer_id = ?");
                    $stmtUpdate->execute([$new_stamps, $customer_id]);

                    // exchange_historyに記録
                    $stmtIns = $pdo->prepare("INSERT INTO exchange_history (customer_id, prize_id, created_at) VALUES (?, ?, CURRENT_TIMESTAMP)");
                    $stmtIns->execute([$customer_id, $prize_id]);

                    // ポイント or クーポン付与
                    if ($prize['is_coupon']) {
                        $stmtCoupon = $pdo->prepare("INSERT INTO customer_coupons (customer_id, coupon_id) VALUES (?, ?)");
                        $stmtCoupon->execute([$customer_id, $prize['coupon_id']]);
                    } elseif ($prize['point'] > 0) {
                        // ★ 修正（point → points）
                        $stmtPoint = $pdo->prepare("UPDATE customers SET points = points + ? WHERE customer_id = ?");
                        $stmtPoint->execute([$prize['point'], $customer_id]);
                    }

                    $pdo->commit();
                    $current_stamps = $new_stamps;

                    $message = "景品『{$prize['name']}』を交換しました！（スタンプ {$prize['required']} 個消費）";

                } catch (Exception $e) {
                    if ($pdo->inTransaction()) $pdo->rollBack();
                    $message = "交換中にエラーが発生しました。";
                }
            }
        }
    }

    // テスト用 スタンプ操作
    if (isset($_POST["stamp_action"])) {
        switch ($_POST["stamp_action"]) {
            case 'add':
                $stmt = $pdo->prepare("UPDATE stamp_cards SET stamp_count = LEAST(?, stamp_count + 1) WHERE customer_id = ?");
                $stmt->execute([$MAX_STAMPS, $customer_id]);
                $message = "スタンプを1つ追加しました";
                break;

            case 'remove':
                $stmt = $pdo->prepare("UPDATE stamp_cards SET stamp_count = GREATEST(0, stamp_count - 1) WHERE customer_id = ?");
                $stmt->execute([$customer_id]);
                $message = "スタンプを1つ削除しました";
                break;

            case 'reset':
                $stmt = $pdo->prepare("UPDATE stamp_cards SET stamp_count = 0 WHERE customer_id = ?");
                $stmt->execute([$customer_id]);
                $message = "スタンプをリセットしました";
                break;

            case 'reset_buttons':
                $stmt = $pdo->prepare("DELETE FROM exchange_history WHERE customer_id = ?");
                $stmt->execute([$customer_id]);
                $message = "交換済みボタンをリセットしました";
                break;
        }

        // 最新スタンプ取得
        $stmt = $pdo->prepare("SELECT stamp_count FROM stamp_cards WHERE customer_id = ?");
        $stmt->execute([$customer_id]);
        $current_stamps = (int)$stmt->fetchColumn();
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

/* -------------------------------------------
    交換済み景品一覧取得
------------------------------------------- */
$stmt = $pdo->prepare("SELECT prize_id FROM exchange_history WHERE customer_id = ?");
$stmt->execute([$customer_id]);
$exchanged_list = $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
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
.stamp-card-area { background: #f7f7e8; padding: 20px; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.06); }
.stamp-grid { display: grid; grid-template-columns: repeat(4,1fr); gap: 10px; padding: 5px; background:#fff; border:2px solid #ccc; border-radius:6px; }
.stamp-cell { aspect-ratio:1/1; display:flex; justify-content:center; align-items:center; border:1px solid #eee; border-radius:4px; overflow:hidden; }
.stamp-cell img { width:100%; height:100%; object-fit:contain; }
.exchange-button { padding:8px 15px; border-radius:5px; border:none; font-weight:bold; min-width:100px; }
.exchange-available { background:#69f0ae; cursor:pointer; }
.exchange-disabled { background:#eee; color:#888; cursor:not-allowed; }
.exchange-exchanged { background:#b0e0c9; cursor:not-allowed; color:#555; }
.prize-item { display:flex; justify-content:space-between; background:#e8f5e9; padding:10px; margin-bottom:8px; border-radius:8px; align-items:center; }
.alert-message { background:#fff3cd; padding:10px; margin-bottom:12px; border-radius:6px; border:1px solid #ffeeba; }
.test-buttons { margin-top:15px; }
.test-buttons form { display:inline-block; margin-right:6px; }
.test-buttons button { padding:6px 12px; border-radius:4px; border:none; cursor:pointer; background:#90caf9; color:#fff; }
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
                        <img src="img/stamp.png" alt="stamp">
                    <?php endif; ?>
                </div>
            <?php endfor; ?>
        </div>
        <p style="text-align:right; margin-top:10px;">現在のスタンプ数: <?= $current_stamps ?> / <?= $MAX_STAMPS ?></p>
    </div>

    <h3 style="margin-top:18px;">景品一覧</h3>

    <?php foreach ($prize_list as $id => $info): 
        $is_exchanged = in_array($id, $exchanged_list, true);
        $can_exchange = !$is_exchanged && $current_stamps >= $info['required'];
        $button_class = $is_exchanged ? "exchange-exchanged"
                    : ($can_exchange ? "exchange-available" : "exchange-disabled");
        $button_text = $is_exchanged ? "交換済" : "交換する";
    ?>
        <div class="prize-item">
            <div><?= htmlspecialchars($info['name']) ?>（<?= $info['required'] ?>個）</div>
            <form method="POST" style="margin:0;">
                <input type="hidden" name="exchange_prize_id" value="<?= $id ?>">
                <button class="exchange-button <?= $button_class ?>" <?= $can_exchange ? "" : "disabled" ?>>
                    <?= $button_text ?>
                </button>
            </form>
        </div>
    <?php endforeach; ?>

    <div class="test-buttons">
        <form method="POST"><input type="hidden" name="stamp_action" value="add"><button>スタンプ追加</button></form>
        <form method="POST"><input type="hidden" name="stamp_action" value="remove"><button>スタンプ削除</button></form>
        <form method="POST"><input type="hidden" name="stamp_action" value="reset"><button>スタンプリセット</button></form>
        <form method="POST"><input type="hidden" name="stamp_action" value="reset_buttons"><button>ボタンリセット</button></form>
    </div>
</div>

</body>
</html>
