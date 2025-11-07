<?php
require 'db-connect.php';

function saveImage($file) {
    if (isset($file) && $file['error'] === UPLOAD_ERR_OK) {
        $uploadDir = 'img/';
        $originalName = basename($file['name']);
        $imageName = uniqid() . '_' . $originalName;
        $uploadPath = $uploadDir . $imageName;

        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
        if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
            return $imageName;
        }
    }
    return null;
}

$pdo = new PDO($connect, USER, PASS);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['command']) && $_POST['command'] === 'insert') {
    try {
        $pdo->beginTransaction();

        $imageName = saveImage($_FILES['image']);

        // products 登録
        $sql = $pdo->prepare('INSERT INTO products (name, description, price, stock, image) VALUES (?, ?, ?, ?, ?)');
        $sql->execute([
            $_POST['name'], $_POST['description'], $_POST['price'], $_POST['stock'], $imageName
        ]);

        $product_id = $pdo->lastInsertId();

        // product_details 登録
        $detail = $pdo->prepare('INSERT INTO product_details (product_id, is_subscribe, product_explain) VALUES (?, ?, ?)');
        $detail->execute([
            $product_id,
            $_POST['is_subscribe'] ?? 0,
            $_POST['product_explain'] ?? ''
        ]);

        $pdo->commit();
        $message = "商品を登録しました！";
    } catch (Exception $e) {
        $pdo->rollBack();
        $message = "エラーが発生しました: " . htmlspecialchars($e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<title>商品登録</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<style>
body {
    font-family: sans-serif;
    background-color: #fafafa;
    margin: 0;
    padding: 20px;
}

h1 {
    text-align: center;
    color: #333;
}

form {
    display: flex;
    flex-direction: column;
    max-width: 500px;
    margin: 0 auto;
    background: #fff;
    padding: 20px;
    border-radius: 10px;
    box-shadow: 0 3px 6px rgba(0,0,0,0.1);
}

label {
    margin-top: 15px;
    font-weight: bold;
    color: #444;
}

input[type="text"],
textarea,
select {
    width: 100%;
    padding: 10px;
    border: 1px solid #ccc;
    border-radius: 5px;
    margin-top: 5px;
    box-sizing: border-box;
    font-size: 1rem;
}

input[type="file"] {
    margin-top: 8px;
}

button {
    margin-top: 20px;
    padding: 12px;
    font-size: 1.1rem;
    background-color: #0078D7;
    color: white;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    transition: background 0.2s ease;
}

button:hover {
    background-color: #005fa3;
}

.message {
    text-align: center;
    color: green;
    font-weight: bold;
    margin-top: 10px;
}

.link-btn {
    display: block;
    text-align: center;
    margin-top: 20px;
    background-color: #555;
    color: white;
    text-decoration: none;
    padding: 12px;
    border-radius: 6px;
}

.link-btn:hover {
    background-color: #333;
}

/* スマホ対応 */
@media (max-width: 600px) {
    form {
        width: 90%;
        padding: 15px;
    }
    button, .link-btn {
        font-size: 1rem;
    }
}
</style>
</head>
<body>

<h1>商品登録フォーム</h1>

<?php if ($message): ?>
    <p class="message"><?= htmlspecialchars($message) ?></p>
<?php endif; ?>

<form action="product-insert.php" method="post" enctype="multipart/form-data">
    <input type="hidden" name="command" value="insert">

    <label>画像アップロード</label>
    <input type="file" name="image" accept="image/*">

    <label>商品名</label>
    <input type="text" name="name" required>

    <label>商品説明</label>
    <textarea name="description" rows="3"></textarea>

    <label>価格</label>
    <input type="text" name="price">

    <label>在庫</label>
    <input type="text" name="stock">

    <label>販売タイプ</label>
    <select name="is_subscribe">
        <option value="0">通常販売</option>
        <option value="1">サブスク</option>
    </select>

    <label>商品詳細説明</label>
    <textarea name="product_explain" rows="4"></textarea>

    <button type="submit">登録する</button>
</form>

<a href="product-manage.php" class="link-btn">商品管理ページへ</a>

</body>
</html>
