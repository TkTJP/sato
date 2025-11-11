<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
require 'db-connect.php';

try {
    $pdo = new PDO($connect, USER, PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // ===== メールアドレス重複チェック =====
    if (isset($_SESSION['customer'])) {
        $customer_id = $_SESSION['customer']['customer_id'];
        $sql = $pdo->prepare('SELECT * FROM customers WHERE email = ? AND customer_id != ?');
        $sql->execute([$_POST['email'], $customer_id]);
    } else {
        $sql = $pdo->prepare('SELECT * FROM customers WHERE email = ?');
        $sql->execute([$_POST['email']]);
    }

    if (empty($sql->fetchAll())) {

        // パスワードをハッシュ化
        $hashed_password = password_hash($_POST['password'], PASSWORD_DEFAULT);

        if (isset($_SESSION['customer'])) {
            // ===== 既存ユーザー更新 =====
            $customer_id = $_SESSION['customer']['customer_id'];

            $updateCustomer = $pdo->prepare('
                UPDATE customers 
                SET name = ?, email = ?, password = ? 
                WHERE customer_id = ?
            ');
            $updateCustomer->execute([
                $_POST['name'],
                $_POST['email'],
                $hashed_password,
                $customer_id
            ]);

            $updateAddress = $pdo->prepare('
                UPDATE addresses 
                SET postal_code = ?, prefecture = ?, city = ?, street = ?, phone_number = ? 
                WHERE customer_id = ?
            ');
            $updateAddress->execute([
                $_POST['postal_code'],
                $_POST['prefecture'],
                $_POST['city'],
                $_POST['street'],
                $_POST['phone_number'],
                $customer_id
            ]);

        } else {
            // ===== 新規登録処理 =====
            $insertCustomer = $pdo->prepare('
                INSERT INTO customers (name, email, password, created_at, subscr_join)
                VALUES (?, ?, ?, NOW(), 0)
            ');
            $insertCustomer->execute([
                $_POST['name'],
                $_POST['email'],
                $hashed_password
            ]);

            $customer_id = $pdo->lastInsertId();

            $insertAddress = $pdo->prepare('
                INSERT INTO addresses (customer_id, postal_code, prefecture, city, street, phone_number, created_at)
                VALUES (?, ?, ?, ?, ?, ?, NOW())
            ');
            $insertAddress->execute([
                $customer_id,
                $_POST['postal_code'],
                $_POST['prefecture'],
                $_POST['city'],
                $_POST['street'],
                $_POST['phone_number']
            ]);
        }

        // ===== 正常完了 → リダイレクト =====
        header('Location: member-signUp-complete.php');
        exit;

    } else {
        echo '<p style="color:red;">そのメールアドレスは既に使用されています。</p>';
    }

} catch (PDOException $e) {
    echo "<p style='color:red;'>DBエラー: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . "</p>";
}
?>
