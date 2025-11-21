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
<title>管理者メニュー</title>
<style>
body {
    font-family: sans-serif;
    background: #f7f7f7;
    margin: 0;
    padding: 0;
}

header {
    background: #0078D7;
    color: white;
    padding: 15px;
    text-align: center;
    font-size: 1.3rem;
}

.container {
    max-width: 500px;
    margin: 40px auto;
    background: white;
    padding: 30px;
    border-radius: 12px;
    box-shadow: 0 3px 6px rgba(0,0,0,0.1);
    text-align: center;
}

h2 {
    margin-bottom: 20px;
}

.menu-btn {
    display: block;
    width: 100%;
    padding: 15px;
    margin: 15px 0;
    font-size: 1.2rem;
    background: #0078D7;
    color: white;
    text-decoration: none;
    border-radius: 8px;
    transition: 0.3s;
}

.menu-btn:hover {
    background: #005fa3;
}

.logout-btn {
    display: block;
    margin-top: 25px;
    color: #d9534f;
    text-decoration: none;
    font-weight: bold;
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
    <a href="purchase-manage.php" class="menu-btn">購入管理</a>

    <a href="admin-logout.php" class="logout-btn">ログアウト</a>
</div>

</body>
</html>
