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
    exit('ログインしてください。');
}

$customer_id = (int)$_SESSION['customer']['customer_id'];
$MAX_STAMPS = 8;

$hands = [
    'choki' => 'jankenChoki.png',
    'pa'    => 'jankenPa.png',
    'gu'    => 'jankenGu.png'
];

$stmt = $pdo->prepare("SELECT stamp_count FROM stamp_cards WHERE customer_id = ?");
$stmt->execute([$customer_id]);
$row = $stmt->fetch();
$current_stamps = $row ? (int)$row['stamp_count'] : 0;

if (!$row) {
    $pdo->prepare("
        INSERT INTO stamp_cards (customer_id, stamp_count, created_at, updated_at)
        VALUES (?, 0, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
    ")->execute([$customer_id]);
}

/* -----------------------
   POST：じゃんけん処理
----------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $player = $_POST['hand'] ?? '';
    $keys = array_keys($hands);
    $computer_hand = $keys[array_rand($keys)];

    // あいこ
    if ($player === $computer_hand) {

        $stmt = $pdo->prepare("UPDATE customers SET points = points + 10 WHERE customer_id = ?");
        $stmt->execute([$customer_id]);

        $_SESSION['stamp_message'] = "あいこ！10ポイント獲得！";
        header("Location: stamp.php");
        exit;

    // 勝ち
    } elseif (
        ($player === 'choki' && $computer_hand === 'pa') ||
        ($player === 'pa'    && $computer_hand === 'gu') ||
        ($player === 'gu'    && $computer_hand === 'choki')
    ) {

        $new_stamps = min($current_stamps + 1, $MAX_STAMPS);

        $stmt = $pdo->prepare("
            UPDATE stamp_cards
            SET stamp_count = ?, updated_at = CURRENT_TIMESTAMP
            WHERE customer_id = ?
        ");
        $stmt->execute([$new_stamps, $customer_id]);

        $_SESSION['stamp_message'] = "じゃんけん勝利！スタンプ＋1！";
        header("Location: stamp.php");
        exit;

    // 負け
    } else {
        header("Location: profile.php");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<title>購入特典じゃんけん</title>
<style>
body { text-align:center; font-family:sans-serif; background:#fafafa; }
.hand-area { display:flex; justify-content:center; gap:20px; margin-top:40px; }
.hand-area img { width:140px; cursor:pointer; }
</style>
</head>
<body>

<?php include('header.php'); ?>

<h2>購入特典じゃんけん</h2>
<p>どれか1つ選んでください</p>

<div class="hand-area">
<?php foreach ($hands as $key => $img): ?>
    <form method="POST">
        <input type="hidden" name="hand" value="<?= $key ?>">
        <button type="submit" style="border:none;background:none;">
            <img src="img/<?= $img ?>">
        </button>
    </form>
<?php endforeach; ?>
</div>

</body>
</html>
