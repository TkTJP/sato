<?php
session_start();
require 'db-connect.php';

try {
    $pdo = new PDO($connect, USER, PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    if (empty($_SESSION['customer']['customer_id'])) {
        exit('ログイン情報がありません。');
    }

    $customer_id = $_SESSION['customer']['customer_id'];

    // ====== POST（登録 or 解除）処理 ======
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['join'])) {
            $sql = "UPDATE customers SET subscr_join = 1 WHERE customer_id = :customer_id";
            $message = "🎉 サブスク登録が完了しました！";
            $_SESSION['customer']['subscr_join'] = 1;
        } elseif (isset($_POST['cancel'])) {
            $sql = "UPDATE customers SET subscr_join = 0 WHERE customer_id = :customer_id";
            $message = "❎ サブスク登録を解除しました。";
            $_SESSION['customer']['subscr_join'] = 0;
        }

        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':customer_id', $customer_id, PDO::PARAM_INT);
        $stmt->execute();

        echo "<p>{$message}</p>";
        echo '<p><a href="profile.php">➡ プロフィールページに戻る</a></p>';
        exit; // 処理をここで終了
    }

    // ====== 現在のサブスク状態を取得 ======
    $sql = "SELECT subscr_join FROM customers WHERE customer_id = :customer_id";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':customer_id', $customer_id, PDO::PARAM_INT);
    $stmt->execute();
    $subscr = $stmt->fetch(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    exit('DBエラー: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8'));
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>サブスク登録</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

    <nav class="nav-bar">
          <button class="back-button" onclick="history.back()">
              <i class="fa-solid fa-arrow-left"></i>
          </button>
          <span class="nav-title">サブスクライブ</span>
    </nav>

    <?php if ($subscr && $subscr['subscr_join'] == 0): ?>
        <form method="post">
            <button type="submit" name="join">サブスクに登録する</button>
        </form>
    <?php elseif ($subscr && $subscr['subscr_join'] == 1): ?>
        <p>✅ 現在、サブスク登録中です。</p>
        <form method="post">
            <button type="submit" name="cancel">サブスクを解除する</button>
        </form>
    <?php else: ?>
        <p>サブスク情報が取得できませんでした。</p>
    <?php endif; ?>
</body>
</html>
