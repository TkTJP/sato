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
    <title>ログアウト</title>
    <style>
        body {
            font-family: sans-serif;
            background: #f4f4f4;
            padding: 0px;
            text-align: center;
        }
        .logout-box {
            background: #fff;
            padding: 30px;
            margin: 40px auto 0;
            width: 350px;
            border-radius: 8px;
            box-shadow: 0 0 10px #ddd;
        }
        a {
            display: inline-block;
            margin-top: 20px;
            padding: 10px 20px;
            background: #0078ff;
            color: #fff;
            border-radius: 5px;
            text-decoration: none;
        }
        a:hover {
            background: #005fcc;
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
