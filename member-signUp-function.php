<?php
    require 'db-connect.php';
?>

<?php
    // PDO接続の確立
    // (USER, PASS, $connect は db-connect.php で定義されている前提)
    $pdo=new PDO($connect, USER, PASS);

    // --- POSTデータ受け取り ---
    $name          = $_POST['name'] ?? '';
    $email         = $_POST['email'] ?? '';
    $password      = $_POST['password'] ?? '';
    //$birthdate     = $_POST['birthdate'] ?? ''; // コメントアウトを維持
    $postal_code   = $_POST['postal_code'] ?? '';
    $prefecture    = $_POST['prefecture'] ?? '';
    $city          = $_POST['city'] ?? '';
    $street        = $_POST['street'] ?? '';
    $phone_number  = $_POST['phone_number'] ?? '';

    // パスワードハッシュ化
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // --- トランザクションとエラー処理 (try-catchを追加) ---
    try {
        // トランザクション開始
        $pdo->beginTransaction();

        // 🚨【重要】セキュリティ・整合性チェックの追加推奨ポイント
        // 1. サーバーサイドバリデーション（必須項目、形式チェック）
        // 2. メールアドレスの重複チェック
        
        // customers 登録
        $sql_customer = "INSERT INTO customers (name, email, password) VALUES (:name, :email, :password)";
        $stmt_customer = $pdo->prepare($sql_customer);
        $stmt_customer->bindParam(':name', $name);
        $stmt_customer->bindParam(':email', $email);
        $stmt_customer->bindParam(':password', $hashed_password);
        $stmt_customer->execute();

        $customer_id = $pdo->lastInsertId();

        // addresses 登録
        $sql_address = "INSERT INTO addresses (customer_id, postal_code, prefecture, city, street, phone_number)
                        VALUES (:customer_id, :postal_code, :prefecture, :city, :street, :phone_number)";
        $stmt_address = $pdo->prepare($sql_address);
        $stmt_address->bindParam(':customer_id', $customer_id);
        $stmt_address->bindParam(':postal_code', $postal_code);
        $stmt_address->bindParam(':prefecture', $prefecture);
        $stmt_address->bindParam(':city', $city);
        $stmt_address->bindParam(':street', $street);
        $stmt_address->bindParam(':phone_number', $phone_number);
        $stmt_address->execute();

        // トランザクションをコミット（確定）
        $pdo->commit();

        // 成功したら完了画面へリダイレクト
        header('Location: member-signUp-complete.php');
        exit();

    } catch (Exception $e) {
        // エラーが発生した場合、トランザクションをロールバック（取り消し）
        $pdo->rollBack();
        
        // ユーザーにエラーを通知
        // 開発環境: exit('登録中にエラーが発生しました: ' . $e->getMessage()); 
        // 本番環境:
        exit('登録中にエラーが発生しました。申し訳ありませんが、時間をおいて再度お試しください。');
    }
?>