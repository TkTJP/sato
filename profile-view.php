<?php
session_start();
require 'db-connect.php';
require 'header.php';

$pdo = new PDO($connect, USER, PASS);

// ログイン中のユーザーIDをセッションから取得（例：$_SESSION['customer']['id']）
$customer_id = $_SESSION['customer']['id'] ?? null;

// ユーザー情報を取得
$sql = $pdo->prepare('SELECT * FROM customers WHERE id = ?');
$sql->execute([$customer_id]);
$customer = $sql->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My情報閲覧画面</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

    <div class="app-container">
        <h2>My情報閲覧画面</h2>

        <?php if ($customer): ?>
            <div class="form-group">
                <label>名前</label>
                <p><?= htmlspecialchars($customer['name'], ENT_QUOTES) ?></p>
            </div>

            <div class="form-group">
                <label>メールアドレス</label>
                <p><?= htmlspecialchars($customer['email'], ENT_QUOTES) ?></p>
            </div>

            <div class="form-group">
                <label>郵便番号</label>
                <p><?= htmlspecialchars($customer['postal_code'], ENT_QUOTES) ?></p>
            </div>

            <div class="form-group">
                <label>住所</label>
                <p><?= htmlspecialchars($customer['prefecture'] . $customer['city'] . $customer['street'], ENT_QUOTES) ?></p>
            </div>

            <div class="form-group">
                <label>電話番号</label>
                <p><?= htmlspecialchars($customer['phone_number'], ENT_QUOTES) ?></p>
            </div>

            <form action="profile-edit.php" method="get">
                <button type="submit" class="edit-button">編集</button>
            </form>

        <?php else: ?>
            <p>データが見つかりません。</p>
        <?php endif; ?>

    </div>

</body>
</html>
