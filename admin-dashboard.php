<?php
session_start();

// ログインしていない or admin_id がない → アクセス禁止
if (!isset($_SESSION["admin_id"])) {
    header("Location: admin-login.php");
    exit;
}

$admin_name = $_SESSION["admin_name"];
?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>管理者メニュー</title>
<style>
/* 全体リセット */
*, *::before, *::after {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
}

body {
    font-family: sans-serif;
    background: #f2f2f2;
    min-height: 100vh;
    display: flex;
    flex-direction: column;
    align-items: center;
    padding-top: 10px; /* ヘッダーとの隙間 */
}

/* ページヘッダー */
header {
    background: #28a745; /* 緑色 */
    color: #fff;
    padding: 15px 10px;
    text-align: center;
    font-size: 1.3rem;
    width: 100%;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

/* 中央ブロック */
.container {
    background: #fff;
    width: 95%;       /* スマホ対応 */
    max-width: 500px; /* PCで広すぎないように */
    margin: 20px auto;
    padding: 25px 20px;
    border-radius: 12px;
    box-shadow: 0 3px 8px rgba(0,0,0,0.12);
    text-align: center;
}

/* タイトル */
h2 {
    margin-bottom: 25px;
    font-size: 1.3rem;
}

/* メニュー選択 */
.menu-btn {
    display: block;
    width: 100%;
    padding: 18px 0;
    margin: 15px 0;
    font-size: 1.15rem;
    background: #0078D7;
    color: #fff;
    text-decoration: none;
    border-radius: 8px;
    font-weight: bold;
    transition: 0.2s;
}

.menu-btn:hover {
    background: #005fa3;
}

/* ログアウト */
.logout-btn {
    display: block;
    margin-top: 30px;
    color: #d9534f;
    text-decoration: none;
    font-weight: bold;
    font-size: 1rem;
}

.logout-btn:hover {
    opacity: 0.8;
}

/* スマホ用微調整 */
@media screen and (max-width: 400px) {
    .container {
        padding: 20px 15px;
    }
    h2 {
        font-size: 1.2rem;
    }
    .menu-btn {
        font-size: 1rem;
        padding: 14px 0;
    }
    .logout-btn {
        font-size: 0.95rem;
    }
}
</style>
</head>
<body>

<?php require 'manager-header.php'; ?>  

<header>
    管理者メニュー
</header>

<div class="container">
    <h2>ようこそ、<?= htmlspecialchars($admin_name) ?> さん</h2>

    <a href="product-manage.php" class="menu-btn">商品管理</a>
    <a href="customer-manage.php" class="menu-btn">顧客管理</a>
    <a href="order-history.php" class="menu-btn">購入管理</a>

    <a href="admin-logout.php" class="logout-btn">ログアウト</a>
</div>

</body>
</html>
