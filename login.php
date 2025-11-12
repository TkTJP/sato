<?php

require_once('db-connect.php');

session_start();

$error = '';
$email = '';

// --- 2. フォーム送信後の処理 ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // ユーザー入力を取得
    $email = htmlspecialchars(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? ''; 

    if (empty($email) || empty($password)) {
        $error = "メールアドレスとパスワードを入力してください。";
    } else {
        try {
            // --- 3. PDOによるデータベース接続 ---
            // $connect は db-connect.php で定義されていることを想定
            $pdo = new PDO($connect, USER, PASS);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // --- 4. ユーザー情報の取得と認証 ---
            $sql = "SELECT customer_id, password, name FROM customers WHERE email = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$email]);
            $customer = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($customer && password_verify($password, $customer['password'])) {
                
                // 認証成功！セッションに情報を保存
                $_SESSION['customer_id'] = $customer['customer_id'];
                $_SESSION['customer_name'] = $customer['name'];
                
                // ログイン後のページにリダイレクト
                header("Location: profile.php"); 
                exit;
                
            } else {
                // 認証失敗
                $error = "メールアドレスまたはパスワードが正しくありません。";
            }
            
        } catch (PDOException $e) {
            // データベース接続/クエリ実行エラー
            $error = "データベースエラーが発生しました。時間を置いてお試しください。";
            // error_log("PDO Error: " . $e->getMessage()); 
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