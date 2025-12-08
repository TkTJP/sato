<?php
session_start();
require 'db-connect.php';

// DB接続
try {
    $pdo = new PDO($connect, USER, PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    exit('DB接続エラー: ' . $e->getMessage());
}

// ログイン確認
if (!isset($_SESSION['customer']['customer_id'])) {
    echo '<p>ログイン情報がありません。<a href="login.php">ログイン画面へ</a></p>';
    exit;
}

$customer_id = $_SESSION['customer']['customer_id'];

// ユーザー情報取得
$sql = $pdo->prepare('
    SELECT c.name, c.email, c.customer_image,
           a.postal_code, a.prefecture, a.city, a.street, a.phone_number
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
<title>My情報閲覧</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<style>
body {
    margin: 0;
    font-family: "Segoe UI", sans-serif;
    background: #f4f6f8;
}

.nav-bar {
    display: flex;
    align-items: center;
    height: 50px;
    background: #fff;
    border-bottom: 1px solid #ddd;
    padding: 0 10px;
}

.nav-title {
    margin: 0 auto;
    font-size: 18px;
    font-weight: bold;
}

.back-button {
    background: none;
    border: none;
    font-size: 18px;
    cursor: pointer;
}

.app-container {
    display: flex;
    justify-content: center;
    padding: 30px 15px;
}

.profile-card {
    width: 100%;
    max-width: 420px;
    background: #fff;
    border-radius: 16px;
    padding: 25px 20px 30px;
    box-shadow: 0 6px 15px rgba(0,0,0,0.15);
}

.form-group {
    display: flex;
    flex-direction: column;
    margin-bottom: 15px;
}

.form-group label {
    font-size: 13px;
    color: #555;
    margin-bottom: 5px;
}

.form-group p {
    margin: 0;
    font-size: 15px;
}

.profile-image {
    text-align: center;
}

.profile-image img {
    width: 110px;
    height: 110px;
    border-radius: 50%;
    object-fit: cover;
}

.edit-button {
    width: 100%;
    padding: 15px;
    border-radius: 50px;
    border: none;
    font-size: 16px;
    font-weight: bold;
    cursor: pointer;
    background: linear-gradient(135deg, #2196f3, #0d47a1);
    color: #fff;
    box-shadow: 0 4px 10px rgba(0,0,0,0.15);
    transition: 0.3s;
}

.edit-button:hover {
    opacity: 0.9;
    transform: translateY(-3px);
}
</style>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

<?php include('header.php'); ?>

<nav class="nav-bar">
    <button class="back-button" onclick="history.back()">
        <i class="fa-solid fa-arrow-left"></i>
    </button>
    <span class="nav-title">My情報閲覧</span>
</nav>

<div class="app-container">

<?php if ($customer): ?>
    <div class="profile-card">

        <div class="form-group profile-image">
            <label>プロフィール画像</label>
            <img src="img/icon<?= (int)$customer['customer_image'] ?>.png" alt="プロフィール画像">
        </div>

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
            <p><?= htmlspecialchars(($customer['prefecture'] ?? '') . ($customer['city'] ?? '') . ($customer['street'] ?? ''), ENT_QUOTES) ?></p>
        </div>

        <div class="form-group">
            <label>電話番号</label>
            <p><?= htmlspecialchars($customer['phone_number'] ?? '', ENT_QUOTES) ?></p>
        </div>

        <form action="profile-edit.php" method="get">
            <button type="submit" class="edit-button">編集</button>
        </form>

    </div>
<?php else: ?>
    <p>データが見つかりません。</p>
<?php endif; ?>

</div>
</body>
</html>
