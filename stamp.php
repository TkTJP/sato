<?php
session_start();
// DB接続情報ファイルへのパスは環境に合わせてください
require 'db-connect.php'; 

// DB接続
try {
    // 接続情報はdb-connect.phpに定義されていると想定
    $pdo = new PDO($connect, USER, PASS); 
    // エラーモードを例外に設定
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); 
} catch (PDOException $e) {
    exit('DB接続エラー: ' . $e->getMessage()); 
}

// ログイン確認 (profile.phpと同じロジック)
if (empty($_SESSION['customer']['customer_id'])) {
    exit('ログイン情報がありません。'); 
}

$customer_id = $_SESSION['customer']['customer_id'];
$message = ''; // ユーザーへの通知メッセージ
$MAX_STAMPS = 8; // スタンプの上限数

// -----------------------------------------------------------------
// 景品リスト (景品マスタテーブルの代わり)
// インデックス (景品IDの代わり), 景品名, 必要なスタンプ数, 初期交換ステータス
$prize_list = [
    0 => ['30%offクーポン', 3, 'available'], 
    1 => ['500ポイント', 5, 'available'],    
    2 => ['10%offクーポン', 5, 'exchanged'],  
    3 => ['300ポイント', 3, 'exchanged'],    
    4 => ['VIPチケット', 8, 'available'],     
];

// 景品交換履歴をセッションで一時的に管理 (exchange_historyテーブルがないため)
// 景品ID (インデックス) がキーとなる
if (!isset($_SESSION['exchanged_prizes'])) {
    // 初回アクセス時、静的なリストの'exchanged'状態をセッションに反映
    $_SESSION['exchanged_prizes'] = [];
    foreach ($prize_list as $index => $prize) {
        if ($prize[2] === 'exchanged') {
            $_SESSION['exchanged_prizes'][$index] = true;
        }
    }
}
// -----------------------------------------------------------------


// --- ★DBからスタンプ数と景品情報を取得する処理★ ---

// 1-1. stamp_cardsテーブルにレコードがあるか確認し、なければ挿入する (UPSERT的な処理)
try {
    $sql_check = $pdo->prepare('SELECT stamp_count FROM stamp_cards WHERE customer_id = ?');
    $sql_check->execute([$customer_id]);
    $data = $sql_check->fetch(PDO::FETCH_ASSOC);

    if (!$data) {
        // レコードがない場合、新規挿入
        $sql_insert = $pdo->prepare('INSERT INTO stamp_cards (customer_id, stamp_count) VALUES (?, 0)');
        $sql_insert->execute([$customer_id]);
        $current_stamps = 0;
    } else {
        $current_stamps = (int)$data['stamp_count'];
    }
} catch (PDOException $e) {
    $message = 'スタンプ情報の取得に失敗しました。';
    $current_stamps = 0;
    // 実際は $e->getMessage() をログに記録
}


// --- 景品交換処理の実行 (フォーム送信時) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['exchange_prize_id'])) {
    $exchange_prize_id = (int)$_POST['exchange_prize_id'];
    $prize_info = $prize_list[$exchange_prize_id] ?? null;

    if ($prize_info === null) {
        $message = '景品情報が見つかりませんでした。';
    } elseif (isset($_SESSION['exchanged_prizes'][$exchange_prize_id])) {
        // セッションの交換済みフラグで確認
        $message = 'この景品は既に交換済みです。';
    } else {
        $prize_name = $prize_info[0];
        $required_stamps = $prize_info[1];

        if ($current_stamps < $required_stamps) {
            $message = 'スタンプが不足しています。必要スタンプ数: ' . $required_stamps . '個';
        } else {
            // --- トランザクション処理開始 ---
            try {
                $pdo->beginTransaction();

                // 1. スタンプを消費 (スタンプ数減算)
                $new_stamp_count = $current_stamps - $required_stamps;
                
                // スタンプ数は0を下回らないようにする
                if ($new_stamp_count < 0) $new_stamp_count = 0;

                $sql_update_stamps = $pdo->prepare('UPDATE stamp_cards SET stamp_count = ? WHERE customer_id = ?');
                $sql_update_stamps->execute([$new_stamp_count, $customer_id]);

                // 2. 交換履歴にレコードを追加 (exchange_historyテーブルがないため、セッションを更新)
                // 実際は、別途作成した exchange_history テーブルに INSERT します。
                $_SESSION['exchanged_prizes'][$exchange_prize_id] = true;
                
                $pdo->commit();
                
                // 成功メッセージとスタンプ数更新
                $message = '景品「' . $prize_name . '」を交換しました！スタンプを' . $required_stamps . '個消費しました。';
                $current_stamps = $new_stamp_count;

            } catch (Exception $e) {
                $pdo->rollBack();
                $message = '景品交換中にエラーが発生しました。再度お試しください。';
                // 実際は $e->getMessage() をログに記録
            }
        }
    }
}


// 最終的な景品リストの状態を確定し、交換済み情報を反映
$final_prize_list = $prize_list;
foreach ($final_prize_list as $index => &$prize) {
    if (isset($_SESSION['exchanged_prizes'][$index])) {
        $prize[2] = 'exchanged'; // 交換済みステータスを更新
    } else {
        $prize[2] = 'available'; // availableステータスを維持
    }
}
unset($prize); // 参照を解除
// -----------------------------------------------------------------
?>

