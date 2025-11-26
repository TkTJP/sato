<?php 
session_start(); 
require 'header.php';

// 各地方のデータを配列で定義
$regions = [
    'hokkaido' => ['name' => '北海道地方', 'top' => '15%', 'left' => '70%', 'file' => 'hokkaido.php'],
    'tohoku'   => ['name' => '東北地方',   'top' => '40%', 'left' => '65%', 'file' => 'tohoku.php'],
    'kanto'    => ['name' => '関東地方',   'top' => '60%', 'left' => '60%', 'file' => 'kanto.php'],
    'chubu'    => ['name' => '中部地方',   'top' => '60%', 'left' => '50%', 'file' => 'chubu.php'],
    'kinki'    => ['name' => '近畿地方',   'top' => '68%', 'left' => '42%', 'file' => 'kinki.php'],
    'chugoku'  => ['name' => '中国地方',   'top' => '68%', 'left' => '25%', 'file' => 'chugoku.php'],
    'shikoku'  => ['name' => '四国地方',   'top' => '73%', 'left' => '29%', 'file' => 'shikoku.php'],
    'kyushu'   => ['name' => '九州地方',   'top' => '78%', 'left' => '15%', 'file' => 'kyusyu.php'],
    'okinawa'  => ['name' => '沖縄地方',   'top' => '83%', 'left' => '74%', 'file' => 'okinawa.php']
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

h1, h2 {
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
    各産MAP
</h2>

<div class="map-container">
    <img src="img/japan.png" alt="日本地図">

    <?php foreach ($regions as $region): ?>
    <div class="pin" style="top: <?= $region['top'] ?>; left: <?= $region['left'] ?>;"
         onclick="location.href='<?= htmlspecialchars($region['file']) ?>'">
        
    </div>
<?php endforeach; ?>

</div>
</body>
</html>
