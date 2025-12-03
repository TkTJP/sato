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

// 12本セット
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
<title><?= htmlspecialchars($product['name']); ?></title>
<link rel="stylesheet" href="style.css">

<style>
/* ==============================
   スマホ対応・CSSハートボタン
================================= */
.like-btn {
    width: 28px;
    height: 28px;
    display: inline-block;
    cursor: pointer;
    position: relative;
    user-select: none;
    padding: 6px;          /* ← iPhoneで必要：タップ領域を広げる */
}

.like-btn::before {
    content: "\2661"; /* ♡ 白ハート */
    font-size: 28px;
    color: #aaa;
    transition: .2s ease;
}

.like-btn.liked::before {
    content: "\2665"; /* ♥ 塗りつぶし */
    color: red;
}

/* 数量ボタン */
.count-box, .set-box {
    display: flex;
    align-items: center;
    gap: 10px;
    margin: 6px 0;
}
.count-box button, .set-box button {
    width: 32px;
    height: 32px;
    font-size: 18px;
    cursor: pointer;
}
</style>
</head>

<body>

<?php include('header.php'); ?>
<a href="top.php">← 戻る</a>

<h2><?= htmlspecialchars($product['name']); ?></h2>

<img src="img/<?= htmlspecialchars($product['image']); ?>" width="200"><br>

<!-- ========== いいね ========== -->
<div class="like-wrap">
    <span id="likeBtn" class="like-btn <?= $isLiked ? 'liked' : '' ?>"></span>
    <span id="likeCount"><?= $totalLikes ?></span>
</div>

<p>価格：¥<?= number_format($product['price']); ?></p>

<!-- ========== 単品 ========== -->
<p>1本 / ¥<?= number_format($product['price']); ?></p>
<div class="count-box">
    <button type="button" id="inc">＋</button>
    <button type="button" id="dec">－</button>
    <span id="qty">0</span>
</div>

<!-- ========== セット ========== -->
<p>12本セット（-10%） / ¥<?= number_format($set_price) ?></p>
<div class="set-box">
    <button type="button" id="boxInc">＋</button>
    <button type="button" id="boxDec">－</button>
    <span id="boxQty">0</span>
</div>

<!-- ========== カート送信 ========== -->
<form method="post" action="cart-confirm.php">
    <input type="hidden" name="id" value="<?= $product_id ?>">
    <input type="hidden" id="qtyInput" name="quantity" value="0">
    <input type="hidden" id="boxInput" name="box_quantity" value="0">
    <button type="submit">カートに入れる</button>
</form>

<!-- ========== 説明 ========== -->
<button id="descBtn">商品説明 ▼</button>
<p id="desc" style="display:none;">
<?= nl2br(htmlspecialchars($product['description'] ?? '説明なし')); ?>
</p>

<script>
// -------------------- 単品 --------------------
let q = 0;
document.getElementById('inc').onclick = ()=>{ q++; update(); }
document.getElementById('dec').onclick = ()=>{ if(q>0) q--; update(); }
function update(){
    document.getElementById('qty').textContent = q;
    document.getElementById('qtyInput').value = q;
}

// -------------------- セット --------------------
let bq = 0;
document.getElementById('boxInc').onclick = ()=>{ bq++; updateBox(); }
document.getElementById('boxDec').onclick = ()=>{ if(bq>0) bq--; updateBox(); }
function updateBox(){
    document.getElementById('boxQty').textContent = bq;
    document.getElementById('boxInput').value = bq;
}

// -------------------- 説明開閉 --------------------
document.getElementById('descBtn').onclick = ()=>{
    const d = document.getElementById('desc');
    const btn = document.getElementById('descBtn');
    const open = d.style.display === 'none';
    d.style.display = open ? 'block' : 'none';
    btn.textContent = open ? '説明 ▲' : '説明 ▼';
};

// -------------------- いいね処理（スマホ対応） --------------------
const likeBtn = document.getElementById('likeBtn');
likeBtn.addEventListener('click', toggleLike);
likeBtn.addEventListener('touchstart', toggleLike);

async function toggleLike(e){
    e.preventDefault(); // iPhoneのゴーストクリック対策

    const res = await fetch('like_toggle.php', {
        method:'POST',
        headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body:'id=<?= $product_id ?>'
    });
    const data = await res.json();

    if(data.success){
        likeBtn.classList.toggle('liked', data.liked);
        document.getElementById('likeCount').textContent = data.likes;
    } else {
        alert(data.message);
    }
}
</script>

</body>
</html>
