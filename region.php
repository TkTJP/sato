<?php
session_start();
require 'header.php';

// 地方＋都道府県データ（全エリアの座標をスマホ向けに最適化）
$allRegions = [
    'hokkaido' => ['name'=>'北海道地方','prefectures'=>[
        ['name'=>'北海道','top'=>'50%','left'=>'50%'] // 中央へ
    ]],
    'tohoku' => ['name'=>'東北地方','prefectures'=>[
        // 縦に長いので上から順番に配置
        ['name'=>'青森県','top'=>'15%','left'=>'50%'],
        ['name'=>'秋田県','top'=>'35%','left'=>'30%'],
        ['name'=>'岩手県','top'=>'35%','left'=>'70%'],
        ['name'=>'山形県','top'=>'60%','left'=>'30%'],
        ['name'=>'宮城県','top'=>'55%','left'=>'70%'],
        ['name'=>'福島県','top'=>'80%','left'=>'50%']
    ]],
    'kanto' => ['name'=>'関東地方','prefectures'=>[
        // 密集しているので少し外側に広げる
        ['name'=>'群馬県','top'=>'20%','left'=>'25%'],
        ['name'=>'栃木県','top'=>'20%','left'=>'75%'],
        ['name'=>'茨城県','top'=>'40%','left'=>'85%'],
        ['name'=>'埼玉県','top'=>'45%','left'=>'45%'],
        ['name'=>'東京都','top'=>'65%','left'=>'45%'],
        ['name'=>'千葉県','top'=>'65%','left'=>'80%'],
        ['name'=>'神奈川県','top'=>'75%','left'=>'30%']
    ]],
    'chubu' => ['name'=>'中部地方','prefectures'=>[
        // 非常に縦長かつ変則的な形
        ['name'=>'新潟県','top'=>'15%','left'=>'80%'],
        ['name'=>'富山県','top'=>'30%','left'=>'40%'],
        ['name'=>'石川県','top'=>'30%','left'=>'20%'],
        ['name'=>'福井県','top'=>'45%','left'=>'15%'],
        ['name'=>'長野県','top'=>'50%','left'=>'60%'],
        ['name'=>'岐阜県','top'=>'60%','left'=>'35%'],
        ['name'=>'山梨県','top'=>'65%','left'=>'70%'],
        ['name'=>'愛知県','top'=>'80%','left'=>'40%'],
        ['name'=>'静岡県','top'=>'85%','left'=>'70%']
    ]],
    'kinki' => ['name'=>'近畿地方','prefectures'=>[
        // 琵琶湖などを避けて配置
        ['name'=>'京都府','top'=>'25%','left'=>'50%'],
        ['name'=>'滋賀県','top'=>'30%','left'=>'75%'],
        ['name'=>'兵庫県','top'=>'40%','left'=>'20%'],
        ['name'=>'大阪府','top'=>'55%','left'=>'45%'],
        ['name'=>'奈良県','top'=>'60%','left'=>'65%'],
        ['name'=>'和歌山県','top'=>'80%','left'=>'50%']
    ]],
    'chugoku' => ['name'=>'中国地方','prefectures'=>[
        // 横長。上下2段に分けるイメージ
        ['name'=>'鳥取県','top'=>'30%','left'=>'75%'],
        ['name'=>'島根県','top'=>'35%','left'=>'30%'],
        ['name'=>'岡山県','top'=>'60%','left'=>'70%'],
        ['name'=>'広島県','top'=>'65%','left'=>'45%'],
        ['name'=>'山口県','top'=>'65%','left'=>'15%']
    ]],
    'shikoku' => ['name'=>'四国地方','prefectures'=>[
        // 前回の修正を維持
        ['name'=>'香川県','top'=>'15%','left'=>'60%'],
        ['name'=>'徳島県','top'=>'35%','left'=>'70%'],
        ['name'=>'愛媛県','top'=>'35%','left'=>'30%'],
        ['name'=>'高知県','top'=>'65%','left'=>'50%']
    ]],
    'kyushu' => ['name'=>'九州地方','prefectures'=>[
        // 縦長。重ならないよう左右に振る
        ['name'=>'福岡県','top'=>'15%','left'=>'50%'],
        ['name'=>'佐賀県','top'=>'25%','left'=>'30%'],
        ['name'=>'大分県','top'=>'30%','left'=>'80%'],
        ['name'=>'長崎県','top'=>'35%','left'=>'15%'],
        ['name'=>'熊本県','top'=>'50%','left'=>'40%'],
        ['name'=>'宮崎県','top'=>'65%','left'=>'70%'],
        ['name'=>'鹿児島県','top'=>'80%','left'=>'40%']
    ]],
    'okinawa' => ['name'=>'沖縄地方','prefectures'=>[
        ['name'=>'沖縄県','top'=>'50%','left'=>'50%'] // 中央へ
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
    aspect-ratio:1/1; /* 画像が正方形でない場合、ここを削除して img {height:auto} にする方が安全ですが、今回は維持します */
}
.map-container img { position:absolute; top:0; left:0; width:100%; height:100%; border-radius:10px; }
.pin {
    position:absolute; width:6%; aspect-ratio:1;
    background: radial-gradient(circle at 30% 30%, #555,#000);
    border-radius:50% 50% 50% 0;
    
    /* ★重要: 座標ズレ防止のため translate を rotate より先に実行 */
    transform: translate(-50%, -100%) rotate(-45deg); 
    
    cursor:pointer; box-shadow:0 3px 6px rgba(0,0,0,0.4);
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}
.pin::after {
    content:""; position:absolute; top:25%; left:25%; width:50%; height:50%;
    background:#fff; border-radius:50%; box-shadow: inset 0 0 3px rgba(0,0,0,0.3);
}
.pin:hover { 
    /* hover時も同じ順序を維持 */
    transform: translate(-50%, -100%) rotate(-45deg) scale(1.3); 
    box-shadow:0 6px 10px rgba(0,0,0,0.5); 
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