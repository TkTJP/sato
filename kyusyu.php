<?php session_start(); ?>
<?php require 'header.php'; ?>

<?php
// 各地方のデータを配列で定義
$regions = [
  'Fukuoka'    => ['name' => '福岡県',    'top' => '15%', 'left' => '60%'],
  'Oita'       => ['name' => '大分県',    'top' => '23%', 'left' => '80%'],
  'Saga'       => ['name' => '佐賀県',    'top' => '25%', 'left' => '45%'],
  'Nagasaki'   => ['name' => '長崎県',    'top' => '27%', 'left' => '36%'],
  'Kumamoto'   => ['name' => '熊本県',    'top' => '45%', 'left' => '63%'],
  'Miyazaki'   => ['name' => '宮崎県',    'top' => '50%', 'left' => '80%'],
  'Kagosima'   => ['name' => '鹿児島県',  'top' => '74%', 'left' => '62%']
];
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <title>日本地図 - 九州地方</title>
  <link rel="stylesheet" href="style.css">
  <style>
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
      background: radial-gradient(circle at 30% 30%, #555, #000);
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
      box-shadow: inset 0 0 3px rgba(0,0,0,0.3);
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
    <img src="img/kyusyu.png" alt="九州地方">

    <?php foreach ($regions as $region): ?>
      <div 
        class="pin"
        style="top: <?= $region['top'] ?>; left: <?= $region['left'] ?>;"
        title="<?= htmlspecialchars($region['name']) ?>"
        onclick="location.href='region-search.php?region=<?= urlencode($region['name']) ?>'">
      </div>
    <?php endforeach; ?>
  </div>
</body>
</html>
