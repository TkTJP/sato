<?php
session_start();
require 'db-connect.php';

/* ▼ 必ず最初にログインチェックを実行する（何も出力する前） ▼ */
if (!isset($_SESSION["admin_id"])) {
     echo "<script>
            alert('ログインしてください');
            window.location.href = 'admin-login.php';
          </script>";
    exit;
}
/* ▲ ログインチェックここまで ▲ */

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['command']) && $_POST['command'] === 'insert') {
    try {
        $pdo = new PDO($connect, USER, PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $pdo->beginTransaction();

        // 画像保存
        $imageName = null;
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = 'img/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
            $originalName = basename($_FILES['image']['name']);
            $imageName = uniqid() . '_' . $originalName;
            $uploadPath = $uploadDir . $imageName;
            move_uploaded_file($_FILES['image']['tmp_name'], $uploadPath);
        }

        // products 登録
        $stmt = $pdo->prepare("
            INSERT INTO products (name, description, price, stock, image, region, prefecture)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $_POST['name'],
            $_POST['description'] ?? '',
            $_POST['price'] ?? 0,
            $_POST['stock'] ?? 0,
            $imageName,
            $_POST['region'] ?? null,
            $_POST['prefecture'] ?? null
        ]);
        $product_id = $pdo->lastInsertId();

        // product_details 登録
        $stmt2 = $pdo->prepare("
            INSERT INTO product_details (product_id, is_subscribe, product_explain)
            VALUES (?, ?, ?)
        ");
        $stmt2->execute([
            $product_id,
            $_POST['is_subscribe'] ?? 0,
            $_POST['product_explain'] ?? ''
        ]);

        $pdo->commit();
        $message = '商品を登録しました！';
    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $message = 'エラー: ' . htmlspecialchars($e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>商品登録(Vueフルスクリーンメニュー)</title>
<script src="https://unpkg.com/vue@3/dist/vue.global.prod.js"></script>
<style>
body {margin:0;font-family:sans-serif;background-color:#fafafa;}
header{display:flex;align-items:center;justify-content:space-between;padding:10px 20px;position:relative;z-index:2;}
.menu-toggle{font-size:1.8rem;cursor:pointer;width:1.8rem;}
.header-title{position:absolute;left:50%;transform:translateX(-50%);font-weight:bold;font-size:1.4rem;white-space:nowrap;}
.right-space{width:1.8rem;}
.fullscreen-menu{position:fixed;top:0;left:-100%;width:100%;height:100%;background:#F1E9D6;z-index:5;display:flex;flex-direction:column;justify-content:space-between;transition:left 0.5s ease;padding:60px 20px 40px 20px;box-sizing:border-box;}
.fullscreen-menu.open{left:0;}
.fullscreen-menu ul{list-style:none;padding:0;margin:0;flex-grow:1;display:flex;flex-direction:column;justify-content:center;align-items:center;}
.fullscreen-menu li{margin:20px 0;font-size:1.5rem;}
.fullscreen-menu li a{color:black;text-decoration:none;font-weight:bold;}
.menu-close{position:absolute;top:20px;right:20px;font-size:2rem;cursor:pointer;}
.logout-btn{padding:12px 25px;font-size:1.2rem;border-radius:5px;text-decoration:none;color:black;border:2px solid white;text-align:center;transition:background 0.3s ease,color 0.3s ease;}
.logout-btn:hover{background:white;color:#FE9D6B;}
main{max-width:500px;margin:40px auto;padding:20px;background:white;border-radius:10px;box-shadow:0 3px 6px rgba(0,0,0,0.1);position:relative;z-index:1;}
label{display:block;margin-top:15px;font-weight:bold;color:#444;}
input,select,textarea{width:100%;padding:10px;margin-top:5px;font-size:1rem;border-radius:5px;border:1px solid #ccc;box-sizing:border-box;}
button{width:80%;display:block;margin:20px auto 0;padding:12px;font-size:1.1rem;background-color:lightgreen;color:black;border:none;border-radius:6px;cursor:pointer;}
button:hover{background-color:#005fa3;}
#image-preview{display:block;margin:10px 0;max-width:100%;max-height:200px;border:1px solid #ccc;border-radius:5px;object-fit:contain;}
.message{margin-top:10px;color:green;font-weight:bold;text-align:center;}
.link-to-manage{display:block;text-align:center;margin-top:20px;text-decoration:none;color:#0078D7;font-weight:bold;}
.link-to-manage:hover{color:#005fa3;}
</style>
</head>
<body>

<?php require 'manager-header.php'; ?>

<div id="app">
    <header>
        <div class="menu-toggle" @click="toggleMenu">&#9776;</div>
        <div class="header-title">商品登録フォーム</div>
        <div class="right-space"></div>
    </header>

    <div class="fullscreen-menu" :class="{ open: menuOpen }">
        <div class="menu-close" @click="closeMenu">×</div>
        <ul>
            <li><a href="product-manage.php" @click="closeMenu">商品管理</a></li>
            <li><a href="customer-manage.php" @click="closeMenu">顧客管理</a></li>
            <li><a href="adorder-history.php" @click="closeMenu">注文履歴</a></li>
        </ul>
        <a href="logout.php" class="logout-btn" @click="closeMenu">ログアウト</a>
    </div>

    <main>
        <?php if ($message): ?>
            <p class="message"><?= htmlspecialchars($message) ?></p>
        <?php endif; ?>

        <form action="" method="post" enctype="multipart/form-data">
            <input type="hidden" name="command" value="insert">

            <label>商品画像</label>
            <input type="file" name="image" accept="image/*" @change="previewImage">
            <img v-if="imageData" :src="imageData" id="image-preview">

            <label>商品名</label>
            <input type="text" name="name" required>

            <label>商品説明</label>
            <textarea name="description" rows="3"></textarea>

            <label>価格</label>
            <input type="text" name="price">

            <label>在庫</label>
            <input type="text" name="stock">

            <label>地方</label>
            <select id="region" name="region" @change="updatePrefectures">
                <option value="">選択してください</option>
                <option value="北海道">北海道</option>
                <option value="東北">東北</option>
                <option value="関東">関東</option>
                <option value="中部">中部</option>
                <option value="近畿">近畿</option>
                <option value="中国">中国</option>
                <option value="四国">四国</option>
                <option value="九州・沖縄">九州・沖縄</option>
            </select>

            <label>都道府県</label>
            <select id="prefecture" name="prefecture">
                <option value="">地方を選んでください</option>
            </select>

            <label>販売タイプ</label>
            <select name="is_subscribe">
                <option value="0">通常販売</option>
                <option value="1">サブスク</option>
            </select>

            <label>商品詳細説明</label>
            <textarea name="product_explain" rows="4"></textarea>

            <button type="submit">登録する</button>
        </form>

        <!-- 商品一覧ページへのリンク -->
        <a href="product-manage.php" class="link-to-manage">商品一覧ページへ戻る</a>
    </main>
</div>

<script>
const { createApp, ref } = Vue;

createApp({
    setup() {
        const menuOpen = ref(false);
        const imageData = ref(null);

        const toggleMenu = () => menuOpen.value = !menuOpen.value;
        const closeMenu = () => menuOpen.value = false;

        const previewImage = (event) => {
            const file = event.target.files[0];
            if (!file) return;
            const reader = new FileReader();
            reader.onload = e => imageData.value = e.target.result;
            reader.readAsDataURL(file);
        };

        const prefectures = {
            "北海道":["北海道"],
            "東北":["青森県","岩手県","宮城県","秋田県","山形県","福島県"],
            "関東":["茨城県","栃木県","群馬県","埼玉県","千葉県","東京都","神奈川県"],
            "中部":["新潟県","富山県","石川県","福井県","山梨県","長野県","岐阜県","静岡県","愛知県"],
            "近畿":["三重県","滋賀県","京都府","大阪府","兵庫県","奈良県","和歌山県"],
            "中国":["鳥取県","島根県","岡山県","広島県","山口県"],
            "四国":["徳島県","香川県","愛媛県","高知県"],
            "九州・沖縄":["福岡県","佐賀県","長崎県","熊本県","大分県","宮崎県","鹿児島県","沖縄県"]
        };

        const updatePrefectures = (event) => {
            const region = event.target.value;
            const prefSelect = document.getElementById('prefecture');
            prefSelect.innerHTML = '<option value="">選択してください</option>';
            if (prefectures[region]) {
                prefectures[region].forEach(p => {
                    const opt = document.createElement('option');
                    opt.value = p;
                    opt.textContent = p;
                    prefSelect.appendChild(opt);
                });
            }
        };

        return { menuOpen, toggleMenu, closeMenu, imageData, previewImage, updatePrefectures };
    }
}).mount('#app');
</script>

</body>
</html>
