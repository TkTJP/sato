<?php
session_start();
require 'header.php';

// 全国の地方データ
$regions = [
    'hokkaido' => ['name' => '北海道地方', 'description' => '北海道の説明文', 'image' => 'img/hokkaido.png', 'top'=>'15%', 'left'=>'70%'],
    'tohoku'   => ['name' => '東北地方', 'description' => '東北地方の説明文', 'image' => 'img/tohoku.png', 'top'=>'40%', 'left'=>'65%'],
    'kanto'    => ['name' => '関東地方', 'description' => '関東地方の説明文', 'image' => 'img/kanto.png', 'top'=>'60%', 'left'=>'60%'],
    'chubu'    => ['name' => '中部地方', 'description' => '中部地方の説明文', 'image' => 'img/chubu.png', 'top'=>'60%', 'left'=>'50%'],
    'kinki'    => ['name' => '近畿地方', 'description' => '近畿地方の説明文', 'image' => 'img/kinki.png', 'top'=>'68%', 'left'=>'42%'],
    'chugoku'  => ['name' => '中国地方', 'description' => '中国地方の説明文', 'image' => 'img/chugoku.png', 'top'=>'68%', 'left'=>'25%'],
    'shikoku'  => ['name' => '四国地方', 'description' => '四国地方の説明文', 'image' => 'img/shikoku.png', 'top'=>'73%', 'left'=>'29%'],
    'kyushu'   => ['name' => '九州地方', 'description' => '九州地方の説明文', 'image' => 'img/kyushu.png', 'top'=>'78%', 'left'=>'15%'],
    'okinawa'  => ['name' => '沖縄地方', 'description' => '沖縄地方の説明文', 'image' => 'img/okinawa.png', 'top'=>'83%', 'left'=>'74%']
];

// URLパラメータで地方を取得
$regionKey = $_GET['region'] ?? null;

if ($regionKey && isset($regions[$regionKey])) {
    $regionData = $regions[$regionKey]; // 選択された地方
} else {
    $regionData = [
        'name' => '日本地図',
        'description' => '地方を選択してください',
        'image' => 'img/japan.png'
    ];
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($regionData['name']) ?></title>
<link rel="stylesheet" href="style.css">
<style>
body {
    text-align: center;
    font-family: sans-serif;
    background: #f8f9fa;
    margin: 0;
    padding: 0;
}
h1, h2 { margin-top: 20px; }

.map-container {
    position: relative;
    display: inline-block;
    margin: 30px auto;
    max-width: 100%;
}

.map-container img {
    width: 100%;
    max-width: 600px;
    height: auto;
    border-radius: 10px;
    display: block;
}

.pin {
    position: absolute;
    width: 4%;
    height: auto;
    aspect-ratio: 1;
    background: radial-gradient(circle at 30% 30%, #555, #000);
    border-radius: 50% 50% 50% 0;
    transform: rotate(-45deg) translate(-50%, -100%);
    cursor: pointer;
    box-shadow: 0 3px 6px rgba(0,0,0,0.4);
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.pin::after {
    content: "";
    position: absolute;
    top: 25%;
    left: 25%;
    width: 50%;
    height: 50%;
    background: #fff;
    border-radius: 50%;
    box-shadow: inset 0 0 3px rgba(0,0,0,0.3);
}

.pin:hover {
    transform: rotate(-45deg) translate(-50%, -100%) scale(1.3);
    box-shadow: 0 6px 10px rgba(0,0,0,0.5);
}

@media (max-width: 480px) {
    .pin { width: 6%; }
}
</style>
</head>
<body>
<h2 class="cart-title">
    <span style="float: left;">
        <button type="button" onclick="history.back();" style="border:none; background:none; font-size:18px;">←</button>
    </span>
    <?= htmlspecialchars($regionData['name']) ?>
</h2>

<?php if (!$regionKey): ?>
    <!-- 地図表示 -->
    <div class="map-container">
        <img src="img/japan.png" alt="日本地図">
        <?php foreach ($regions as $key => $region): ?>
            <div class="pin"
                 style="top: <?= $region['top'] ?>; left: <?= $region['left'] ?>;"
                 title="<?= htmlspecialchars($region['name']) ?>"
                 onclick="location.href='region.php?region=<?= $key ?>'">
            </div>
        <?php endforeach; ?>
    </div>
<?php else: ?>
    <!-- 地方詳細表示 -->
    <h1><?= htmlspecialchars($regionData['name']) ?></h1>
    <img src="<?= htmlspecialchars($regionData['image']) ?>" alt="<?= htmlspecialchars($regionData['name']) ?>" style="max-width:100%; height:auto; border-radius:10px;">
    <p><?= htmlspecialchars($regionData['description']) ?></p>
    <a href="region.php">← 日本地図に戻る</a>
<?php endif; ?>

</body>
</html>
