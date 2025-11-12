<?php
session_start();
require 'db-connect.php';

// ✅ DB接続
try {
    $pdo = new PDO($connect, USER, PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); // エラーモードを設定
} catch (PDOException $e) {
    exit('DB接続エラー: ' . $e->getMessage());
}

// ✅ セッション確認
$customer_id = $_SESSION['customer']['customer_id'] ?? null;
if (!$customer_id) {
    echo '<p>ログイン情報がありません。<a href="login.php">ログイン画面へ</a></p>';
    exit;
}

// ✅ 更新処理
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $new_password = $_POST['password']; // 新しいパスワード（空の場合もある）

    // customersテーブルのUPDATEクエリを構築
    $customer_updates = ['name = ?', 'email = ?'];
    $customer_params = [$name, $email, $customer_id];

    // パスワードが入力されている場合のみ、ハッシュ化して更新対象に含める
    if (!empty($new_password)) {
        // パスワードをハッシュ化（セキュリティ修正点）
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $customer_updates[] = 'password = ?';
        // パラメータ配列の3番目（customer_idの前）にハッシュ済みパスワードを挿入
        array_splice($customer_params, 2, 0, [$hashed_password]); 
    }
    
    // customersテーブルを更新
    $sql_customer = 'UPDATE customers SET ' . implode(', ', $customer_updates) . ' WHERE customer_id=?';
    $pdo->prepare($sql_customer)->execute($customer_params);


    // addressesテーブルにデータがあるか確認
    $check = $pdo->prepare('SELECT * FROM addresses WHERE customer_id=?');
    $check->execute([$customer_id]);

    $address_data = [
        $_POST['postal_code'],
        $_POST['prefecture'],
        $_POST['city'],
        $_POST['street'],
        $_POST['phone_number'],
    ];

    if ($check->fetch()) {
        // 既存の住所情報を更新
        $sql_address = $pdo->prepare('UPDATE addresses 
                              SET postal_code=?, prefecture=?, city=?, street=?, phone_number=?
                              WHERE customer_id=?');
        $sql_address->execute(array_merge($address_data, [$customer_id]));
    } else {
        // 未登録なら新規登録
        $sql_address = $pdo->prepare('INSERT INTO addresses (customer_id, postal_code, prefecture, city, street, phone_number, created_at)
                              VALUES (?, ?, ?, ?, ?, ?, NOW())');
        $sql_address->execute(array_merge([$customer_id], $address_data));
    }

    // ✅ セッション情報を更新（←ここを追加）
    $_SESSION['customer']['name'] = $name;
    $_SESSION['customer']['email'] = $email;

    // 更新完了後に完了画面へリダイレクト
    header('Location: profile-edit-complete.php');
    exit;
}

// ✅ 現在の情報を取得（JOIN）
$sql = $pdo->prepare('
    SELECT c.name, c.email, 
           a.postal_code, a.prefecture, a.city, a.street, a.phone_number
    FROM customers c
    LEFT JOIN addresses a ON c.customer_id = a.customer_id
    WHERE c.customer_id = ?
');
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
    <?php include('header.php'); ?> 
    <div class="app-container">
    <h2>My情報編集画面</h2>

    <form action="" method="post">
        <div class="form-group">
            <label for="name">名前</label>
            <input type="text" id="name" name="name" value="<?= htmlspecialchars($customer['name'] ?? '', ENT_QUOTES) ?>" required>
        </div>

        <div class="form-group">
            <label for="email">メールアドレス</label>
            <input type="email" id="email" name="email" value="<?= htmlspecialchars($customer['email'] ?? '', ENT_QUOTES) ?>" required>
        </div>

        <div class="form-group">
            <label for="password">パスワード <small>(※変更する場合のみ入力)</small></label>
            <input type="password" id="password" name="password" value="" placeholder="変更しない場合は空欄"> 
        </div>

        <div class="form-group postal-group">
            <label for="postal_code">郵便番号</label>
            <input type="text" id="postal_code" name="postal_code" maxlength="7" value="<?= htmlspecialchars($customer['postal_code'] ?? '', ENT_QUOTES) ?>">
            <button type="button" class="search-button" onclick="searchAddress()">検索</button>
        </div>

        <div class="form-group">
            <label for="prefecture">都道府県</label>
            <input type="text" id="prefecture" name="prefecture" value="<?= htmlspecialchars($customer['prefecture'] ?? '', ENT_QUOTES) ?>">
        </div>

        <div class="form-group">
            <label for="city">市区町村</label>
            <input type="text" id="city" name="city" value="<?= htmlspecialchars($customer['city'] ?? '', ENT_QUOTES) ?>">
        </div>

        <div class="form-group">
            <label for="street">番地・建物名・部屋番号</label>
            <input type="text" id="street" name="street" value="<?= htmlspecialchars($customer['street'] ?? '', ENT_QUOTES) ?>">
        </div>

        <div class="form-group">
            <label for="phone_number">電話番号</label>
            <input type="tel" id="phone_number" name="phone_number" value="<?= htmlspecialchars($customer['phone_number'] ?? '', ENT_QUOTES) ?>">
        </div>

        <button type="submit" class="save-button">保存</button>
        <button type="button" class="back-button" onclick="location.href='profile-view.php'">戻る</button>
    </form>
</div>

</body>
</html>
