<?php
session_start();
require_once 'db-connect.php';
$pdo = new PDO($connect, USER, PASS);

$error = "";

// --- ログイン処理 ---
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = $_POST["email"] ?? "";
    $password = $_POST["password"] ?? "";

    // メールアドレスからユーザーを取得
    $stmt = $pdo->prepare("SELECT * FROM customers WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // ユーザーが存在しない
    if (!$user) {
        $error = "メールアドレスかパスワードが違います。";
    } 
    // パスワードが一致しない
    elseif (!password_verify($password, $user["password"])) {
        $error = "メールアドレスかパスワードが違います。";
    }
    // 管理者フラグが 1 ではない
    elseif ($user["is_admin"] != 1) {
        $error = "管理者権限がありません。";
    }
    // ログイン成功
    else {
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
<title>管理者ログイン</title>
<style>
body {
    font-family: sans-serif;
    background: #f7f7f7;
    display: flex;
    flex-direction: column;
    align-items: center;
    height: 100vh;
    margin: 0;
}

.login-box {
    background: white;
    padding: 25px 35px;
    border-radius: 10px;
    width: 320px;
    box-shadow: 0 3px 6px rgba(0,0,0,0.1);
    margin-top: 40px;
}

h2 {
    text-align: center;
}

input[type="email"],
input[type="password"] {
    width: 100%;
    padding: 10px;
    margin-top: 10px;
    margin-bottom: 15px;
    border-radius: 5px;
    border: 1px solid #aaa;
    font-size: 1rem;
}

button {
    width: 100%;
    padding: 10px;
    background: #0078D7;
    border: none;
    color: white;
    font-size: 1rem;
    border-radius: 5px;
    cursor: pointer;
}

button:hover {
    background: #005fa3;
}

.error {
    color: red;
    margin-bottom: 15px;
    text-align: center;
}
</style>
</head>
<body>

<?php require 'manager-header.php'; ?>   <!-- ← 追加した部分 -->

<div class="login-box">
    <h2>管理者ログイン</h2>

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
