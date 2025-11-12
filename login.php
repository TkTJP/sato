<?php
require_once('db-connect.php');

// セッションの有効期限を延長（例：2時間）
session_set_cookie_params([
    'lifetime' => 7200,
    'path' => '/',
    'secure' => false, // HTTPSなら true
    'httponly' => true,
    'samesite' => 'Lax'
]);
session_start();

$error = '';
$email = '';

// --- すでにログイン中ならリダイレクト ---
if (isset($_SESSION['customer']['customer_id'])) {
    header("Location: profile.php");
    exit;
}

// --- フォーム送信後の処理 ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $email = htmlspecialchars(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? ''; 

    if (empty($email) || empty($password)) {
        $error = "メールアドレスとパスワードを入力してください。";
    } else {
        try {
            $pdo = new PDO($connect, USER, PASS);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            $sql = "SELECT customer_id, password, name, subscr_join FROM customers WHERE email = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$email]);
            $customer = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($customer && password_verify($password, $customer['password'])) {
                
                // 認証成功 → セッションに保存
                $_SESSION['customer'] = [
                    'customer_id' => $customer['customer_id'],
                    'name' => $customer['name'],
                    'subscr_join' => $customer['subscr_join']
                ];

                session_regenerate_id(true); // セッションID更新で安全性向上
                header("Location: profile.php"); 
                exit;

            } else {
                $error = "メールアドレスまたはパスワードが正しくありません。";
            }
            
        } catch (PDOException $e) {
            $error = "データベースエラーが発生しました。";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <title>ログイン</title>
</head>
<body>

<?php include('header.php'); ?>

<nav class="nav-bar">
    <button class="back-button" onclick="history.back()">
        <i class="fa-solid fa-arrow-left"></i>
    </button>
    <span class="nav-title">ログイン</span>
</nav>

<div class="login-form-container">
    <?php if ($error): ?>
        <p class="error-message"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <form action="" method="POST">
        <label for="email">メールアドレス</label>
        <input type="text" id="email" name="email" value="<?= htmlspecialchars($email) ?>" required>

        <label for="password">パスワード</label>
        <input type="password" id="password" name="password" required>

        <input type="submit" value="ログイン">
    </form>
</div>

</body>
</html>
