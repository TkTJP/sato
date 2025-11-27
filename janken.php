<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
require 'db-connect.php';

if (empty($_SESSION['customer']['customer_id'])) {
    exit('ログインしてください');
}

$customer_id = (int)$_SESSION['customer']['customer_id'];

/* -----------------------------------
   🔒 二重実行防止トークン発行
----------------------------------- */
if (empty($_SESSION['janken_token'])) {
    $_SESSION['janken_token'] = bin2hex(random_bytes(16));
}

$token = $_SESSION['janken_token'];

/* -----------------------------------
   POST処理（じゃんけん実行）
----------------------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // ✅ 戻るボタンによる再実行防止
    if (!isset($_POST['token']) || $_POST['token'] !== $_SESSION['janken_token']) {
        header("Location: stamp.php");
        exit;
    }

    // 使用済みにする（ここが超重要）
    unset($_SESSION['janken_token']);

    $user_hand = $_POST['hand'] ?? '';
    $hands = ['gu', 'choki', 'pa'];

    if (!in_array($user_hand, $hands, true)) {
        header("Location: stamp.php");
        exit;
    }

    $cpu_hand = $hands[array_rand($hands)];

    // ✅ 勝敗判定
    if ($user_hand === $cpu_hand) {
        $result = 'draw';
    } elseif (
        ($user_hand === 'gu' && $cpu_hand === 'choki') ||
        ($user_hand === 'choki' && $cpu_hand === 'pa') ||
        ($user_hand === 'pa' && $cpu_hand === 'gu')
    ) {
        $result = 'win';
    } else {
        $result = 'lose';
    }

    /* -----------------------------------
       ✅ 勝ち・あいこはスタンプ +1
    ----------------------------------- */
    if ($result === 'win' || $result === 'draw') {
        $stmt = $pdo->prepare("
            UPDATE stamp_cards
            SET stamp_count = stamp_count + 1,
                updated_at = CURRENT_TIMESTAMP
            WHERE customer_id = ?
        ");
        $stmt->execute([$customer_id]);
    }

    /* -----------------------------------
       ✅ 結果メッセージ保存
    ----------------------------------- */
    if ($result === 'win') {
        $_SESSION['stamp_message'] = "🎉 勝ちました！スタンプを1個獲得！";
    } elseif ($result === 'draw') {
        $_SESSION['stamp_message'] = "😐 あいこです！スタンプを1個獲得！";
    } else {
        $_SESSION['stamp_message'] = "😭 負けました…スタンプは増えません。";
    }

    /* -----------------------------------
       ✅ 勝敗に関係なく stamp.php へ
    ----------------------------------- */
    header("Location: stamp.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<title>じゃんけん</title>
</head>
<body>

<h2>じゃんけん</h2>

<form method="POST">
    <input type="hidden" name="token" value="<?= htmlspecialchars($token, ENT_QUOTES, 'UTF-8') ?>">
    <button type="submit" name="hand" value="gu">✊ グー</button>
    <button type="submit" name="hand" value="choki">✌ チョキ</button>
    <button type="submit" name="hand" value="pa">✋ パー</button>
</form>

</body>
</html>
