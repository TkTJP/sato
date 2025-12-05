<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
require 'db-connect.php';

try {
    $pdo = new PDO($connect, USER, PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    exit('DB接続エラー: ' . $e->getMessage());
}

if (empty($_SESSION['customer']['customer_id'])) {
    exit('ログインしてください。');
}
$customer_id = (int)$_SESSION['customer']['customer_id'];

$use_points = (int)($_POST['points'] ?? 0);
$customer_coupon_id = (int)($_POST['customer_coupon_id'] ?? 0);
$payment = $_POST['payment'] ?? '';

/* =========================
   ✅ サブスク加入確認（追加）
========================= */
$stmt = $pdo->prepare("SELECT subscr_join FROM customers WHERE customer_id = ?");
$stmt->execute([$customer_id]);
$subscr_join = (int)$stmt->fetchColumn();

/* =========================
   カート取得
========================= */
$stmt = $pdo->prepare("
SELECT c.product_id, c.quantity, p.price
FROM carts c
JOIN products p ON c.product_id = p.product_id
WHERE c.customer_id = ?
");
$stmt->execute([$customer_id]);
$cart_items = $stmt->fetchAll();

if (!$cart_items) {
    exit('カートが空です。');
}

/* =========================
   ✅ 合計再計算（送料込み）
========================= */
$total = 0;
foreach ($cart_items as $item) {
    $total += $item['price'] * $item['quantity'];
}

$shipping_fee = ($subscr_join === 1) ? 0 : 500;
$total += $shipping_fee;

/* =========================
   クーポン割引
========================= */
$discount = 0;
if ($customer_coupon_id > 0) {
    $stmt = $pdo->prepare("
    SELECT c.rate
    FROM customer_coupons cc
    JOIN coupons c ON cc.coupon_id = c.coupon_id
    WHERE cc.id = ?
      AND cc.customer_id = ?
      AND cc.is_used = 0
    ");
    $stmt->execute([$customer_coupon_id, $customer_id]);
    $coupon = $stmt->fetch();

    if (!$coupon) {
        exit('不正なクーポンです');
    }

    $discount = floor($total * ($coupon['rate'] / 100));
}

$final_total = max(0, $total - $discount - $use_points);

try {
    $pdo->beginTransaction();

    /* 購入登録 */
    $stmt = $pdo->prepare("
    INSERT INTO purchases (customer_id, total, purchase_date)
    VALUES (?, ?, NOW())
    ");
    $stmt->execute([$customer_id, $final_total]);
    $purchase_id = $pdo->lastInsertId();

    /* 明細登録 */
    $stmt = $pdo->prepare("
    INSERT INTO purchase_details
    (purchase_id, product_id, quantity, price)
    VALUES (?, ?, ?, ?)
    ");
    foreach ($cart_items as $i) {
        $stmt->execute([$purchase_id, $i['product_id'], $i['quantity'], $i['price']]);
    }

    /* ポイント減算 */
    if ($use_points > 0) {
        $stmt = $pdo->prepare("
        UPDATE customers
        SET points = points - ?
        WHERE customer_id = ? AND points >= ?
        ");
        $stmt->execute([$use_points, $customer_id, $use_points]);

        if ($stmt->rowCount() === 0) {
            throw new Exception('ポイント不足');
        }
    }

    /* クーポン使用済み */
    if ($customer_coupon_id > 0) {
        $stmt = $pdo->prepare("
        UPDATE customer_coupons
        SET is_used = 1, used_at = NOW()
        WHERE id = ? AND customer_id = ?
        ");
        $stmt->execute([$customer_coupon_id, $customer_id]);
    }

    /* 購入ポイント付与（3％） */
    $add_point = floor($final_total * 0.03);
    $stmt = $pdo->prepare("
    UPDATE customers SET points = points + ? WHERE customer_id = ?
    ");
    $stmt->execute([$add_point, $customer_id]);

    /* カート削除 */
    $stmt = $pdo->prepare("DELETE FROM carts WHERE customer_id = ?");
    $stmt->execute([$customer_id]);

    $pdo->commit();

} catch (Exception $e) {
    $pdo->rollBack();
    exit('注文失敗：' . $e->getMessage());
}

header("Location: janken.php");
exit;
