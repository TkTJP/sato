<?php
require_once('db-connect.php');
session_start();

$error = '';
$email = '';

// --- Cookieによる自動ログインチェック ---
if (!isset($_SESSION['customer_id']) && isset($_COOKIE['remember_token'])) {
    try {
        $pdo = new PDO($connect, USER, PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $token = $_COOKIE['remember_token'];
        $sql = "SELECT customer_id, name FROM customers WHERE remember_token = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$token]);
        $customer = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($customer) {
            $_SESSION['customer_id'] = $customer['customer_id'];
            $_SESSION['customer_name'] = $customer['name'];
            header("Location: profile.php");
            exit;
        }
    } catch (PDOException $e) {
        // DBエラー時はCookie削除
        setcookie('remember_token', '', time() - 3600, '/');
    }
}

// --- 通常のログイン処理 ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = htmlspecialchars(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = "メールアドレスとパスワードを入力してください。";
    } else {
        try {
            $pdo = new PDO($connect, USER, PASS);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $sql = "SELECT customer_id, password, name FROM customers WHERE email = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$email]);
            $customer = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($customer && password_verify($password, $customer['password'])) {
                // 認証成功
                $_SESSION['customer_id'] = $customer['customer_id'];
                $_SESSION['customer_name'] = $customer['name'];

                // --- ログイン保持トークンを生成 ---
                $token = bin2hex(random_bytes(32));
                $sql = "UPDATE customers SET remember_token = ? WHERE customer_id = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$token, $customer['customer_id']]);

                // Cookieに保存（30日間有効）
                setcookie('remember_token', $token, time() + (86400 * 30), "/", "", false, true);

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
        <p class="error-message"><?php echo $error; ?></p>
    <?php endif; ?>

    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST">
        <label for="email">メールアドレス</label>
        <input type="text" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required class="input-field">

        <label for="password">パスワード</label>
        <input type="password" id="password" name="password" required class="input-field">

        <input type="submit" value="ログイン" class="green-button login-button-custom">
    </form>
</div>
</body>
</html>