<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>スタンプカード</title>
  <link rel="stylesheet" href="style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    /* デザイン画像に合わせたスタイリングを追記 */
    .stamp-card-container {
        max-width: 600px;
        margin: 20px auto;
        padding: 20px;
    }
    .stamp-card-area {
        background-color: #f7f7e8; /* カード背景色 */
        padding: 20px;
        border-radius: 10px;
        margin: 20px 0;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }
    .stamp-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr); /* 4列 */
        gap: 10px;
        border: 2px solid #ccc; /* 枠線 */
        padding: 5px;
        background-color: #ffffff;
    }
    .stamp-cell {
        aspect-ratio: 1 / 1; /* 正方形 */
        border: 1px solid #eee;
        display: flex;
        justify-content: center;
        align-items: center;
        background-color: #ffffff;
    }
    .stamp-cell img {
        width: 80%;
        height: 80%;
        object-fit: contain;
        /* stamp.pngの色（赤）に合わせるためのフィルタ */
        filter: invert(10%) sepia(90%) saturate(700%) hue-rotate(330deg) brightness(1.2);
    }
    .prize-list {
        margin-top: 30px;
    }
    .prize-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        /* 画像の景品一覧の色に近づける */
        background-color: #e8f5e9; 
        padding: 10px;
        margin-bottom: 5px;
        border-radius: 8px;
    }
    .prize-info {
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .prize-icon {
        background-color: #ffc107; /* ポイント/クーポンの背景色 */
        padding: 5px;
        border-radius: 5px;
        min-width: 30px;
        text-align: center;
    }
    .exchange-button {
        padding: 8px 15px;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        font-weight: bold;
        min-width: 100px;
        white-space: nowrap;
    }
    .exchange-available {
        background-color: #69f0ae; /* 交換するボタンの色 (グリーン系) */
        color: #333;
    }
    .exchange-disabled {
        background-color: #eee;
        color: #999;
        cursor: not-allowed;
    }
    .exchange-exchanged {
        background-color: #b0e0c9; /* 交換済みボタンの色 (薄いグリーン系) */
        color: #333;
        cursor: not-allowed;
    }
    .title-message {
        text-align: center;
        font-size: 1.1em;
        font-weight: bold;
        color: #555;
        padding-bottom: 10px;
    }
    .alert-message {
        padding: 10px;
        margin: 10px 0;
        border: 1px solid;
        border-radius: 5px;
        background-color: #ffc10740;
        border-color: #ffc107;
        color: #333;
        text-align: center;
    }
  </style>
</head>
<body>

  <?php include('header.php'); ?>
  
  <div class="stamp-card-container">
    <nav class="nav-bar">
        <button class="back-button" onclick="history.back()">
            <i class="fa-solid fa-arrow-left"></i>
        </button>
        <span class="nav-title">スタンプカード</span>
    </nav>
    
    <?php if ($message): ?>
        <div class="alert-message"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <p class="title-message">
        \スタンプをためて景品を獲得しよう！/
    </p>

    <div class="stamp-card-area">
        <div class="stamp-grid">
            <?php 
            for ($i = 0; $i < $MAX_STAMPS; $i++): 
                if ($i < $current_stamps): 
            ?>
                    <div class="stamp-cell">
                        <img src="sato/image/stamp.png" alt="スタンプ"> 
                    </div>
            <?php 
                else: 
            ?>
                    <div class="stamp-cell"></div>
            <?php 
                endif; 
            endfor; 
            ?>
        </div>
        <p style="text-align: right; margin-top: 10px; font-size: 0.9em;">
            現在のスタンプ数: **<?= $current_stamps ?>** / <?= $MAX_STAMPS ?>
        </p>
    </div>

    <h3>景品一覧</h3>
    
    <div class="prize-list">
        <?php foreach ($final_prize_list as $index => $prize): 
            $prize_name = $prize[0];
            $required_stamps = $prize[1];
            $status = $prize[2]; 

            // ボタンの状態を判定
            $button_class = 'exchange-disabled';
            $button_text = '交換する';
            $button_disabled = 'disabled';
            $form_action = '#'; 

            if ($status === 'exchanged') {
                $button_class = 'exchange-exchanged';
                $button_text = '交換済み';
            } elseif ($current_stamps >= $required_stamps) {
                // 交換可能 (スタンプが足りている)
                $button_class = 'exchange-available';
                $button_text = '交換する';
                $button_disabled = '';
                $form_action = 'stamp_card.php'; // POST処理を行う
            }
        ?>
            <div class="prize-item">
                <div class="prize-info">
                    <span class="prize-icon">
                      <i class="fa-solid <?= strpos($prize_name, 'ポイント') !== false ? 'fa-coins' : 'fa-tags' ?>" style="color: #333;"></i>
                    </span>
                    <span><?= htmlspecialchars($prize_name) ?></span>
                    <span style="font-size: 0.8em; color: #777;">(スタンプ<?= $required_stamps ?>個)</span>
                </div>
                
                <form method="POST" action="<?= $form_action ?>" style="margin: 0;">
                    <input type="hidden" name="exchange_prize_id" value="<?= $index ?>">
                    <button type="submit" 
                            class="exchange-button <?= $button_class ?>" 
                            <?= $button_disabled ?>>
                        <?= $button_text ?>
                    </button>
                </form>
            </div>
        <?php endforeach; ?>
    </div>
  </div>

</body>
</html>