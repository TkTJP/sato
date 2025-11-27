<?php
session_start();
require 'db-connect.php';
$pdo = new PDO($connect, USER, PASS);

if (!isset($_SESSION['customer'])) {
    echo "ログインしてください。";
    exit;
}
$customer_id = $_SESSION['customer']['customer_id'];

// ------------- カート取得 -------------
$stmt = $pdo->prepare("
SELECT p.product_id, p.name, p.price, p.image,
       c.quantity, c.box
FROM carts c
JOIN products p ON c.product_id = p.product_id
WHERE c.customer_id = ?
");
$stmt->execute([$customer_id]);
$cart = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ------------- 合計 -------------
$total = 0;
foreach ($cart as $item) {
    $total += $item['price'] * $item['quantity'];
    $total += ($item['price'] * 12 * 0.9) * $item['box'];
}

// ------------- ポイント -------------
$pointStmt = $pdo->prepare("SELECT points FROM customers WHERE customer_id = ?");
$pointStmt->execute([$customer_id]);
$points = (int)$pointStmt->fetchColumn();

// ------------- クーポン -------------
$couponStmt = $pdo->query("SELECT coupon_id, coupon_name, rate FROM coupons");
$coupons = $couponStmt->fetchAll(PDO::FETCH_ASSOC);

// ------------- 送料 -------------
$shipping_fee = 500;
$pre_total = $total + $shipping_fee;
?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<title>注文確認</title>

<style>
/* ------- 最低限だけ -------- */
body { font-family: sans-serif; max-width: 650px; margin: 0 auto; padding: 10px; line-height: 1.6; }
.row { display:flex; justify-content:space-between; }
hr { margin:12px 0; }
img { width:60px; }
</style>

</head>
<body>

<?php include('header.php'); ?>
<h2>注文内容の確認</h2>

<?php if (empty($cart)): ?>
<p>カートが空です。</p>

<?php else: ?>

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
  単品 × <?= $item['quantity'] ?>（1本 ¥<?= number_format($single) ?>）  
  <span style="float:right;">¥<?= number_format($single * $item['quantity']) ?></span><br>
<?php endif; ?>

<?php if ($item['box'] > 0): ?>
  12本セット × <?= $item['box'] ?>（セット ¥<?= number_format($set) ?>）  
  <span style="float:right;">¥<?= number_format($set * $item['box']) ?></span><br>
<?php endif; ?>

<hr>
<?php endforeach; ?>


<!-- 合計 -->
<b>商品価格（送料込み）</b>
<span style="float:right;">¥<?= number_format($pre_total) ?></span><br>

送料 <span style="float:right;">¥<?= number_format($shipping_fee) ?></span><br><br>

ポイント利用：  
<input type="number" id="use_point" min="0" max="<?= $points ?>" value="0"> / <?= $points ?>P<br>

クーポン：  
<select id="coupon">
  <option value="0" data-rate="0">クーポン選択</option>
  <?php foreach ($coupons as $c): ?>
  <option value="<?= $c['coupon_id'] ?>" data-rate="<?= $c['rate'] ?>">
    <?= htmlspecialchars($c['coupon_name']) ?>（-<?= $c['rate'] ?>%）
  </option>
  <?php endforeach; ?>
</select><br>

クーポン割引：<span id="coupon_discount">- ¥0</span><br>
ポイント割引：<span id="point_discount">- ¥0</span><br>

<hr>

<b>お支払金額</b>
<span style="float:right;" id="final_total">¥<?= number_format($pre_total) ?></span><br><br>


<form action="order-complete.php" method="post">
    <input type="hidden" name="base_total" value="<?= $total ?>">
    <input type="hidden" name="shipping" value="<?= $shipping_fee ?>">
    <input type="hidden" name="points" id="post_points" value="0">
    <input type="hidden" name="coupon_rate" id="post_coupon" value="0">
    <input type="hidden" name="final_total" id="post_total" value="<?= $pre_total ?>">

    <b>支払い方法</b><br>
    <label><input type="radio" name="payment" value="card" required> クレカ</label><br>
    <label><input type="radio" name="payment" value="conveni"> コンビニ</label><br>
    <label><input type="radio" name="payment" value="paypay"> PayPay</label><br><br>

    <button type="submit">注文を確定する</button>
</form>

<?php endif; ?>

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
  const rate = parseInt(couponSel.selectedOptions[0].dataset.rate || 0);

  const couponVal = Math.floor(base * (rate / 100));
  const pointVal = Math.min(pt, base - couponVal);

  const sum = Math.max(base - couponVal - pointVal, 0);

  cd.textContent = "- ¥" + couponVal.toLocaleString();
  pd.textContent = "- ¥" + pointVal.toLocaleString();
  final.textContent = "¥" + sum.toLocaleString();

  postP.value = pointVal;
  postC.value = rate;
  postT.value = sum;
}

usePoint.addEventListener("input", calc);
couponSel.addEventListener("change", calc);
</script>

</body>
</html>
