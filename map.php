<?php
session_start();
require 'header.php';

// 地方データ
$regions = [
    'hokkaido' => ['name'=>'北海道地方','top'=>'15%','left'=>'70%'],
    'tohoku'   => ['name'=>'東北地方','top'=>'40%','left'=>'65%'],
    'kanto'    => ['name'=>'関東地方','top'=>'60%','left'=>'60%'],
    'chubu'    => ['name'=>'中部地方','top'=>'60%','left'=>'50%'],
    'kinki'    => ['name'=>'近畿地方','top'=>'68%','left'=>'42%'],
    'chugoku'  => ['name'=>'中国地方','top'=>'68%','left'=>'25%'],
    'shikoku'  => ['name'=>'四国地方','top'=>'73%','left'=>'29%'],
    'kyushu'   => ['name'=>'九州地方','top'=>'78%','left'=>'15%'],
    'okinawa'  => ['name'=>'沖縄地方','top'=>'83%','left'=>'74%']
];
?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>日本地図 - 地方選択</title>
<link rel="stylesheet" href="style.css">
<style>
body {
    font-family: sans-serif;
    background: #f8f9fa;
    text-align: center;
    margin: 0;
    padding: 10px;
}

.map-container {
    position: relative;
    width: 100%;
    max-width: 600px;  /* 最大幅 */
    margin: 20px auto;
    /* アスペクト比固定（縦横比が変わらないようにする） */
    aspect-ratio: 1 / 1;
}

.map-container img {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    border-radius: 10px;
}

.pin {
    position: absolute;
    width: 5%;
    aspect-ratio: 1;
    background: radial-gradient(circle at 30% 30%, #555,#000);
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

@media(max-width:768px){ .pin{width:7%;} }
@media(max-width:480px){ .pin{width:10%;} h2{font-size:18px;} }
</style>
</head>
<body>
<h2>日本地図 - 地方選択</h2>
<div class="map-container">
    <img src="img/japan.png" alt="日本地図">
    <?php foreach($regions as $key=>$region): ?>
        <div class="pin" style="top: <?= $region['top'] ?>; left: <?= $region['left'] ?>;"
             title="<?= htmlspecialchars($region['name']) ?>"
             onclick="location.href='region.php?region=<?= $key ?>'">
        </div>
    <?php endforeach; ?>
</div>
</body>
</html>
