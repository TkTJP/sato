<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>会員画面</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

    <?php include('header.php'); ?>

    <nav class="nav-bar">
          <button class="back-button" onclick="history.back()">
              <i class="fa-solid fa-arrow-left"></i>
          </button>
          <span class="nav-title">会員画面</span>
    </nav>

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