<?php
session_start();
require 'header.php';

// 地方＋都道府県データ（画像に合わせて座標を精密調整）
$allRegions = [
    'hokkaido' => ['name'=>'北海道地方','prefectures'=>[
        ['name'=>'北海道','top'=>'45%','left'=>'50%']
    ]],
    'tohoku' => ['name'=>'東北地方','prefectures'=>[
        // 縦長の地図に合わせてジグザグに配置
        ['name'=>'青森県','top'=>'18%','left'=>'50%'],
        ['name'=>'秋田県','top'=>'38%','left'=>'30%'],
        ['name'=>'岩手県','top'=>'38%','left'=>'70%'],
        ['name'=>'山形県','top'=>'62%','left'=>'35%'],
        ['name'=>'宮城県','top'=>'58%','left'=>'70%'],
        ['name'=>'福島県','top'=>'82%','left'=>'50%']
    ]],
    'kanto' => ['name'=>'関東地方','prefectures'=>[
        // 密集回避のため少し外側に散らす
        ['name'=>'群馬県','top'=>'25%','left'=>'25%'],
        ['name'=>'栃木県','top'=>'25%','left'=>'70%'],
        ['name'=>'茨城県','top'=>'45%','left'=>'85%'],
        ['name'=>'埼玉県','top'=>'45%','left'=>'40%'], // 東京と重ならないよう少し左へ
        ['name'=>'東京都','top'=>'65%','left'=>'50%'],
        ['name'=>'千葉県','top'=>'65%','left'=>'80%'],
        ['name'=>'神奈川県','top'=>'75%','left'=>'35%']
    ]],
    'chubu' => ['name'=>'中部地方','prefectures'=>[
        // 形が複雑なので画像に合わせて大きく散らす
        ['name'=>'新潟県','top'=>'15%','left'=>'80%'], // 右上に長く伸びている
        ['name'=>'富山県','top'=>'35%','left'=>'55%'],
        ['name'=>'石川県','top'=>'30%','left'=>'20%'], // 左に突き出ている
        ['name'=>'福井県','top'=>'50%','left'=>'25%'],
        ['name'=>'長野県','top'=>'55%','left'=>'65%'],
        ['name'=>'岐阜県','top'=>'65%','left'=>'40%'],
        ['name'=>'山梨県','top'=>'70%','left'=>'75%'],
        ['name'=>'愛知県','top'=>'80%','left'=>'45%'],
        ['name'=>'静岡県','top'=>'85%','left'=>'75%']
    ]],
    'kinki' => ['name'=>'近畿地方','prefectures'=>[
        ['name'=>'京都府','top'=>'25%','left'=>'50%'],
        ['name'=>'滋賀県','top'=>'30%','left'=>'75%'], // 琵琶湖の右
        ['name'=>'兵庫県','top'=>'45%','left'=>'25%'],
        ['name'=>'大阪府','top'=>'60%','left'=>'45%'],
        ['name'=>'奈良県','top'=>'65%','left'=>'65%'],
        ['name'=>'和歌山県','top'=>'85%','left'=>'40%'] // 下に突き出ている
    ]],
    'chugoku' => ['name'=>'中国地方','prefectures'=>[
        // 横長なので上下左右にしっかり分ける
        ['name'=>'鳥取県','top'=>'35%','left'=>'70%'],
        ['name'=>'島根県','top'=>'35%','left'=>'35%'],
        ['name'=>'岡山県','top'=>'65%','left'=>'75%'],
        ['name'=>'広島県','top'=>'65%','left'=>'45%'],
        ['name'=>'山口県','top'=>'65%','left'=>'15%']
    ]],
    'shikoku' => ['name'=>'四国地方','prefectures'=>[
        ['name'=>'香川県','top'=>'20%','left'=>'65%'],
        ['name'=>'徳島県','top'=>'35%','left'=>'80%'],
        ['name'=>'愛媛県','top'=>'35%','left'=>'25%'],
        ['name'=>'高知県','top'=>'70%','left'=>'50%']
    ]],
    'kyushu' => ['name'=>'九州地方','prefectures'=>[
        ['name'=>'福岡県','top'=>'15%','left'=>'50%'],
        ['name'=>'佐賀県','top'=>'25%','left'=>'30%'],
        ['name'=>'大分県','top'=>'30%','left'=>'75%'],
        ['name'=>'長崎県','top'=>'35%','left'=>'15%'],
        ['name'=>'熊本県','top'=>'55%','left'=>'40%'],
        ['name'=>'宮崎県','top'=>'65%','left'=>'70%'],
        ['name'=>'鹿児島県','top'=>'85%','left'=>'40%']
    ]],
    'okinawa' => ['name'=>'沖縄地方','prefectures'=>[
        ['name'=>'沖縄県','top'=>'50%','left'=>'50%']
    ]]
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
.map-container {
    position:relative; width:100%; max-width:600px; margin:20px auto;
    /* 画像のサイズにコンテナを合わせる設定 */
}
.map-container img { 
    width:100%; height:auto; /* 高さを自動にして歪みを防ぐ */
    display: block;
    border-radius:10px; 
}
.pin {
    position:absolute; width:6%; aspect-ratio:1;
    background: radial-gradient(circle at 30% 30%, #555,#000);
    border-radius:50% 50% 50% 0;
    
    /* 座標ズレ防止の肝: 位置合わせ(translate)を先に実行 */
    transform: translate(-50%, -100%) rotate(-45deg); 
    
    cursor:pointer; box-shadow:0 3px 6px rgba(0,0,0,0.4);
    transition: transform 0.2s ease, box-shadow 0.2s ease;
    z-index: 10; /* ピンが重なったときに押しやすいように */
}
.pin::after {
    content:""; position:absolute; top:25%; left:25%; width:50%; height:50%;
    background:#fff; border-radius:50%; box-shadow: inset 0 0 3px rgba(0,0,0,0.3);
}
.pin:hover { 
    transform: translate(-50%, -100%) rotate(-45deg) scale(1.3); 
    box-shadow:0 6px 10px rgba(0,0,0,0.5); 
    z-index: 20; /* ホバー時に一番手前に */
}
@media(max-width:768px){ 
    .pin{width:8%;} 
}
@media(max-width:480px){ 
    .pin{width:10%;} 
    h2{font-size:18px;} 
}
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