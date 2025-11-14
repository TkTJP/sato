<?php
session_start();
require 'db-connect.php';
$pdo = new PDO($connect, USER, PASS);

// --- ログイン確認 ---
if (!isset($_SESSION['customer'])) {
  echo 'ログインしてください。';
  exit;
}

$customer_id = $_SESSION['customer']['customer_id'];

// --- カート情報取得 ---
$sql = '
SELECT p.product_id, p.name, p.price, p.image, c.quantity
FROM carts c
JOIN products p ON c.product_id = p.product_id
WHERE c.customer_id = ?
';
$stmt = $pdo->prepare($sql);
$stmt->execute([$customer_id]);
$cart = $stmt->fetchAll(PDO::FETCH_ASSOC);

// --- 小計計算 ---
$total = 0;
foreach ($cart as $item) {
  $total += $item['price'] * $item['quantity'];
}

// --- 顧客ポイント取得 ---
$pointStmt = $pdo->prepare('SELECT points FROM customers WHERE customer_id = ?');
$pointStmt->execute([$customer_id]);
$points = (int)$pointStmt->fetchColumn();

// --- クーポン取得 ---
$couponStmt = $pdo->query('SELECT coupon_id, coupon_name, rate FROM coupons');
$coupons = $couponStmt->fetchAll(PDO::FETCH_ASSOC);

// --- 送料設定 ---
$shipping_fee = 500;
$pre_discount_total = $total + $shipping_fee;
?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<title>注文内容の確認 - SATONOMI</title>
<style>
  body { font-family: sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; }
  .row { display: flex; justify-content: space-between; align-items: center; margin: 8px 0; }
  .price { font-weight: bold; }
  .discount { color: orange; }
  input[type=number] { width: 80px; text-align: right; }
  select { width: 160px; }
</style>
</head>
<body>

<?php require 'header.php'; ?>

<h2>注文内容の確認</h2>

<?php if (empty($cart)): ?>
  <p>カートが空です。</p>
<?php else: ?>

  <?php foreach ($cart as $item): ?>
    <div class="row" style="border-bottom:1px solid #ccc;padding:5px 0;">
      <div>
        <img src="img/<?= htmlspecialchars($item['image']) ?>" width="60">
        <?= htmlspecialchars($item['name']) ?> × <?= $item['quantity'] ?>
      </div>
      <div>¥<?= number_format($item['price'] * $item['quantity']) ?></div>
    </div>
  <?php endforeach; ?>

  <hr>

  <!-- 商品価格（送料込み） -->
  <div class="row">
    <div>商品価格（送料込み）</div>
    <div>¥<?= number_format($pre_discount_total) ?></div>
  </div>

  <!-- 送料 -->
  <div class="row">
    <div>送料</div>
    <div>¥<?= number_format($shipping_fee) ?></div>
  </div>

  <!-- ポイント利用 -->
  <div class="row">
    <div>ポイント利用</div>
    <div>
      <?= number_format($points) ?>P /
      <input type="number" id="use_point" name="use_point" min="0" max="<?= $points ?>" value="0">
    </div>
  </div>

  <!-- クーポン選択 -->
  <div class="row">
    <div>クーポン</div>
    <div>
      <select id="coupon" name="coupon_id">
        <option value="0" data-rate="0">クーポン選択</option>
        <?php foreach ($coupons as $c): ?>
          <option value="<?= $c['coupon_id'] ?>" data-rate="<?= $c['rate'] ?>">
            <?= htmlspecialchars($c['coupon_name']) ?>（-<?= $c['rate'] ?>%）
          </option>
        <?php endforeach; ?>
      </select>
    </div>
  </div>

  <!-- クーポン割引 -->
  <div class="row">
    <div></div>
    <div class="discount" id="coupon_discount">- ¥0</div>
  </div>

  <!-- ポイント割引 -->
  <div class="row">
    <div>ポイント利用</div>
    <div class="discount" id="point_discount">- ¥0</div>
  </div>

  <hr>

  <!-- ⭐ お支払金額（計算後の最終金額を反映） -->
  <div class="row">
    <div><strong>お支払金額</strong></div>
    <div id="final_total_pay" class="price">¥<?= number_format($pre_discount_total) ?></div>
  </div>

  <form action="order-complete.php" method="post">
    <input type="hidden" name="base_total" value="<?= $total ?>">
    <input type="hidden" name="shipping" value="<?= $shipping_fee ?>">
    <input type="hidden" name="points" id="post_points" value="0">
    <input type="hidden" name="coupon_rate" id="post_coupon" value="0">
    <input type="hidden" name="final_total" id="post_total" value="<?= $pre_discount_total ?>">

    <h3>支払い方法</h3>
    <label><input type="radio" name="payment" value="card" required> クレジットカード</label><br>
    <label><input type="radio" name="payment" value="conveni"> コンビニ決済</label><br>
    <label><input type="radio" name="payment" value="paypay"> PayPay</label><br><br>

    <button type="submit">注文を確定する</button>
  </form>

<?php endif; ?>

<script>
const baseTotal = <?= $pre_discount_total ?>;
const usePoint = document.getElementById('use_point');
const couponSelect = document.getElementById('coupon');
const couponDiscount = document.getElementById('coupon_discount');
const pointDiscount = document.getElementById('point_discount');

// ⭐ 支払金額の反映先
const finalTotalPay = document.getElementById('final_total_pay');

const postPoints = document.getElementById('post_points');
const postCoupon = document.getElementById('post_coupon');
const postTotal = document.getElementById('post_total');

function updateTotal() {
  const point = parseInt(usePoint.value || 0);
  const couponRate = parseInt(couponSelect.selectedOptions[0].dataset.rate || 0);

  const couponValue = Math.floor(baseTotal * (couponRate / 100));
  const pointValue = Math.min(point, baseTotal);

  const newTotal = Math.max(baseTotal - couponValue - pointValue, 0);

  couponDiscount.textContent = `- ¥${couponValue.toLocaleString()}`;
  pointDiscount.textContent = `- ¥${pointValue.toLocaleString()}`;

  // ⭐ 支払金額に反映
  finalTotalPay.textContent = `¥${newTotal.toLocaleString()}`;

  // hidden 値更新
  postPoints.value = pointValue;
  postCoupon.value = couponRate;
  postTotal.value = newTotal;
}

usePoint.addEventListener('input', updateTotal);
couponSelect.addEventListener('change', updateTotal);
</script>

</body>
</html>
