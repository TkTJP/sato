<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>マイページ</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body>

    <!-- 堀くんが作ってる部分あとで修正
        <header class="app-header">
            <h1>会員登録画面</h1>
            <div class="logo-area">
                <div class="logo-placeholder">ざ<span class="logo-text">SATONOMI</span></div>
            </div>
            <button class="header-button cart-button" aria-label="カート">🛒</button>
            <button class="header-button mypage-button" aria-label="マイページ">👤</button>
        </header>
    -->

    <button class="back-button" onclick="location.href='member.html'">←</button>
    <h1>マイページ</h1>

        <section id="profile">
        <div id="avatar">
            <img src="placeholder_icon.png" alt="ユーザーアイコン" style="width: 100px; height: 100px; border-radius: 50%;">
        </div>

        <!-- ⑦ 名前 -->
        <p id="user-name">山田 太郎</p>

        <!-- ⑧ ポイント -->
        <p id="user-points">所持ポイント：1500</p>
    </section>

    <!-- ここから各種リンクを入れる まだリンク入れてないので飛べません -->
    <section id="menu">

        <!-- MY情報閲覧に遷移する -->
        <div class="menu-item">
            <a href="">My情報</a>
        </div>

        <!-- 購入履歴に遷移する -->
        <div class="menu-item">
            <a href="">購入履歴</a>
        </div>

        <!-- サブスクリプション登録に遷移する -->
        <div class="menu-item">
            <a href="">サブスクリプション登録</a>
        </div>

        <!-- スタンプカードに遷移する -->
        <div class="menu-item">
            <a href="">スタンプカード</a>
        </div>
    </section>

    <!-- ログアウトした時用 まだリンク入れてないです -->
    <footer>
        <div id="logout">
            <a href="">ログアウト</a>
        </div>
    </footer>

</body>
</html>
