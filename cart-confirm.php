<?php
session_start();
require 'db-connect.php';
$pdo = new PDO($connect, USER, PASS);

if (!isset($_SESSION['customer'])) {
    echo '<?php include("header.php"); ?>';
    echo "<p>ログインしてください。</p>";
    echo '
        <form action="login.php" method="get">
            <button type="submit">ログインする</button>
        </form>
    ';
    exit;
}
$customer_id = $_SESSION['customer']['customer_id'];

// ----------------------
//  追加処理（単品 + セット）
// ----------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {

    $product_id = (int)$_POST['id'];
    $qty        = max(0, (int)($_POST['quantity']     ?? 0));
    $box        = max(0, (int)($_POST['box_quantity'] ?? 0));

    $check = $pdo->prepare("SELECT quantity, box FROM carts WHERE customer_id=? AND product_id=?");
    $check->execute([$customer_id, $product_id]);
    $exist = $check->fetch(PDO::FETCH_ASSOC);

    if ($exist) {
        $pdo->prepare("UPDATE carts SET quantity = quantity + ?, box = box + ? WHERE customer_id=? AND product_id=?")
            ->execute([$qty, $box, $customer_id, $product_id]);
    } else {
        $pdo->prepare("INSERT INTO carts (customer_id, product_id, quantity, box, added_at) VALUES (?,?,?,?,NOW())")
            ->execute([$customer_id, $product_id, $qty, $box]);
    }

    header("Location: cart-confirm.php");
    exit;
}


// ----------------------
//  数量変更・削除
// ----------------------
if (isset($_GET['action'], $_GET['id'], $_GET['kind'])) {

    $product_id = (int)$_GET['id'];
    $kind       = $_GET['kind'];

    $stmt = $pdo->prepare("SELECT quantity, box FROM carts WHERE customer_id=? AND product_id=?");
    $stmt->execute([$customer_id, $product_id]);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$data) {
        header("Location: cart-confirm.php");
        exit;
    }

    $qty = $data['quantity'];
    $box = $data['box'];

    if ($kind === "single") {
        if ($_GET['action'] === 'plus')  $qty++;
        if ($_GET['action'] === 'minus') $qty = max(0, $qty - 1);
    }

    if ($kind === "set") {
        if ($_GET['action'] === 'plus')  $box++;
        if ($_GET['action'] === 'minus') $box = max(0, $box - 1);
    }

    if ($_GET['action'] === 'delete') {
        $qty = 0;
        $box = 0;
    }

    if ($qty == 0 && $box == 0) {
        $pdo->prepare("DELETE FROM carts WHERE customer_id=? AND product_id=?")
            ->execute([$customer_id, $product_id]);
    } else {
        $pdo->prepare("UPDATE carts SET quantity=?, box=? WHERE customer_id=? AND product_id=?")
            ->execute([$qty, $box, $customer_id, $product_id]);
    }

    header("Location: cart-confirm.php");
    exit;
}


// ----------------------
//  カート取得
// ----------------------
$stmt = $pdo->prepare("
    SELECT p.product_id, p.name, p.price, p.image,
           c.quantity, c.box
    FROM carts c
    JOIN products p ON c.product_id = p.product_id
    WHERE c.customer_id=?
");
$stmt->execute([$customer_id]);
$cart = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ----------------------
//  合計
// ----------------------
$total = 0;
?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>カート</title>
<style>
/* ▼必要最低限だけ */
img { width:70px; }
button { padding:6px 12px; }
</style>
</head>
<body>
<?php include('header.php'); ?>
<h2>カート</h2>

<?php if (empty($cart)): ?>
    <p>カートに商品はありません。</p>

<?php else: ?>

<?php foreach ($cart as $item): ?>
<?php
$id    = $item['product_id'];
$name  = htmlspecialchars($item['name']);
$img   = htmlspecialchars($item['image']);
$qty   = $item['quantity'];
$box   = $item['box'];

$price_single = $item['price'];
$price_set    = $item['price'] * 12 * 0.9;

$total += ($price_single * $qty) + ($price_set * $box);
?>

<!-- 1商品分（最小構成） -->
<img src="img/<?= $img ?>"><br>
<b><?= $name ?></b><br>

1本：¥<?= number_format($price_single) ?><br>
<a href="?action=minus&id=<?= $id ?>&kind=single">－</a>
<?= $qty ?>
<a href="?action=plus&id=<?= $id ?>&kind=single">＋</a><br>
小計（単品）：¥<?= number_format($price_single * $qty) ?><br><br>

12本セット：¥<?= number_format($price_set) ?><br>
<a href="?action=minus&id=<?= $id ?>&kind=set">－</a>
<?= $box ?>
<a href="?action=plus&id=<?= $id ?>&kind=set">＋</a><br>
小計（セット）：¥<?= number_format($price_set * $box) ?><br><br>

<a href="?action=delete&id=<?= $id ?>&kind=single" style="color:red;">削除</a>
<hr>

<?php endforeach; ?>

<p><b>合計：¥<?= number_format($total) ?></b></p>

<form action="order-confirm.php" method="get">
    <button type="submit">購入する</button>
</form>

<?php endif; ?>

</body>
</html>
