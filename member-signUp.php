<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>会員登録画面</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="app-container">

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

        <main class="registration-form-container">
            <button class="back-button" onclick="location.href='member.html'">←</button>

            <div class="field-image-area">
                <div class="profile-image-placeholder">
                    ここにプロフィール画像を入れる
                </div>
            </div>

            <form class="registration-form" action="member-signUp-complete.html">
                <!-- 名前を入力する -->
                <div class="form-group">
                    <label for="name">名前</label>
                    <input type="text" id="name" maxlength="32" placeholder="山田 太郎">
                </div>

                <!-- 生年月日を入力する -->
                <div class="form-group">
                    <label for="birthdate">生年月日</label>
                    <input type="date" id="birthdate" value="2000-01-01">
                </div>
                
                <!-- メールアドレスを入力する -->
                <div class="form-group">
                    <label for="email">メールアドレス</label>
                    <input type="email" id="email" maxlength="64" placeholder="example@satonomi.com">
                </div>
                
                <!-- パスワードを入力する -->
                <div class="form-group">
                    <label for="password">パスワード</label>
                    <input type="password" id="password" maxlength="64">
                </div>

                <!-- 郵便番号を入力する -->
                <div class="form-group postal-group">
                    <label for="postal">郵便番号</label>
                    <input type="text" id="postal" maxlength="7" pattern="\d{3}-?\d{4}" placeholder="例: 1234567">
                    <button type="button" class="search-button">検索</button>
                </div>
                
                <!-- 住所1を入力する -->
                <div class="form-group">
                    <label for="address1">住所1</label>
                    <input type="text" id="address1" maxlength="128" placeholder="都道府県 市区町村">
                </div>
                
                <!-- 住所2を入力する -->
                <div class="form-group">
                    <label for="address2">住所2</label>
                    <input type="text" id="address2" maxlength="128" placeholder="番地・建物名・部屋番号">
                </div>

                <!-- 登録完了画面に遷移する -->
                <div class="form-group submit-group">
                    <button type="submit" class="submit-button">登録</button>
                </div>
            </form>
        </main>
    </div>
</body>
</html>
