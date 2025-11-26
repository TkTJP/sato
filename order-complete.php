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
   POSTデータ取得
========================= */
$base_total  = (int)($_POST['base_total'] ?? 0);
$shipping    = (int)($_POST['shipping'] ?? 0);
$use_points  = (int)($_POST['points'] ?? 0);
$coupon_rate = (int)($_POST['coupon_rate'] ?? 0);
$final_total = (int)($_POST['final_total'] ?? 0);
$payment     = $_POST['payment'] ?? '';

/* =========================
   カート商品取得
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
   トランザクション開始
========================= */
try {
    $pdo->beginTransaction();

    /* -------------------------
       ① purchases に登録
    ------------------------- */
    $stmt = $pdo->prepare("
        INSERT INTO purchases (customer_id, total, purchase_date)
        VALUES (?, ?, CURRENT_TIMESTAMP)
    ");
    $stmt->execute([$customer_id, $final_total]);

    $purchase_id = $pdo->lastInsertId();

    /* -------------------------
       ② purchase_detail に登録
    ------------------------- */
    $stmtDetail = $pdo->prepare("
        INSERT INTO purchase_detail
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

    /* -------------------------
       ③ ポイント使用分減算
    ------------------------- */
    if ($use_points > 0) {
        $stmt = $pdo->prepare("
            UPDATE customers
            SET points = points - ?
            WHERE customer_id = ? AND points >= ?
        ");
        $stmt->execute([$use_points, $customer_id, $use_points]);
    }

    /* -------------------------
       ④ クーポン使用済み更新
    ------------------------- */
    if ($coupon_rate > 0) {
        $stmt = $pdo->prepare("
            UPDATE customer_coupons
            SET is_used = 1, used_at = CURRENT_TIMESTAMP
            WHERE customer_id = ? AND is_used = 0
            ORDER BY acquired_at ASC
            LIMIT 1
        ");
        $stmt->execute([$customer_id]);
    }

    /* -------------------------
       ⑤ カート削除
    ------------------------- */
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
