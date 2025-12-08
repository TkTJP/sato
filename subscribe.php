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

    $message = '';

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

        if ($sql) {
            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(':customer_id', $customer_id, PDO::PARAM_INT);
            $stmt->execute();
        }
    }

    // DBから最新の状態を取得
    $sql = "SELECT subscr_join FROM customers WHERE customer_id = :customer_id";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':customer_id', $customer_id, PDO::PARAM_INT);
    $stmt->execute();
    $subscr = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$subscr) $subscr = ['subscr_join' => 0];

} catch (PDOException $e) {
    exit('DBエラー: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8'));
}
?>

<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>サブスク登録</title>
<style>
body {
    margin: 0;
    font-family: "Segoe UI", sans-serif;
    background: #f4f6f8;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    min-height: 100vh;
    text-align: center;
}

.completion-message {
    font-size: 20px;
    font-weight: bold;
    color: #4caf50;
    margin-bottom: 15px;
}

.notice-message {
    font-size: 14px;
    color: #555;
    background: #f0f0f0;
    padding: 10px 15px;
    border-radius: 10px;
    margin-bottom: 20px;
    max-width: 320px;
}

.top-button {
    display: inline-block;
    padding: 12px 25px;
    border-radius: 50px;
    border: none;
    text-decoration: none;
    color: #fff;
    font-weight: bold;
    background: linear-gradient(135deg, #4caf50, #2e7d32);
    box-shadow: 0 4px 10px rgba(0,0,0,0.15);
    transition: 0.3s;
    margin-top: 10px;
}

.top-button:hover {
    opacity: 0.9;
    transform: translateY(-2px);
}

.form-button {
    display: inline-block;
    padding: 12px 25px;
    border-radius: 50px;
    border: none;
    color: #fff;
    font-weight: bold;
    font-size: 16px;
    cursor: pointer;
    transition: 0.3s;
    margin-bottom: 15px;
}

.form-button.join {
    background: linear-gradient(135deg, #4caf50, #2e7d32);
}

.form-button.cancel {
    background: #f44336;
}

.form-button:hover {
    opacity: 0.9;
    transform: translateY(-2px);
}
</style>
</head>
<body>

<?php include 'header.php'; ?>

<?php if ($message): ?>
    <p class="completion-message"><?= htmlspecialchars($message, ENT_QUOTES) ?></p>
<?php endif; ?>

<p class="notice-message">※サブスクに登録すると、全てのご注文の送料が無料になります。</p>

<?php if ($subscr['subscr_join'] == 0): ?>
    <form method="post">
        <button type="submit" name="join" class="form-button join">サブスクに登録する</button>
    </form>
<?php else: ?>
    <form method="post">
        <button type="submit" name="cancel" class="form-button cancel">サブスクを解除する</button>
    </form>
<?php endif; ?>

<a href="profile.php" class="top-button">マイページへ戻る</a>

</body>
</html>
