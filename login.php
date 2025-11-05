<?php
    require 'header.php';
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ログイン画面</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="app-container">
        <main class="login-container">
            <button class="back-button" onclick="location.href='member.html'">←</button>
            <form class="login-form" action="login-output.php" method="post">
                <p class="login-title">ログイン</p>

                <!-- メールアドレスを入力する -->
                <div class="form-group">
                    <label for="email">メールアドレス</label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        maxlength="64"
                        placeholder="メールアドレスを入力"
                        required
                    >
                </div>

                <!-- パスワードを入力する -->
                <div class="form-group">
                    <label for="password">パスワード</label>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        maxlength="64"
                        placeholder="パスワードを入力"
                        required
                    >
                </div>

                <!-- マイページに遷移する -->
                <div class="form-group submit-group">
                    <button type="submit" class="login-button">ログイン</button>
                </div>
            </form>
        </main>
    </div>
</body>
</html>
