<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My情報編集完了</title>

<style>
body {
    margin: 0;
    font-family: "Segoe UI", sans-serif;
    background: #f4f6f8;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    min-height: 100vh;
    text-align: center;
}

.completion-message {
    font-size: 20px;
    font-weight: bold;
    color: #4caf50;
    margin-bottom: 25px;
}

.top-button {
    display: inline-block;
    padding: 12px 25px;
    border-radius: 50px;
    border: none;
    text-decoration: none;
    color: #fff;
    font-weight: bold;
    background: linear-gradient(135deg, #4caf50, #2e7d32);
    box-shadow: 0 4px 10px rgba(0,0,0,0.15);
    transition: 0.3s;
}

.top-button:hover {
    opacity: 0.9;
    transform: translateY(-2px);
}
</style>

</head>
<body>

<?php include('header.php'); ?>

<p class="completion-message">My情報の更新が完了しました！</p>

<a href="profile.php" class="top-button">マイページへ戻る</a>

</body>
</html>
