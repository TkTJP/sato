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
   ポイント
========================= */
$stmt = $pdo->prepare("SELECT points FROM customers WHERE customer_id = ?");
$stmt->execute([$customer_id]);
$points = (int)$stmt->fetchColumn();

/* =========================
   サブスク
========================= */
$stmt = $pdo->prepare("SELECT subscr_join FROM customers WHERE customer_id = ?");
$stmt->execute([$customer_id]);
$subscr_join = (int)$stmt->fetchColumn();

/* =========================
   クーポン
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
$shipping_fee = ($subscr_join === 1) ? 0 : 500;
$pre_total = $total + $shipping_fee;
?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<title>購入画面</title>

<style>
/* =============================
   SATONOMI 注文確認ページ
============================= */

body {
    font-family: "Noto Sans JP", sans-serif;
    background: #f5f5f5;
    margin: 0;
    padding-bottom: 40px;
     padding-top: 140px;/* nav の高さ分ずらす（重なり防止）*/
}

.page-wrap {
    width: 95%;
    max-width: 800px; /* スマホ時の見え方を崩さないため */
    margin: 0 auto;   /* 中央寄せ */
}


/* ナビバー */
.nav-bar {
    display: flex;
    align-items: center;
    background: #9de7c6;
    padding: 12px 16px;
    font-size: 18px;
    font-weight: bold;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    z-index: 999;
}
.back-button {
    background: none;
    border: none;
    font-size: 22px;
    margin-right: 10px;
}

/* 商品カード */
.item-card {
    background: #ffffff;
    border-radius: 16px;
    padding: 16px;
    margin: 50px;
    box-shadow: 0 4px 10px rgba(0,0,0,0.08);
    display: flex;
    gap: 16px;
    align-items: center;
}
.item-card img {
    width: 80px;
    height: auto;
    border-radius: 10px;
    background: #fff;
}
.item-info {
    flex: 1;
}
.item-name {
    font-size: 17px;
    font-weight: bold;
    margin-bottom: 6px;
}
.price-line {
    display: flex;
    justify-content: space-between;
    margin: 2px 0;
    font-size: 15px;
}

/* セクション */
.section-block {
    padding: 0 16px;
    margin-top: 8px;
}

.section-title2 {
    font-weight: bold;
    margin-bottom: 6px;
}

/* 入力UI */
input[type="number"],
select {
    padding: 6px;
    border-radius: 6px;
    border: 1px solid #ccc;
}

/* 割引表示 */
#coupon_discount,
#point_discount {
    color: #ff8400;
    font-weight: bold;
}

/* 合計表示 */
.total-line {
    display: flex;
    justify-content: space-between;
    font-size: 17px;
    font-weight: bold;
    padding: 10px 16px;
}

/* ボタン */
button[type="submit"] {
    width: 95%;
    margin: 24px auto;
    display: block;
    padding: 14px;
    background: #9de7c6;
    font-size: 18px;
    font-weight: bold;
    border: none;
    border-radius: 16px;
}
button[type="submit"]:hover {
    background: #8bd7b7;
}

.row-line {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 8px 0;
}

.row-left {
    font-weight: bold;
    font-size: 16px;
}

.row-right {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 16px;
}
.row-right input[type="number"] {
    width: 70px;
    padding: 6px;
    border-radius: 6px;
    border: 1px solid #ccc;
    text-align: right;
}

/* クーポン選択をボタン風に小さくする */
#coupon {
    padding: 4px 10px;
    border-radius: 20px;
    border: 1px solid #333;
    background: #fff;
    font-size: 14px;
    height: 28px;
    appearance: none;  /* ブラウザ標準の巨大スタイル無効 */
}

/* 右側に小さく矢印を残す */
#coupon {
    background-image: url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='12' height='12'><path d='M2 4 L6 8 L10 4' stroke='black' stroke-width='2' fill='none' /></svg>");
    background-repeat: no-repeat;
    background-position: right 6px center;
    background-size: 12px;
    padding-right: 24px;
}


</style>
</head>
<body>

<?php include('header.php'); ?>

