<?php
session_start();
require 'db-connect.php';

// ----------------------------------
// DB接続
// ----------------------------------
try {
    $pdo = new PDO($connect, USER, PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    exit('DB接続エラー: ' . $e->getMessage());
}

// ----------------------------------
// ログインチェック
// ----------------------------------
if (empty($_SESSION['customer']['customer_id'])) {
    exit('ログイン情報がありません。');
}

$customer_id = (int)$_SESSION['customer']['customer_id'];
$message = '';
$MAX_STAMPS = 8;

// ----------------------------------
// 景品マスタ（固定）
// ----------------------------------
$prize_list = [
    1 => ['name' => '30%offクーポン', 'required' => 3],
    2 => ['name' => '500ポイント',    'required' => 5],
    3 => ['name' => '10%offクーポン', 'required' => 5],
    4 => ['name' => '300ポイント',    'required' => 3],
];

// ----------------------------------
// stamp_cards にレコードが存在するかチェック
// ----------------------------------
$sql = $pdo->prepare("SELECT stamp_count FROM stamp_cards WHERE customer_id = ?");
$sql->execute([$customer_id]);
$row = $sql->fetch(PDO::FETCH_ASSOC);

if (!$row) {
    $pdo->prepare("INSERT INTO stamp_cards (customer_id, stamp_count) VALUES (?, 0)")
        ->execute([$customer_id]);
    $current_stamps = 0;
} else {
    $current_stamps = (int)$row["stamp_count"];
}

// ----------------------------------
// 景品交換処理
// ----------------------------------
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["exchange_prize_id"])) {

    $prize_id = (int)$_POST["exchange_prize_id"];

    // マスタチェック
    if (!array_key_exists($prize_id, $prize_list)) {
        $message = "不正な景品IDです。";
    } else {

        $prize_name = $prize_list[$prize_id]['name'];
        $required   = $prize_list[$prize_id]['required'];

        // 交換済みか確認
        $sql = $pdo->prepare("SELECT 1 FROM exchange_history WHERE customer_id = ? AND prize_id = ?");
        $sql->execute([$customer_id, $prize_id]);
        $already = $sql->fetchColumn();

        if ($already) {
            $message = "この景品は既に交換済みです。";
        } elseif ($current_stamps < $required) {
            $message = "スタンプが不足しています。（必要: {$required}）";
        } else {

            // ----------------------------------
            // トランザクション開始
            // ----------------------------------
            try {
                $pdo->beginTransaction();

                $new_stamps = max(0, $current_stamps - $required);

                // スタンプの更新
                $pdo->prepare("UPDATE stamp_cards SET stamp_count = ? WHERE customer_id = ?")
                    ->execute([$new_stamps, $customer_id]);

                // 交換履歴追加
                $pdo->prepare("INSERT INTO exchange_history (customer_id, prize_id) VALUES (?, ?)")
                    ->execute([$customer_id, $prize_id]);

                $pdo->commit();

                $message = "景品『{$prize_name}』を交換しました！ （スタンプ {$required} 個消費）";
                $current_stamps = $new_stamps;

            } catch (Exception $e) {
                $pdo->rollBack();
                $message = "交換処理中にエラーが発生しました。";
            }
        }
    }
}

// ----------------------------------
// 交換済み景品一覧
// ----------------------------------
$sql = $pdo->prepare("SELECT prize_id FROM exchange_history WHERE customer_id = ?");
$sql->execute([$customer_id]);
$exchanged_list = $sql->fetchAll(PDO::FETCH_COLUMN);
?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<title>スタンプカード</title>
<link rel="stylesheet" href="style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style>
.stamp-card-container { max-width: 600px; margin: 20px auto; padding: 20px; }
.stamp-card-area { background: #f7f7e8; padding: 20px; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
.stamp-grid { display: grid; grid-template-columns: repeat(4,1fr); gap: 10px; padding: 5px; background:#fff; border:2px solid #ccc; }
.stamp-cell { aspect-ratio:1/1; display:flex; justify-content:center; align-items:center; border:1px solid #eee; }
.stamp-cell img { width:80%; height:80%; filter: invert(10%) sepia(90%) saturate(700%) hue-rotate(330deg) brightness(1.2); }
.exchange-button { padding:8px 15px; border-radius:5px; border:none; font-weight:bold; min-width:100px; }
.exchange-available { background:#69f0ae; cursor:pointer; }
.exchange-disabled { background:#eee; color:#888; cursor:not-allowed; }
.exchange-exchanged { background:#b0e0c9; cursor:not-allowed; color:#555; }
.prize-item { display:flex; justify-content:space-between; background:#e8f5e9; padding:10px; margin-bottom:5px; border-radius:8px; }
</style>
</head>
<body>

<?php include('header.php'); ?>

<div class="stamp-card-container">

<h2>スタンプカード</h2>

<?php if ($message): ?>
    <div class="alert-message"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<div class="stamp-card-area">
    <div class="stamp-grid">
        <?php for ($i = 0; $i < $MAX_STAMPS; $i++): ?>
            <div class="stamp-cell">
                <?php if ($i < $current_stamps): ?>
                    <img src="sato/img/stamp.png" alt="stamp">
                <?php endif; ?>
            </div>
        <?php endfor; ?>
    </div>
    <p style="text-align:right; margin-top:10px;">現在のスタンプ数: <?= $current_stamps ?> / <?= $MAX_STAMPS ?></p>
</div>

<h3>景品一覧</h3>

<?php foreach ($prize_list as $id => $info): ?>
<?php
    $is_exchanged = in_array($id, $exchanged_list);
    $can_exchange = !$is_exchanged && $current_stamps >= $info['required'];

    $button_class = $is_exchanged
        ? "exchange-exchanged"
        : ($can_exchange ? "exchange-available" : "exchange-disabled");

    $button_text = $is_exchanged ? "交換済" : "交換する";
?>
<div class="prize-item">
    <div>
        <?= htmlspecialchars($info['name']) ?>（<?= $info['required'] ?>個）
    </div>

    <form method="POST">
        <input type="hidden" name="exchange_prize_id" value="<?= $id ?>">
        <button class="exchange-button <?= $button_class ?>" <?= $can_exchange ? "" : "disabled" ?>>
            <?= $button_text ?>
        </button>
    </form>
</div>
<?php endforeach; ?>

</div>

</body>
</html>
