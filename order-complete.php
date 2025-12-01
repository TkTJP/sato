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

/* =========================
   ログイン確認
========================= */
if (empty($_SESSION['customer']['customer_id'])) {
    exit('ログインしてください。');
}
$customer_id = (int)$_SESSION['customer']['customer_id'];

/* =========================
   POSTデータ
========================= */
$use_points  = (int)($_POST['points'] ?? 0);
$coupon_id   = (int)($_POST['coupon_id'] ?? 0);
$payment     = $_POST['payment'] ?? '';

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
   ✅ 合計金額をDBから再計算（改ざん防止）
========================= */
$total = 0;
foreach ($cart_items as $item) {
    $total += $item['price'] * $item['quantity'];
}

/* =========================
   ✅ クーポン割引取得
========================= */
$discount = 0;
if ($coupon_id > 0) {
    $stmt = $pdo->prepare("
        SELECT c.rate
        FROM customer_coupons cc
        JOIN coupons c ON cc.coupon_id = c.coupon_id
        WHERE cc.customer_id = ?
          AND cc.coupon_id = ?
          AND cc.is_used = 0
        LIMIT 1
    ");
    $stmt->execute([$customer_id, $coupon_id]);
    $coupon = $stmt->fetch();

    if (!$coupon) {
        exit('不正なクーポンです。');
    }

    $discount = floor($total * ($coupon['rate'] / 100));
}

$final_total = max(0, $total - $discount - $use_points);

try {
    $pdo->beginTransaction();

    /* =========================
       ① purchases 登録
    ========================= */
    $stmt = $pdo->prepare("
        INSERT INTO purchases (customer_id, total, purchase_date)
        VALUES (?, ?, CURRENT_TIMESTAMP)
    ");
    $stmt->execute([$customer_id, $final_total]);
    $purchase_id = $pdo->lastInsertId();

    /* =========================
       ② purchase_details 登録
    ========================= */
    $stmtDetail = $pdo->prepare("
        INSERT INTO purchase_details
        (purchase_id, product_id, quantity, price)
        VALUES (?, ?, ?, ?)
    ");

    foreach ($cart_items as $item) {
        $stmtDetail->execute([
            $purchase_id,
            $item['product_id'],
            $item['quantity'],
            $item['price']
        ]);
    }

    /* =========================
       ③ ✅ ポイント減算（残高チェック）
    ========================= */
    if ($use_points > 0) {
        $stmt = $pdo->prepare("
            UPDATE customers
            SET points = points - ?
            WHERE customer_id = ? AND points >= ?
        ");
        $stmt->execute([$use_points, $customer_id, $use_points]);

        if ($stmt->rowCount() === 0) {
            throw new Exception('ポイント残高が不足しています。');
        }
    }

    /* =========================
       ④ ✅ 指定クーポンを使用済みに.
    ========================= */
    if ($coupon_id > 0) {
        $stmt = $pdo->prepare("
            UPDATE customer_coupons
            SET is_used = 1, used_at = CURRENT_TIMESTAMP
            WHERE customer_id = ? AND coupon_id = ? AND is_used = 0
        ");
        $stmt->execute([$customer_id, $coupon_id]);

        if ($stmt->rowCount() === 0) {
            throw new Exception('クーポンの使用に失敗しました。');
        }
    }
        /* =========================
            購入ポイント付与（3%）
        ========================= */
    $add_point = floor($final_total * 0.03);

        $stmt = $pdo->prepare("
            UPDATE customers
            SET points = points + ?
            WHERE customer_id = ?
        ");
    $stmt->execute([$add_point, $customer_id]);

    /* =========================
       ⑤ カート削除
    ========================= */
    $stmt = $pdo->prepare("DELETE FROM carts WHERE customer_id = ?");
    $stmt->execute([$customer_id]);

    $pdo->commit();

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    exit('注文処理に失敗しました：' . $e->getMessage());
}

/* =========================
   ⑥ じゃんけんへ遷移
========================= */
header("Location: janken.php");
exit;
