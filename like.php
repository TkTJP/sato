<?php
require 'db-connect.php';
$pdo = new PDO($connect, USER, PASS);

if (!isset($_POST['id']) || !is_numeric($_POST['id'])) {
    echo json_encode(['success' => false]);
    exit;
}

$product_id = (int)$_POST['id'];

// 🔹 現在のいいね数を取得
$stmt = $pdo->prepare('SELECT likes FROM products WHERE product_id = ?');
$stmt->execute([$product_id]);
$current = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$current) {
    echo json_encode(['success' => false]);
    exit;
}

$newLikes = (int)$current['likes'] + 1;

// 🔹 いいね数を更新
$update = $pdo->prepare('UPDATE products SET likes = ? WHERE product_id = ?');
$update->execute([$newLikes, $product_id]);

echo json_encode(['success' => true, 'likes' => $newLikes]);
?>
