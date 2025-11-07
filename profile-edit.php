<?php
session_start();
require 'db-connect.php';
require 'header.php';

$pdo = new PDO($connect, USER, PASS);
$customer_id = $_SESSION['customer']['id'] ?? null;

// 更新処理
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sql = $pdo->prepare('UPDATE customers SET name=?, email=?, password=?, postal_code=?, prefecture=?, city=?, street=?, phone_number=? WHERE id=?');
    $sql->execute([
        $_POST['name'],
        $_POST['email'],
        $_POST['password'],
        $_POST['postal_code'],
        $_POST['prefecture'],
        $_POST['city'],
        $_POST['street'],
        $_POST['phone_number'],
        $customer_id
    ]);
    header('Location: profile.php');
    exit;
}

// 現在の情報を取得
$sql = $pdo->prepare('SELECT * FROM customers WHERE id = ?');
$sql->execute([$customer_id]);
$customer = $sql->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>プロフィール編集</title>
    <link rel="stylesheet" href="style.css">

    <script>
        // 郵便番号検索
        function searchAddress() {
            const postalCode = document.getElementById('postal_code').value.replace('-', '').trim();
            if (postalCode.length !== 7) {
                alert('郵便番号は7桁で入力してください。');
                return;
            }

            fetch(`https://zipcloud.ibsnet.co.jp/api/search?zipcode=${postalCode}`)
                .then(response => response.json())
                .then(data => {
                    if (data.results) {
                        const result = data.results[0];
                        document.getElementById('prefecture').value = result.address1;
                        document.getElementById('city').value = result.address2 + result.address3;
                    } else {
                        alert('住所が見つかりませんでした。');
                    }
                })
                .catch(() => alert('住所検索に失敗しました。'));
        }
    </script>

</head>
<body>

    <div class="app-container">
        <h2>プロフィール編集</h2>

        <form action="" method="post">
            <div class="form-group">
                <label for="name">名前</label>
                <input type="text" id="name" name="name" value="<?= htmlspecialchars($customer['name'], ENT_QUOTES) ?>" required>
            </div>

            <div class="form-group">
                <label for="email">メールアドレス</label>
                <input type="email" id="email" name="email" value="<?= htmlspecialchars($customer['email'], ENT_QUOTES) ?>" required>
            </div>

            <div class="form-group">
                <label for="password">パスワード</label>
                <input type="password" id="password" name="password" value="<?= htmlspecialchars($customer['password'], ENT_QUOTES) ?>" required>
            </div>

            <!-- 郵便番号 -->
            <div class="form-group postal-group">
                <label for="postal_code">郵便番号</label>
                <input type="text" id="postal_code" name="postal_code" maxlength="7" value="<?= htmlspecialchars($customer['postal_code'], ENT_QUOTES) ?>" required>
                <button type="button" class="search-button" onclick="searchAddress()">検索</button>
            </div>

            <!-- 都道府県 -->
            <div class="form-group">
                <label for="prefecture">都道府県</label>
                <input type="text" id="prefecture" name="prefecture" value="<?= htmlspecialchars($customer['prefecture'], ENT_QUOTES) ?>" required>
            </div>

            <!-- 市区町村 -->
            <div class="form-group">
                <label for="city">市区町村</label>
                <input type="text" id="city" name="city" value="<?= htmlspecialchars($customer['city'], ENT_QUOTES) ?>" required>
            </div>

            <!-- 番地・建物名など -->
            <div class="form-group">
                <label for="street">番地・建物名・部屋番号</label>
                <input type="text" id="street" name="street" value="<?= htmlspecialchars($customer['street'], ENT_QUOTES) ?>" required>
            </div>

            <!-- 電話番号 -->
            <div class="form-group">
                <label for="phone_number">電話番号</label>
                <input type="tel" id="phone_number" name="phone_number" value="<?= htmlspecialchars($customer['phone_number'], ENT_QUOTES) ?>" required>
            </div>

            <button type="submit" class="save-button">保存</button>
        </form>
    </div>

</body>
</html>
