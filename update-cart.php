<?php
session_start();

if (!isset($_SESSION['cart'])) {
    echo json_encode(['error' => 'no cart']);
    exit;
}

$name = $_POST['name'] ?? '';
$action = $_POST['action'] ?? '';

foreach ($_SESSION['cart'] as $i => $item) {
    if ($item['name'] === $name) {
        if ($action === 'plus') {
            $_SESSION['cart'][$i]['quantity']++;
        } elseif ($action === 'minus' && $_SESSION['cart'][$i]['quantity'] > 1) {
            $_SESSION['cart'][$i]['quantity']--;
        }
        $quantity = $_SESSION['cart'][$i]['quantity'];
    }
}

// 合計金額を再計算
$total = 0;
foreach ($_SESSION['cart'] as $item) {
    $total += $item['price'] * $item['quantity'];
}

header('Content-Type: application/json');
echo json_encode(['quantity' => $quantity, 'total' => $total]);