<nav class="nav-bar">
    <button class="back-button" onclick="history.back()">
        <i class="fa-solid fa-arrow-left"></i>
    </button>
    <span class="nav-title">注文内容の確認</span>
</nav>


<div class="page-wrap">


<?php foreach ($cart as $item): ?>
<?php
$name = htmlspecialchars($item['name']);
$img = htmlspecialchars($item['image']);
$single = $item['price'];
$set = $item['price'] * 12 * 0.9;
?>
<div class="item-card">
    <img src="img/<?= $img ?>">
    <div class="item-info">
        <div class="item-name"><?= $name ?></div>

        <?php if ($item['quantity'] > 0): ?>
        <div class="price-line">
            <span>単品 × <?= $item['quantity'] ?></span>
            <span>¥<?= number_format($single * $item['quantity']) ?></span>
        </div>
        <?php endif; ?>

        <?php if ($item['box'] > 0): ?>
        <div class="price-line">
            <span>12本セット × <?= $item['box'] ?></span>
            <span>¥<?= number_format($set * $item['box']) ?></span>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php endforeach; ?>


<div class="section-block">
    <div class="total-line">
        <span>送料</span>
        <span>¥<?= number_format($shipping_fee) ?><?php if ($subscr_join === 1) echo "（サブスク特典）"; ?></span>
    </div>

    <!-- 合計 -->
<div class="section-block">
    <div class="total-line">
        <span>合計</span>
        <span>¥<?= number_format($pre_total) ?></span>
    </div>
</div>

<!-- お支払方法 -->
<div class="section-block">
    <div class="section-title2">お支払方法</div>
    <label><input type="radio" name="payment" value="card" required> クレジットカード</label><br>
    <label><input type="radio" name="payment" value="conveni"> コンビニ決済</label><br>
    <label><input type="radio" name="payment" value="paypay"> paypay</label><br>
</div>


<!-- ポイント利用 -->
<div class="section-block">
    <div class="row-line">
        <div class="row-left">ポイント利用</div>
        <div class="row-right">
            <?= $points ?>P /
            <input type="number" id="use_point" min="0" max="<?= $points ?>" value="0">
        </div>
    </div>
</div>


<!-- 明細（商品価格 → 送料 → クーポン → ポイント割引） -->
<div class="section-block">

    <div class="row-line">
        <div class="row-left">商品価格</div>
        <div class="row-right">¥<?= number_format($total) ?></div>
    </div>

    <div class="row-line">
        <div class="row-left">送料</div>
        <div class="row-right">¥<?= number_format($shipping_fee) ?></div>
    </div>

    <div class="row-line" style="margin-top:12px;">
    <div class="row-left">クーポン</div>
    <div class="row-right">
        <select id="coupon">
            <option value="0" data-rate="0">クーポン選択</option>
            <?php foreach ($coupons as $c): ?>
            <option value="<?= $c['customer_coupon_id'] ?>" data-rate="<?= $c['rate'] ?>">
                <?= htmlspecialchars($c['coupon_name']) ?>（<?= $c['rate'] ?>%OFF）
            </option>
            <?php endforeach; ?>
        </select>
        <span id="coupon_discount" style="color:#ff8400;">-¥0</span>
    </div>
</div>


    <div class="row-line" style="margin-top:6px;">
        <div class="row-left">ポイント利用</div>
        <div class="row-right">
            <span id="point_discount" style="color:#ff8400;">-¥0</span>
        </div>
    </div>

</div>


<!-- 支払金額 -->
<div class="section-block">
    <div class="total-line" style="margin-top:14px;">
        <span>お支払金額</span>
        <span id="final_total">¥<?= number_format($pre_total) ?></span>
    </div>
</div>


<form action="order-complete.php" method="post">
    <input type="hidden" name="points" id="post_points" value="0">
    <input type="hidden" name="customer_coupon_id" id="post_coupon" value="0">
    <input type="hidden" name="final_total" id="post_total" value="<?= $pre_total ?>">

    <button type="submit">購入を確定する</button>
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


</div>
</body>
</html>
