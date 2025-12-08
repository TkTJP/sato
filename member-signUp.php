<?php
session_start();
require 'db-connect.php';
?>

<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<title>会員登録画面</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<style>
body {
    margin: 0;
    font-family: "Segoe UI", sans-serif;
    background: #f4f6f8;
}

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

/* 全体 */
.app-container {
    display: flex;
    justify-content: center;
    padding: 30px 15px;
}

.registration-form-container {
    width: 100%;
    max-width: 420px;
    background: #fff;
    border-radius: 16px;
    padding: 25px 20px 30px;
    box-shadow: 0 6px 15px rgba(0,0,0,0.15);
}

/* アイコン選択 */
.icon-select {
    text-align: center;
    margin-bottom: 20px;
}

.icon-select label {
    margin: 0 6px;
    cursor: pointer;
}

.icon-select input {
    display: none;
}

.icon-select img {
    width: 70px;
    height: 70px;
    border-radius: 50%;
    border: 3px solid transparent;
    transition: 0.2s;
}

.icon-select input:checked + img {
    border: 3px solid #ff5722;
    box-shadow: 0 0 8px rgba(255,87,34,0.6);
    transform: scale(1.05);
}

/* フォーム */
.form-group {
    display: flex;
    flex-direction: column;
    margin-bottom: 15px;
}

.form-group label {
    font-size: 13px;
    margin-bottom: 6px;
}

.form-group input {
    padding: 12px;
    border-radius: 10px;
    border: 1px solid #ccc;
    font-size: 15px;
}

/* 郵便番号 */
.postal-group {
    flex-direction: row;
    gap: 10px;
    align-items: center;
}

.postal-group input {
    flex: 1;
}

.search-button {
    padding: 12px 14px;
    border-radius: 10px;
    border: none;
    background: linear-gradient(135deg, #9c27b0, #673ab7);
    color: #fff;
    cursor: pointer;
}

/* 登録 */
.submit-button {
    width: 100%;
    padding: 15px;
    margin-top: 20px;
    border-radius: 50px;
    border: none;
    font-size: 16px;
    font-weight: bold;
    cursor: pointer;
    background: linear-gradient(135deg, #ff9800, #ff5722);
    color: #fff;
    box-shadow: 0 4px 10px rgba(0,0,0,0.15);
}
</style>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<script>
function searchAddress() {
    const postalCode = document.getElementById('postal_code').value.replace('-', '').trim();
    if (!postalCode.match(/^\d{7}$/)) {
        alert('郵便番号は7桁で入力してください');
        return;
    }

    fetch(`https://zipcloud.ibsnet.co.jp/api/search?zipcode=${postalCode}`)
        .then(response => response.json())
        .then(data => {
            if (data.results) {
                const r = data.results[0];
                document.getElementById('prefecture').value = r.address1;
                document.getElementById('city').value = r.address2 + r.address3;
            } else {
                alert('住所が見つかりません');
            }
        })
        .catch(() => alert('通信エラー'));
}
</script>
</head>

<body>

<?php include('header.php'); ?>

<nav class="nav-bar">
    <button class="back-button" onclick="history.back()">
        <i class="fa-solid fa-arrow-left"></i>
    </button>
    <span class="nav-title">会員登録</span>
</nav>

<div class="app-container">
<main class="registration-form-container">

<form action="member-signUp-function.php" method="post">

    <!-- ✅ アイコン選択 -->
    <div class="form-group icon-select">
        <label>プロフィール画像</label>
        <div>
            <?php for ($i=1; $i<=4; $i++): ?>
                <label>
                    <input type="radio" name="customer_image" value="<?= $i ?>" <?= $i===1?'checked':'' ?>>
                    <img src="img/icon<?= $i ?>.png" alt="icon<?= $i ?>">
                </label>
            <?php endfor; ?>
        </div>
    </div>

    <div class="form-group">
        <label>名前</label>
        <input type="text" name="name" required>
    </div>

    <div class="form-group">
        <label>メールアドレス</label>
        <input type="email" name="email" required>
    </div>

    <div class="form-group">
        <label>パスワード</label>
        <input type="password" name="password" required>
    </div>

    <div class="form-group postal-group">
        <input type="text" id="postal_code" name="postal_code" placeholder="1000001" required>
        <button type="button" class="search-button" onclick="searchAddress()">検索</button>
    </div>

    <div class="form-group">
        <input type="text" id="prefecture" name="prefecture" placeholder="都道府県" required>
    </div>

    <div class="form-group">
        <input type="text" id="city" name="city" placeholder="市区町村" required>
    </div>

    <div class="form-group">
        <input type="text" name="street" placeholder="番地・建物名・部屋番号" required>
    </div>

    <div class="form-group">
        <input type="tel" name="phone_number" placeholder="09012345678" required>
    </div>

    <button type="submit" class="submit-button">登録</button>

</form>
</main>
</div>

</body>
</html>
