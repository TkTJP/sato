<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>header</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <style>
    /* ヘッダー全体 */
    header {
      background-color: #F1E9D6;
      height: 15vh; /* 高さを画面の15%に固定 */
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 0 2rem;
      box-sizing: border-box;
    }

    /* ロゴ＋タイトル部分 */
    .logo {
      display: flex;
      align-items: flex-end; /* 下揃えでタイトルと高さを合わせる */
      height: 100%;
    }

    /* ロゴ画像 */
    .logo img {
      height: 90%; /* ヘッダー全体の70%程度の高さ */
      object-fit: contain;
    }

    /* サイトタイトル */
    .site-title {
      font-size: 2.2rem;
      font-weight: bold;
      margin-left: 1rem;
      margin-bottom: 0.3rem; /* 文字をやや下に寄せる */
      text-shadow: 2px 2px 3px rgba(0, 0, 0, 0.3);
      white-space: nowrap;
    }

    /* 丸いアイコン背景 */
    .icon-circle {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      width: 40px;
      height: 40px;
      border-radius: 50%;
      background-color: white;
      color: black;
      font-size: 1.1rem;
      box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
      transition: all 0.2s ease;
    }

    .icon-circle:hover {
      background-color: #ddd;
      transform: translateY(-2px);
    }
  </style>
</head>
<body>
  <header>
    <div class="logo">
      <img src="img/logo.png" alt="ロゴ">
      <span class="site-title">SATONOMI</span>
    </div>
    <div class="level-right">
      <a class="icon-circle mr-4" href="#">
        <i class="fas fa-shopping-cart"></i>
      </a>
      <a class="icon-circle" href="#">
        <i class="fas fa-user"></i>
      </a>
    </div>
  </header>
</body>
</html>
