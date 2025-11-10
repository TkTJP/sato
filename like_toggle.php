<?php
session_start();
require 'db-connect.php';
$pdo = new PDO($connect, USER, PASS);

header('Content-Type: application/json; charset=utf-8');

if (!isset($_POST['id']) || !is_numeric($_POST['id'])) {
    echo json_encode(['success' => false, 'message' => '無効なリクエスト']);
    exit;
}

$product_id = (int)$_POST['id'];
$customer_id = $_SESSION['customer']['id'] ?? null;

if (!$customer_id) {
    echo json_encode(['success' => false, 'message' => 'ログインしてください']);
    exit;
}

// すでにいいね済みか確認
$check = $pdo->prepare('SELECT 1 FROM likes WHERE product_id = ? AND customer_id = ?');
$check->execute([$product_id, $customer_id]);
$liked = $check->fetch();

if ($liked) {
    // 取り消し
    $del = $pdo->prepare('DELETE FROM likes WHERE product_id = ? AND customer_id = ?');
    $del->execute([$product_id, $customer_id]);
    $liked = false;
} else {
    // 新規追加
    $ins = $pdo->prepare('INSERT INTO likes (product_id, customer_id, liked_at) VALUES (?, ?, NOW())');
    $ins->execute([$product_id, $customer_id]);
    $liked = true;
}

// 総いいね数再取得
$count = $pdo->prepare('SELECT COUNT(*) FROM likes WHERE product_id = ?');
$count->execute([$product_id]);
$total = $count->fetchColumn();

echo json_encode(['success' => true, 'liked' => $liked, 'likes' => $total]);
?>
