<?php
session_start();

// セッション破棄（ログアウト処理）
$_SESSION = array();
session_destroy();
?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>ログアウト</title>
<style>
body {
    font-family: sans-serif;
    background: #f4f4f4;
    margin: 0;
    padding: 0;
    display: flex;
    flex-direction: column;
    align-items: center;
    min-height: 100vh;
}

/* ログアウトボックス */
.logout-box {
    background: #fff;
    padding: 30px 20px;
    margin: 10vh auto 0 auto;
    width: 90%;
    max-width: 400px;
    border-radius: 8px;
    box-shadow: 0 0 10px #ddd;
    text-align: center;
    position: relative;
    z-index: 1;
}

.logout-box h2 {
    margin-bottom: 15px;
}

.logout-box p {
    margin-bottom: 20px;
}

.logout-box a {
    display: inline-block;
    padding: 10px 20px;
    background: #0078ff;
    color: #fff;
    border-radius: 5px;
    text-decoration: none;
    transition: 0.2s;
}

.logout-box a:hover {
    background: #005fcc;
}

/* スマホ対応 */
@media screen and (max-width: 360px) {
    .logout-box {
        padding: 20px 15px;
    }
    .logout-box a {
        padding: 10px 15px;
        font-size: 0.95rem;
    }
}
</style>
</head>
<body>

<?php require 'manager-header.php'; ?>

<div class="logout-box">
    <h2>ログアウトしました</h2>
    <p>ご利用ありがとうございました。</p>
    <a href="admin-login.php">ログイン画面に戻る</a>
</div>

</body>
</html>
