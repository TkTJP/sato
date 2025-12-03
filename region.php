<?php
session_start();
require 'header.php';

// 全地方＋都道府県データ
$allRegions = [
    // 省略：先ほどの $allRegions と同じ
];

$regionKey = $_GET['region'] ?? null;
if(!$regionKey || !isset($allRegions[$regionKey])) die('地方が指定されていません。');
$regionData = $allRegions[$regionKey];
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
    font-family: sans-serif;
    background: #f8f9fa;
    text-align: center;
    margin: 0;
    padding: 10px;
}

.map-container {
    position: relative;
    width: 100%;
    max-width: 600px;
    margin: 20px auto;
    /* 画像の縦横比を固定（ピンがずれないように） */
    aspect-ratio: 1/1;
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
    width: 6%;
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
    top:25%; left:25%;
    width:50%; height:50%;
    background:#fff;
    border-radius:50%;
    box-shadow: inset 0 0 3px rgba(0,0,0,0.3);
}

.pin:hover {
    transform: rotate(-45deg) translate(-50%, -100%) scale(1.3);
    box-shadow:0 6px 10px rgba(0,0,0,0.5);
}

@media(max-width:768px){ .pin{width:8%;} }
@media(max-width:480px){ .pin{width:10%;} h2{font-size:18px;} }
</style>
</head>
<body>
<h2><?= htmlspecialchars($regionData['name']) ?></h2>

<div class="map-container">
    <img src="img/<?= $regionKey ?>.png" alt="<?= htmlspecialchars($regionData['name']) ?>">
    <?php foreach($regionData['prefectures'] as $pref): ?>
        <div class="pin"
             style="top: <?= $pref['top'] ?>; left: <?= $pref['left'] ?>;"
             title="<?= htmlspecialchars($pref['name']) ?>"
             onclick="location.href='region-search.php?region=<?= urlencode($pref['name']) ?>'">
        </div>
    <?php endforeach; ?>
</div>

<a href="map.php" style="display:block; margin-top:10px;">← 日本地図に戻る</a>
</body>
</html>
