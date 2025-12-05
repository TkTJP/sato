<?php
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

/* =========================
   カート取得
========================= */
$stmt = $pdo->prepare("
    SELECT p.product_id, p.name, p.price, p.image,
           c.quantity, c.box
    FROM carts c
    JOIN products p ON c.product_id = p.product_id
    WHERE c.customer_id = ?
");
$stmt->execute([$customer_id]);
$cart = $stmt->fetchAll();

if (!$cart) {
    exit('カートが空です。');
}

/* =========================
   合計計算
========================= */
$total = 0;
foreach ($cart as $item) {
    $total += $item['price'] * $item['quantity'];
    $total += ($item['price'] * 12 * 0.9) * $item['box'];
}

/* =========================
   ポイント取得
========================= */
$stmt = $pdo->prepare("SELECT points FROM customers WHERE customer_id = ?");
$stmt->execute([$customer_id]);
$points = (int)$stmt->fetchColumn();

/* =========================
   ✅ ユーザー専用クーポン取得
========================= */
$stmt = $pdo->prepare("
    SELECT 
        cc.id AS customer_coupon_id,
        c.coupon_name,
        c.rate
    FROM customer_coupons cc
    JOIN coupons c ON cc.coupon_id = c.coupon_id
    WHERE cc.customer_id = ?
      AND cc.is_used = 0
");
$stmt->execute([$customer_id]);
$coupons = $stmt->fetchAll();

/* =========================
   送料
========================= */
$shipping_fee = 500;
$pre_total = $total + $shipping_fee;
?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<title>注文確認</title>
<style>
body { font-family: sans-serif; max-width: 650px; margin: auto; }
img { width:60px; }
hr { margin:12px 0; }
</style>
</head>
<body>

<?php include('header.php'); ?>
<h2>注文内容確認</h2>

<?php foreach ($cart as $item): ?>
<?php
$name = htmlspecialchars($item['name']);
$img = htmlspecialchars($item['image']);
$single = $item['price'];
$set = $item['price'] * 12 * 0.9;
?>
<img src="img/<?= $img ?>"><br>
<b><?= $name ?></b><br>

<?php if ($item['quantity'] > 0): ?>
単品 × <?= $item['quantity'] ?>（¥<?= number_format($single) ?>）
<span style="float:right">¥<?= number_format($single * $item['quantity']) ?></span><br>
<?php endif; ?>

<?php if ($item['box'] > 0): ?>
12本セット × <?= $item['box'] ?>（¥<?= number_format($set) ?>）
<span style="float:right">¥<?= number_format($set * $item['box']) ?></span><br>
<?php endif; ?>

<hr>
<?php endforeach; ?>

送料：¥<?= number_format($shipping_fee) ?><br>
<b>合計：¥<?= number_format($pre_total) ?></b><br><br>

ポイント：
<input type="number" id="use_point" min="0" max="<?= $points ?>" value="0"> / <?= $points ?>P<br>

クーポン：
<select id="coupon">
<option value="0" data-rate="0">選択なし</option>
<?php foreach ($coupons as $c): ?>
<option value="<?= $c['customer_coupon_id'] ?>" data-rate="<?= $c['rate'] ?>">
<?= htmlspecialchars($c['coupon_name']) ?>（<?= $c['rate'] ?>%OFF）
</option>
<?php endforeach; ?>
</select><br><br>

クーポン割引：<span id="coupon_discount">¥0</span><br>
ポイント割引：<span id="point_discount">¥0</span><br>
<b>支払金額：<span id="final_total">¥<?= number_format($pre_total) ?></span></b>

<form action="order-complete.php" method="post">
<input type="hidden" name="points" id="post_points" value="0">
<input type="hidden" name="customer_coupon_id" id="post_coupon" value="0">
<input type="hidden" name="final_total" id="post_total" value="<?= $pre_total ?>">

<br><b>支払方法</b><br>
<label><input type="radio" name="payment" value="card" required> クレカ</label>
<label><input type="radio" name="payment" value="paypay"> PayPay</label>
<label><input type="radio" name="payment" value="conveni"> コンビニ</label><br><br>

<button type="submit">注文確定</button>
</form>

<script>
const base = <?= $pre_total ?>;

const usePoint = document.getElementById("use_point");
const couponSel = document.getElementById("coupon");
const final = document.getElementById("final_total");
const cd = document.getElementById("coupon_discount");
const pd = document.getElementById("point_discount");

const postP = document.getElementById("post_points");
const postC = document.getElementById("post_coupon");
const postT = document.getElementById("post_total");

function calc() {
    const pt = parseInt(usePoint.value || 0);
    const selected = couponSel.selectedOptions[0];
    const rate = parseInt(selected.dataset.rate || 0);
    const couponId = selected.value;

    const couponVal = Math.floor(base * (rate / 100));
    const pointVal = Math.min(pt, base - couponVal);
    const sum = Math.max(base - couponVal - pointVal, 0);

    cd.textContent = "-¥" + couponVal.toLocaleString();
    pd.textContent = "-¥" + pointVal.toLocaleString();
    final.textContent = "¥" + sum.toLocaleString();

    postP.value = pointVal;
    postC.value = couponId;
    postT.value = sum;
}

usePoint.addEventListener("input", calc);
couponSel.addEventListener("change", calc);
</script>

</body>
</html>
