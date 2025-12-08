<?php
session_start();

// ログイン済みなら member.php を飛ばして profile.php に移動
if (!empty($_SESSION['customer']['customer_id'])) {
    header("Location: profile.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>会員画面</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- ===== CSSここに組み込み ===== -->
    <style>
        body {
            margin: 0;
            font-family: "Segoe UI", sans-serif;
            background: #f4f6f8;
        }

        /* ナビバー */
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

        /* 画面中央レイアウト */
        .app-container {
            min-height: 80vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .button-area {
            width: 100%;
            max-width: 320px;
            display: flex;
            flex-direction: column;
            gap: 20px;
            padding: 20px;
        }

        /* 共通ボタン */
        .action-button {
            width: 100%;
            padding: 16px;
            font-size: 17px;
            font-weight: bold;
            border: none;
            border-radius: 50px;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 10px rgba(0,0,0,0.15);
        }

        /* 会員登録ボタン */
        .register-button {
            background: linear-gradient(135deg, #ff9800, #ff5722);
            color: #fff;
        }

        .register-button:hover {
            opacity: 0.9;
            transform: translateY(-3px);
        }

        /* ログインボタン */
        .login-button {
            background: linear-gradient(135deg, #2196f3, #0d47a1);
            color: #fff;
        }

        .login-button:hover {
            opacity: 0.9;
            transform: translateY(-3px);
        }
    </style>

    <!-- アイコン用 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
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

        <!-- 会員登録 -->
        <button class="action-button register-button"
                onclick="location.href='member-signUp.php'">
            会員登録
        </button>

        <!-- ログイン -->
        <button class="action-button login-button"
                onclick="location.href='login.php'">
            ログイン
        </button>

    </div>
</div>

</body>
</html>
