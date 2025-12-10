<?php
session_start();
require 'db-connect.php';
$pdo = new PDO($connect, USER, PASS);

// -------------------------
// 商品取得
// -------------------------
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo '不正なアクセスです。';
    exit;
}

$product_id = (int)$_GET['id'];

$stmt = $pdo->prepare('SELECT * FROM products WHERE product_id = ?');
$stmt->execute([$product_id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$product) exit('商品が存在しません');

// セット（12本・10%引き）
$set_price = $product['price'] * 12 * 0.9;

// -------------------------
// いいね数・状態
// -------------------------
$countStmt = $pdo->prepare('SELECT COUNT(*) FROM likes WHERE product_id = ?');
$countStmt->execute([$product_id]);
$totalLikes = $countStmt->fetchColumn();

$customer_id = $_SESSION['customer']['customer_id'] ?? null;
$isLiked = false;

if ($customer_id) {
    $checkStmt = $pdo->prepare('SELECT 1 FROM likes WHERE product_id = ? AND customer_id = ?');
    $checkStmt->execute([$product_id, $customer_id]);
    $isLiked = (bool)$checkStmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($product['name']); ?></title>

<style>
/* =============================
   共通スタイル（上のCSSと統一）
============================= */
body {
    font-family: "Noto Sans JP", sans-serif;
    background:#f5f5f5;
    margin:0;
    padding-top:80px;
}

.page-container {
    width: 94%;
    max-width: 540px;
    margin: 0 auto 40px;
}

/* 商品カード */
.product-card {
    background:#fff;
    padding:20px;
    border-radius:14px;
    box-shadow:0 2px 6px rgba(0,0,0,0.1);
    position: relative;
    margin-top:20px;
}

/* 商品画像 */
.product-card img {
    width:100%;
    border-radius:12px;
    margin-bottom:10px;
}

/* ========== いいねボタン ========== */
.like-btn {
    width: 34px;
    height: 34px;
    display: inline-block;
    cursor: pointer;
    position: absolute;
    top: 16px;
    right: 16px;
    user-select: none;
}

.like-btn::before {
    content: "\2661"; /* ♡ */
    font-size: 34px;
    color: #aaa;
    transition: .2s;
}

.like-btn.liked::before {
    content: "\2665"; /* ♥ */
    color: red;
}

.like-count {
    font-size:16px;
    color:#555;
    margin-left:3px;
}

/* 数量ボタン */
.count-box, .set-box {
    display:flex;
    align-items:center;
    gap:14px;
    margin:8px 0;
}

.count-box button, .set-box button {
    width:36px;
    height:36px;
    border-radius:50%;
    border:none;
    font-size:20px;
    background:#ddd;
    cursor:pointer;
}

/* カートボタン */
.add-cart-btn {
    width:100%;
    background:#007bff;
    color:#fff;
    padding:14px 0;
    font-size:18px;
    border:none;
    border-radius:10px;
    margin-top:20px;
    cursor:pointer;
}

/* 説明カード */
.desc-card {
    background:#fff;
    padding:16px;
    border-radius:12px;
    margin-top:20px;
    box-shadow:0 2px 4px rgba(0,0,0,0.1);
}

.desc-toggle {
    width:100%;
    background:#eee;
    padding:10px;
    border-radius:8px;
    border:none;
    font-size:16px;
    cursor:pointer;
}

.back-link {
    display: inline-block;
    margin-bottom: 10px;
    color: #333;               /* 青色リンクを消す・黒字にする */
    text-decoration: none;     /* 下線を消す */
    font-size: 16px;
    font-weight: 500;
    padding: 6px 4px;
}

.back-link:hover {
    color: #000;               /* ホバー時に少し濃く */
    text-decoration: underline; /* うっすら下線 */
}

</style>
</head>

<body>

<?php include('header.php'); ?>

<div class="page-container">

<a href="top.php" class="back-link">← 戻る</a>

<div class="product-card">

    <!-- いいね -->
    <span id="likeBtn" class="like-btn <?= $isLiked ? 'liked' : '' ?>"></span>

    <img src="img/<?= htmlspecialchars($product['image']); ?>">

    <h2><?= htmlspecialchars($product['name']); ?></h2>

    <div style="margin-bottom:10px;">
        <span id="likeCount" class="like-count"><?= $totalLikes ?></span> 件のいいね
    </div>

    <p><b>価格：</b> ¥<?= number_format($product['price']); ?></p>

    <!-- 単品 -->
    <p><b>1本</b> / ¥<?= number_format($product['price']); ?></p>
    <div class="count-box">
        <button type="button" id="inc">＋</button>
        <button type="button" id="dec">－</button>
        <span id="qty">0</span>
    </div>

    <!-- セット -->
    <p><b>12本セット（10%引き）</b> / ¥<?= number_format($set_price); ?></p>
    <div class="set-box">
        <button type="button" id="boxInc">＋</button>
        <button type="button" id="boxDec">－</button>
        <span id="boxQty">0</span>
    </div>

    <!-- カート送信 -->
    <form method="post" action="cart-confirm.php">
        <input type="hidden" name="id" value="<?= $product_id ?>">
        <input type="hidden" id="qtyInput" name="quantity" value="0">
        <input type="hidden" id="boxInput" name="box_quantity" value="0">
        <button class="add-cart-btn" type="submit">カートに入れる</button>
    </form>

</div>

<!-- 商品説明カード -->
<div class="desc-card">
    <button id="descBtn" class="desc-toggle">商品説明 ▼</button>
    <p id="desc" style="display:none; margin-top:10px;">
        <?= nl2br(htmlspecialchars($product['description'] ?? '説明なし')); ?>
    </p>
</div>

</div>

<script>
// -------------------- 単品 --------------------
let q = 0;
document.getElementById('inc').onclick = ()=>{ q++; updateQty(); };
document.getElementById('dec').onclick = ()=>{ if(q>0) q--; updateQty(); };
function updateQty(){
    qty.textContent = q;
    qtyInput.value = q;
}

// -------------------- セット --------------------
let bq = 0;
document.getElementById('boxInc').onclick = ()=>{ bq++; updateBox(); };
document.getElementById('boxDec').onclick = ()=>{ if(bq>0) bq--; updateBox(); };
function updateBox(){
    boxQty.textContent = bq;
    boxInput.value = bq;
}

// -------------------- 説明 開閉 --------------------
descBtn.onclick = ()=>{
    const open = desc.style.display === "none";
    desc.style.display = open ? "block" : "none";
    descBtn.textContent = open ? "商品説明 ▲" : "商品説明 ▼";
};

// -------------------- いいね --------------------
const likeBtn = document.getElementById('likeBtn');

likeBtn.addEventListener('click', toggleLike);
likeBtn.addEventListener('touchstart', toggleLike);

async function toggleLike(e){
    e.preventDefault();

    const res = await fetch('like_toggle.php', {
        method:'POST',
        headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body:'id=<?= $product_id ?>'
    });
    const data = await res.json();

    if(data.success){
        likeBtn.classList.toggle('liked', data.liked);
        likeCount.textContent = data.likes;
    } else {
        alert(data.message);
    }
}
</script>

</body>
</html>
