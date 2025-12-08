<?php
session_start();
require 'db-connect.php';

// DB接続
try {
    $pdo = new PDO($connect, USER, PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    exit('DB接続エラー: ' . $e->getMessage());
}

// セッション確認
$customer_id = $_SESSION['customer']['customer_id'] ?? null;
if (!$customer_id) {
    echo '<p>ログイン情報がありません。<a href="login.php">ログイン画面へ</a></p>';
    exit;
}

// 更新処理
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $new_password = $_POST['password'];
    $customer_image = (int)$_POST['customer_image'];

    $customer_updates = ['name = ?', 'email = ?', 'customer_image = ?'];
    $customer_params = [$name, $email, $customer_image, $customer_id];

    if (!empty($new_password)) {
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $customer_updates[] = 'password = ?';
        array_splice($customer_params, 3, 0, [$hashed_password]);
    }

    $sql_customer = 'UPDATE customers SET ' . implode(', ', $customer_updates) . ' WHERE customer_id=?';
    $pdo->prepare($sql_customer)->execute($customer_params);

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
        $sql_address = $pdo->prepare(
            'UPDATE addresses SET postal_code=?, prefecture=?, city=?, street=?, phone_number=? WHERE customer_id=?'
        );
        $sql_address->execute(array_merge($address_data, [$customer_id]));
    } else {
        $sql_address = $pdo->prepare(
            'INSERT INTO addresses (customer_id, postal_code, prefecture, city, street, phone_number, created_at)
             VALUES (?, ?, ?, ?, ?, ?, NOW())'
        );
        $sql_address->execute(array_merge([$customer_id], $address_data));
    }

    $_SESSION['customer']['name'] = $name;
    $_SESSION['customer']['email'] = $email;

    header('Location: profile-edit-complete.php');
    exit;
}

// 現在の情報取得
$sql = $pdo->prepare('
    SELECT c.name, c.email, c.customer_image,
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
<title>My情報編集</title>
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

.app-container {
    display: flex;
    justify-content: center;
    padding: 30px 15px;
}

.edit-card {
    width: 100%;
    max-width: 440px;
    background: #fff;
    border-radius: 16px;
    padding: 25px 20px 30px;
    box-shadow: 0 6px 15px rgba(0,0,0,0.15);
}

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
    font-size: 15px;
    border-radius: 10px;
    border: 1px solid #ccc;
}

.postal-group {
    display: flex;
    flex-direction: row; /* 横並び */
    gap: 8px;
    align-items: center;
}

.postal-group input {
    flex: 1;
}

.postal-group button {
    white-space: nowrap;
    padding: 12px 16px;
    border: none;
    border-radius: 10px;
    font-size: 14px;
    background: linear-gradient(135deg, #9c27b0, #673ab7);
    color: #fff;
    cursor: pointer;
}

.icon-select img {
    width: 60px;
    height: 60px;
    border-radius: 50%;
}

.save-button {
    width: 100%;
    padding: 15px;
    margin-top: 10px;
    border-radius: 50px;
    border: none;
    font-size: 16px;
    font-weight: bold;
    cursor: pointer;
    background: linear-gradient(135deg, #4caf50, #2e7d32);
    color: #fff;
    box-shadow: 0 4px 10px rgba(0,0,0,0.15);
    transition: 0.3s;
}

.save-button:hover {
    opacity: 0.9;
    transform: translateY(-3px);
}
</style>

<script>
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
                const r = data.results[0];
                document.getElementById('prefecture').value = r.address1;
                document.getElementById('city').value = r.address2 + r.address3;
            } else {
                alert('住所が見つかりませんでした。');
            }
        })
        .catch(() => alert('住所検索に失敗しました。'));
}
</script>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

<?php include('header.php'); ?>

<nav class="nav-bar">
    <button class="back-button" onclick="history.back()">
        <i class="fa-solid fa-arrow-left"></i>
    </button>
    <span class="nav-title">My情報編集</span>
</nav>

<div class="app-container">
<form action="" method="post" class="edit-card">

    <div class="form-group icon-select">
        <label>プロフィール画像</label>
        <div>
            <?php for ($i=1; $i<=4; $i++): ?>
                <label style="margin-right:10px;">
                    <input type="radio" name="customer_image" value="<?= $i ?>" <?= $customer['customer_image']==$i?'checked':'' ?>>
                    <img src="img/icon<?= $i ?>.png" alt="icon<?= $i ?>">
                </label>
            <?php endfor; ?>
        </div>
    </div>

    <div class="form-group">
        <label>名前</label>
        <input type="text" name="name" value="<?= htmlspecialchars($customer['name'] ?? '', ENT_QUOTES) ?>" required>
    </div>

    <div class="form-group">
        <label>メールアドレス</label>
        <input type="email" name="email" value="<?= htmlspecialchars($customer['email'] ?? '', ENT_QUOTES) ?>" required>
    </div>

    <div class="form-group">
        <label>パスワード（変更時のみ）</label>
        <input type="password" name="password" placeholder="変更しない場合は空欄">
    </div>

    <div class="form-group">
        <label>郵便番号</label>
        <div class="postal-group">
            <input type="text" id="postal_code" name="postal_code"
                   placeholder="郵便番号"
                   value="<?= htmlspecialchars($customer['postal_code'] ?? '', ENT_QUOTES) ?>">
            <button type="button" onclick="searchAddress()">検索</button>
        </div>
    </div>

    <div class="form-group">
        <input type="text" id="prefecture" name="prefecture"
               placeholder="都道府県"
               value="<?= htmlspecialchars($customer['prefecture'] ?? '', ENT_QUOTES) ?>">
    </div>

    <div class="form-group">
        <input type="text" id="city" name="city"
               placeholder="市区町村"
               value="<?= htmlspecialchars($customer['city'] ?? '', ENT_QUOTES) ?>">
    </div>

    <div class="form-group">
        <input type="text" name="street"
               placeholder="番地・建物名・部屋番号"
               value="<?= htmlspecialchars($customer['street'] ?? '', ENT_QUOTES) ?>">
    </div>

    <div class="form-group">
        <input type="tel" name="phone_number"
               placeholder="電話番号"
               value="<?= htmlspecialchars($customer['phone_number'] ?? '', ENT_QUOTES) ?>">
    </div>

    <button type="submit" class="save-button">保存</button>
</form>
</div>

</body>
</html>
