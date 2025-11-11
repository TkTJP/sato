<?php
session_start();
require 'db-connect.php';

// ✅ DB接続
try {
    $pdo = new PDO($connect, USER, PASS);
} catch (PDOException $e) {
    exit('DB接続エラー: ' . $e->getMessage());
}

// ✅ セッション確認
if (!isset($_SESSION['customer']['customer_id'])) {
    echo '<p>ログイン情報がありません。<a href="login.php">ログイン画面へ</a></p>';
    exit;
}

$customer_id = $_SESSION['customer']['customer_id'];

// ✅ ユーザー情報を取得（customers と addresses を結合）
$sql = $pdo->prepare('
    SELECT c.name, c.email, a.postal_code, a.prefecture, a.city, a.street, a.phone_number
    FROM customers c
    LEFT JOIN addresses a ON c.customer_id = a.customer_id
    WHERE c.customer_id = ?
');
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
    
<?php include('header.php'); ?>

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
            <p><?= htmlspecialchars($customer['postal_code'] ?? '', ENT_QUOTES) ?></p>
        </div>

        <div class="form-group">
            <label>住所</label>
            <p><?= htmlspecialchars(
                ($customer['prefecture'] ?? '') . 
                ($customer['city'] ?? '') . 
                ($customer['street'] ?? ''),
                ENT_QUOTES
            ) ?></p>
        </div>

        <div class="form-group">
            <label>電話番号</label>
            <p><?= htmlspecialchars($customer['phone_number'] ?? '', ENT_QUOTES) ?></p>
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
