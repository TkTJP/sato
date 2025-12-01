<?php
session_start();
require 'db-connect.php';

header('Content-Type: application/json; charset=utf-8');

$pdo = new PDO($connect, USER, PASS);

// ---------------------
// ログイン確認
// ---------------------
if (empty($_SESSION['customer']['customer_id'])) {
    echo json_encode(['success' => false, 'message' => 'ログインしてください']);
    exit;
}

$customer_id = (int)$_SESSION['customer']['customer_id'];
$product_id  = (int)($_POST['id'] ?? 0);

if ($product_id <= 0) {
    echo json_encode(['success' => false, 'message' => '不正なIDです']);
    exit;
}

// ---------------------
// 既にいいねしているか？
—---------------------
$check = $pdo->prepare("SELECT 1 FROM likes WHERE product_id = ? AND customer_id = ?");
$check->execute([$product_id, $customer_id]);
$already = $check->fetch();

// ---------------------
// いいねトグル（ON/OFF）
// ---------------------
if ($already) {
    $pdo->prepare("DELETE FROM likes WHERE product_id = ? AND customer_id = ?")
        ->execute([$product_id, $customer_id]);
    $liked = false;
} else {
    $pdo->prepare("INSERT INTO likes(product_id, customer_id) VALUES(?, ?)")
        ->execute([$product_id, $customer_id]);
    $liked = true;
}

// ---------------------
// 最新のいいね数
// ---------------------
$count = $pdo->prepare("SELECT COUNT(*) FROM likes WHERE product_id = ?");
$count->execute([$product_id]);
$total = $count->fetchColumn();

echo json_encode([
    'success' => true,
    'liked'   => $liked,
    'likes'   => $total
]);
exit;
