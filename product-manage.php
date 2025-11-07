<?php
require 'db-connect.php';
$pdo = new PDO($connect, USER, PASS);

// -----------------------------
// 更新・削除処理
// -----------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = $_POST['product_id'] ?? null;
    $command = $_POST['command'] ?? '';

    if ($product_id) {
        // 画像アップロード処理
        $image_name = null;
        if (!empty($_FILES['image']['name'])) {
            $image_name = basename($_FILES['image']['name']);
            $target = __DIR__ . "/img/" . $image_name;
            if (move_uploaded_file($_FILES['image']['tmp_name'], $target)) {
                // OK
            }
        }

        if ($command === 'update') {
            $sql = $pdo->prepare("
                UPDATE products 
                SET name = ?, price = ?, description = ?, stock = ?, image = COALESCE(?, image)
                WHERE product_id = ?
            ");
            $sql->execute([
                $_POST['name'],
                $_POST['price'],
                $_POST['description'],
                $_POST['stock'],
                $image_name,
                $product_id
            ]);

            $sql2 = $pdo->prepare("
                UPDATE product_details 
                SET is_subscribe = ?, product_explain = ?
                WHERE product_id = ?
            ");
            $sql2->execute([
                $_POST['is_subscribe'],
                $_POST['product_explain'],
                $product_id
            ]);
        }

        if ($command === 'delete') {
            $pdo->prepare("DELETE FROM products WHERE product_id = ?")->execute([$product_id]);
            $pdo->prepare("DELETE FROM product_details WHERE product_id = ?")->execute([$product_id]);
        }
    }

    // 処理後にリロード（F5での再送信防止）
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// -----------------------------
// 商品一覧の取得
// -----------------------------
$sql = $pdo->query("
    SELECT p.*, d.is_subscribe, d.product_explain 
    FROM products p 
    LEFT JOIN product_details d ON p.product_id = d.product_id 
    ORDER BY p.product_id DESC
");
$products = $sql->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>商品管理</title>
  <style>
    body {
      font-family: sans-serif;
      margin: 0;
      padding: 20px;
      background-color: #f9f9f9;
    }

    h1 {
      text-align: center;
      margin-bottom: 20px;
    }

    .table-container {
      overflow-x: auto;
      -webkit-overflow-scrolling: touch;
      background: white;
      border-radius: 8px;
      box-shadow: 0 0 10px rgba(0,0,0,0.1);
    }

    table {
      border-collapse: collapse;
      width: 100%;
      min-width: 900px;
      white-space: nowrap;
    }

    th, td {
      border: 1px solid #ccc;
      padding: 8px;
      text-align: left;
      vertical-align: middle;
    }

    th {
      background: #f0f0f0;
    }

    img {
      width: 80px;
      height: 80px;
      object-fit: cover;
      border-radius: 6px;
      display: block;
      margin: 0 auto 5px;
    }

    input[type="file"] {
      width: 100%;
      max-width: 120px;
      font-size: 0.8rem;
      white-space: normal;
    }

    textarea, input[type="text"], input[type="number"], select {
      width: 100%;
      box-sizing: border-box;
      font-size: 0.9rem;
    }

    .btn {
      display: inline-block;
      padding: 6px 10px;
      margin: 2px 0;
      border: none;
      border-radius: 5px;
      cursor: pointer;
      font-size: 0.85rem;
      color: white;
    }

    .btn-update {
      background-color: #4CAF50;
    }

    .btn-delete {
      background-color: #E74C3C;
    }

    .btn-insert {
      background-color: #3498DB;
      display: block;
      margin: 20px auto;
      text-align: center;
      text-decoration: none;
      padding: 10px 20px;
      width: 200px;
      border-radius: 6px;
    }

    .btn-insert:hover {
      opacity: 0.9;
    }

    @media (max-width: 600px) {
      table {
        font-size: 0.85rem;
      }
      img {
        width: 60px;
        height: 60px;
      }
      input[type="file"] {
        max-width: 100px;
      }
    }
  </style>
</head>
<body>
  <h1>商品管理</h1>

  <div class="table-container">
    <table>
      <tr>
        <th>No</th>
        <th>画像</th>
        <th>商品名</th>
        <th>価格</th>
        <th>商品説明</th>
        <th>在庫</th>
        <th>販売タイプ</th>
        <th>商品詳細説明</th>
        <th>操作</th>
      </tr>

      <?php foreach ($products as $index => $row): ?>
      <tr>
        <form action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>" method="post" enctype="multipart/form-data">
          <input type="hidden" name="product_id" value="<?= htmlspecialchars($row['product_id']) ?>">

          <td><?= $index + 1 ?></td>
          <td style="text-align:center;">
            <?php if (!empty($row['image'])): ?>
              <img src="img/<?= htmlspecialchars($row['image']) ?>" alt="商品画像">
            <?php endif; ?>
            <input type="file" name="image">
          </td>
          <td><input type="text" name="name" value="<?= htmlspecialchars($row['name']) ?>"></td>
          <td><input type="number" name="price" value="<?= htmlspecialchars($row['price']) ?>"></td>
          <td><textarea name="description"><?= htmlspecialchars($row['description']) ?></textarea></td>
          <td><input type="number" name="stock" value="<?= htmlspecialchars($row['stock']) ?>"></td>
          <td>
            <select name="is_subscribe">
              <option value="0" <?= $row['is_subscribe'] == 0 ? 'selected' : '' ?>>通常販売</option>
              <option value="1" <?= $row['is_subscribe'] == 1 ? 'selected' : '' ?>>サブスク</option>
            </select>
          </td>
          <td><textarea name="product_explain"><?= htmlspecialchars($row['product_explain']) ?></textarea></td>
          <td>
            <button type="submit" name="command" value="update" class="btn btn-update">更新</button><br>
            <button type="submit" name="command" value="delete" class="btn btn-delete" onclick="return confirm('本当に削除しますか？');">削除</button>
          </td>
        </form>
      </tr>
      <?php endforeach; ?>
    </table>
  </div>

  <a href="product-insert.php" class="btn btn-insert">＋ 商品を追加する</a>
</body>
</html>
