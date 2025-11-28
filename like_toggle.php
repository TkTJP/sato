<?php
session_start();
require 'db-connect.php';

header('Content-Type: application/json; charset=utf-8');

$pdo = new PDO($connect, USER, PASS);

// 未ログイン
if (!isset($_SESSION['customer']['id'])) {
    echo json_encode(['success' => false, 'message' => 'ログインしてください']);
    exit;
}

$customer_id = $_SESSION['customer']['id'];
$product_id = $_POST['id'] ?? null;

if (!$product_id || !is_numeric($product_id)) {
    echo json_encode(['success' => false, 'message' => '不正なIDです']);
    exit;
}

// すでにいいねしているか確認
$check = $pdo->prepare("SELECT 1 FROM likes WHERE product_id = ? AND customer_id = ?");
$check->execute([$product_id, $customer_id]);
$already = $check->fetch();

// --- トグル処理（ON/OFF切替） ---
if ($already) {
    // いいね取り消し
    $del = $pdo->prepare("DELETE FROM likes WHERE product_id = ? AND customer_id = ?");
    $del->execute([$product_id, $customer_id]);
    $liked = false;
} else {
    // いいね追加
    $add = $pdo->prepare("INSERT INTO likes(product_id, customer_id) VALUES(?, ?)");
    $add->execute([$product_id, $customer_id]);
    $liked = true;
}

// --- 最新のいいね数を取得 ---
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM likes WHERE product_id = ?");
$countStmt->execute([$product_id]);
$totalLikes = $countStmt->fetchColumn();

echo json_encode([
    'success' => true,
    'liked'   => $liked,
    'likes'   => $totalLikes
]);
exit;