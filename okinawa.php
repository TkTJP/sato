<?php session_start();?>
<?php require 'header.php';?>
<?php

// 各地方のデータを配列で定義
$regions = [
  'okinawa'    => ['name' => '沖縄',    'top' => '37%', 'left' => '50%']
];
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <title>日本地図 - 地方選択</title>
  <link rel="stylesheet" href="style.css">

  <style>
    body {
      text-align: center;
      font-family: sans-serif;
      background: #f8f9fa;
    }

    h1 {
      margin-top: 20px;
    }

    .map-container {
      position: relative;
      display: inline-block;
      margin-top: 30px;
    }

    .map-container img {
      width: 600px;
      height: auto;
      border-radius: 10px;
    }

    .pin {
  position: absolute;
  width: 22px;
  height: 22px;
  background: radial-gradient(circle at 30% 30%, #555, #000); /* 光の反射っぽいグラデーション */
  border-radius: 50% 50% 50% 0;
  transform: rotate(-45deg) translate(-50%, -100%);
  cursor: pointer;
  box-shadow: 0 3px 6px rgba(0, 0, 0, 0.4);
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.pin::after {
  content: "";
  position: absolute;
  top: 6px;
  left: 6px;
  width: 8px;
  height: 8px;
  background: #fff;
  border-radius: 50%;
  box-shadow: inset 0 0 3px rgba(0,0,0,0.3); /* 内側に少し奥行き */
}

.pin:hover {
  transform: rotate(-45deg) translate(-50%, -100%) scale(1.25);
  box-shadow: 0 6px 10px rgba(0, 0, 0, 0.5);
}


    
  </style>
</head>
<body>
  <h2 class="cart-title">
    <span style="float: left;">
        <button type="button" onclick="history.back();" style="border: none; background: none; font-size: 18px;">←</button>
    </span>
    
  </h2>

  <div class="map-container">
    <img src="img/okinawa.png" alt="沖縄">

    <?php foreach ($regions as $key => $region): ?>
     <div 
    class="pin"
    style="top: <?= $region['top'] ?>; left: <?= $region['left'] ?>; color: black;"
    onclick="location.href='region-search.php?region=<?= urlencode($region['name']) ?>'">
    ●
</div>

    <?php endforeach; ?>
  </div>
</body>
</html>
