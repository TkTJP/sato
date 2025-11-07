<?php
    require 'header.php';
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>会員画面</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="app-container">
        <div class="button-area">
            <!-- 会員登録画面に遷移にする -->
            <button class="action-button register-button" onclick="location.href='member-signUp.php'">会員登録</button>

            <!-- ログイン画面に遷移にする -->
            <button class="action-button login-button" onclick="location.href='login.php'">ログイン</button>
        </div>
    </div>
</body>
</html>