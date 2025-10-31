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

        <!-- 堀くんが作ってる部分あとで修正
        <header class="app-header">
            <h1>ログイン画面</h1>
            <div class="logo-area">
                <div class="logo-placeholder">ざ<span class="logo-text">SATONOMI</span></div>
            </div>
            <button class="header-button cart-button" aria-label="カート">🛒</button>
            <button class="header-button mypage-button" aria-label="マイページ">👤</button>
        </header>
        -->

        <main class="login-container">
            <button class="back-button" onclick="location.href='member.html'">←</button>

            <form class="login-form" action="">
                <p class="login-title">ログイン</p>

                <!-- メールアドレスを入力する -->
                <div class="form-group">
                    <label for="email">メールアドレス</label>
                    <input type="email" id="email" maxlength="64" placeholder="メールアドレスを入力" required>
                </div>

                <!-- パスワードを入力する -->
                <div class="form-group">
                    <label for="password">パスワード</label>
                    <input type="password" id="password" maxlength="64" placeholder="パスワードを入力" required>
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