<?php
session_start();
require_once 'db-connect.php';

$pdo = new PDO($connect, USER, PASS, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
]);

$error = "";

// --- ログイン処理 ---
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = $_POST["email"] ?? "";
    $password = $_POST["password"] ?? "";

    // メールアドレスからユーザーを取得
    $stmt = $pdo->prepare("SELECT * FROM customers WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        $error = "メールアドレスかパスワードが違います。";
    } elseif (!password_verify($password, $user["password"])) {
        $error = "メールアドレスかパスワードが違います。";
    } elseif ($user["is_admin"] != 1) {
        $error = "管理者権限がありません。";
    } else {
        // ログイン成功
        $_SESSION["admin_id"] = $user["customer_id"];
        $_SESSION["admin_name"] = $user["name"];

        header("Location: admin-dashboard.php");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>管理者ログイン</title>
<style>
body {
    font-family: sans-serif;
    background: #f7f7f7;
    display: flex;
    flex-direction: column;
    align-items: center;
    min-height: 100vh;
    margin: 0;
}

/* ログインボックス */
.login-box {
    background: white;
    padding: 25px 20px;
    border-radius: 10px;
    width: 90%;
    max-width: 400px;
    box-shadow: 0 3px 6px rgba(0,0,0,0.1);
    margin: 10vh auto;
}

h2 {
    text-align: center;
    margin-bottom: 20px;
}

input[type="email"],
input[type="password"] {
    width: 95%;
    max-width: 300px;
    padding: 12px;
    margin: 8px auto 15px auto;
    border-radius: 5px;
    border: 1px solid #aaa;
    font-size: 1rem;
    display: block;
}

button {
    width: 95%;
    max-width: 300px;
    padding: 12px;
    background: #0078D7;
    border: none;
    color: white;
    font-size: 1rem;
    border-radius: 5px;
    cursor: pointer;
    display: block;
    margin: 10px auto 0 auto;
    transition: 0.2s;
}

button:hover {
    background: #005fa3;
}

.error {
    color: red;
    margin-bottom: 15px;
    text-align: center;
}

/* スマホ用調整 */
@media screen and (max-width: 360px) {
    .login-box {
        padding: 20px 15px;
    }
    button, input[type="email"], input[type="password"] {
        font-size: 0.95rem;
        padding: 10px;
    }
}
</style>
</head>
<body>

<?php require 'manager-header.php'; ?>

<div class="login-box">
    <h2>動くかどうかの確認です</h2>

    <?php if ($error): ?>
        <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="post">
        <label>メールアドレス</label>
        <input type="email" name="email" required>

        <label>パスワード</label>
        <input type="password" name="password" required>

        <button type="submit">ログイン</button>
    </form>
</div>

</body>
</html>
