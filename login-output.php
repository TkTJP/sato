<?php
    session_start();
    require 'db-connect.php';
    require 'header.php';
?>

<?php

    $pdo = new PDO($connect, USER, PASS);

    // フォーム送信をチェック
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {

        $email = $_POST['email'];
        $password = $_POST['password'];

        // 新規登録ボタンが押された場合
        if (isset($_POST['register'])) {
            try {
                // メール重複チェック
                $checkSql = $pdo->prepare('SELECT COUNT(*) FROM customers WHERE email = ?');
                $checkSql->execute([$email]);
                $count = $checkSql->fetchColumn();

                if ($count > 0) {
                    $error = 'このメールアドレスは既に登録されています。';
                } else {
                    // パスワードをハッシュ化して保存
                    $password_hash = password_hash($password, PASSWORD_DEFAULT);
                    $sql = $pdo->prepare('INSERT INTO customers (email, password) VALUES (?, ?)');
                    $sql->execute([$email, $password_hash]);

                    // セッションに保存してプロフィールへ
                    $customerId = $pdo->lastInsertId();
                    $_SESSION['customer'] = [
                        'id' => $customerId,
                        'email' => $email,
                    ];

                    header('Location: profile.php');
                    exit();
                }

            } catch (PDOException $e) {
                $error = '登録エラー: ' . $e->getMessage();
            }

        }

        // ログインボタンが押された場合
        if (isset($_POST['login'])) {
            $sql = $pdo->prepare('SELECT * FROM customers WHERE email = ?');
            $sql->execute([$email]);
            $row = $sql->fetch(PDO::FETCH_ASSOC);

            if ($row && password_verify($password, $row['password'])) {
                $_SESSION['customer'] = [
                    'id' => $row['id'],
                    'email' => $row['email'],
                ];
                header('Location: profile.php');
                exit();
            } else {
                $error = 'メールアドレスまたはパスワードが違います。';
            }
        }
    }
?>