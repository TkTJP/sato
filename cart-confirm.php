<?php 
session_start();

// セッションにカートがない場合の初期データ
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [
        ['name' => '長州地サイダー', 'price' => 1080, 'quantity' => 1, 'image' => 'images/cider.jpg']
    ];
}
$cart = $_SESSION['cart'];
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>カート確認画面</title>
</head>
<body>
    <?php include('header.php'); ?>
<div class="cart-container">

    <!-- ② カートタイトル -->
    <h2 class="cart-title">
        <span style="float: left;">
            <button type="button" onclick="history.back();" style="border: none; background: none; font-size: 18px;">←</button>
        </span>
        カート
    </h2>
    <!-- ③ 商品一覧 -->
    <?php foreach ($cart as $item): ?>
    <div class="cart-item" data-name="<?= htmlspecialchars($item['name'], ENT_QUOTES) ?>">
        <a href="product-detail.php?name=<?= urlencode($item['name']) ?>" class="item-link">
            <img src="<?= htmlspecialchars($item['image'], ENT_QUOTES) ?>" 
                alt="<?= htmlspecialchars($item['name'], ENT_QUOTES) ?>" 
                class="item-image">
            <div class="item-info">
                <p class="item-name"><?= htmlspecialchars($item['name'], ENT_QUOTES) ?></p>
                <p class="item-price">¥<?= number_format($item['price']) ?></p>
            </div>
        </a>
        <div class="item-quantity">
            <button class="quantity-btn" data-action="minus">-</button>
            <span class="quantity"><?= $item['quantity'] ?></span>
            <button class="quantity-btn" data-action="plus">+</button>
        </div>
    </div>
<?php endforeach; ?>

<!-- 合計 -->
<div class="total">
    <p>合計</p>
    <p class="total-price" id="total-price">
        ¥<?= number_format(array_sum(array_map(fn($i) => $i['price'] * $i['quantity'], $cart))) ?>
    </p>
</div>

    <!-- ⑤ 購入ボタン -->
    <form action="checkout.php" method="post">
        <button type="submit" class="purchase-button">購入手続きへ</button>
    </form>
</div>
<script>
document.querySelectorAll('.quantity-btn').forEach(btn => {
    btn.addEventListener('click', async (e) => {
        const action = e.target.dataset.action;
        const item = e.target.closest('.cart-item');
        const name = item.dataset.name;

        // PHPに送信
        const res = await fetch('update-cart.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: `name=${encodeURIComponent(name)}&action=${encodeURIComponent(action)}`
        });

        // PHPから返ってきたJSONを受け取る
        const data = await res.json();

        // 数量と合計を更新
        item.querySelector('.quantity').textContent = data.quantity;
        document.getElementById('total-price').textContent = `¥${data.total.toLocaleString()}`;
    });
});
</script>

</body>
</html>
