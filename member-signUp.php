<?php
    require 'db-connect.php';
    require 'header.php';
?>

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

            <main class="registration-form-container">
                <button class="back-button" onclick="location.href='member.php'">←</button>

                <div class="field-image-area">
                    <div class="profile-image-placeholder">
                        ここにプロフィール画像を入れる
                    </div>
                </div>

                <!-- PHPファイルに送信 -->
                <form class="registration-form" action="member-signUp-function.php" method="post">
                    
                    <!-- 名前 -->
                    <div class="form-group">
                        <label for="name">名前</label>
                        <input type="text" id="name" name="name" maxlength="32" required>
                    </div>
                    
                    <!-- メールアドレス -->
                    <div class="form-group">
                        <label for="email">メールアドレス</label>
                        <input type="email" id="email" name="email" maxlength="64" required>
                    </div>
                    
                    <!-- パスワード -->
                    <div class="form-group">
                        <label for="password">パスワード</label>
                        <input type="password" id="password" name="password" maxlength="64" required>
                    </div>

                    <!-- 郵便番号 -->
                    <div class="form-group postal-group">
                        <label for="postal_code">郵便番号</label>
                        <input type="text" id="postal_code" name="postal_code" maxlength="7" pattern="\d{3}-?\d{4}" required>
                        <button type="button" class="search-button">検索</button>
                    </div>
                    
                    <!-- 都道府県 -->
                    <div class="form-group">
                        <label for="prefecture">都道府県</label>
                        <input type="text" id="prefecture" name="prefecture" maxlength="64" required>
                    </div>

                    <!-- 市区町村 -->
                    <div class="form-group">
                        <label for="city">市区町村</label>
                        <input type="text" id="city" name="city" maxlength="64" required>
                    </div>

                    <!-- 番地・建物名など -->
                    <div class="form-group">
                        <label for="street">番地・建物名・部屋番号</label>
                        <input type="text" id="street" name="street" maxlength="128" required>
                    </div>

                    <!-- 電話番号 -->
                    <div class="form-group">
                        <label for="phone_number">電話番号</label>
                        <input type="tel" id="phone_number" name="phone_number" maxlength="15" required>
                    </div>

                    <!-- 登録ボタン -->
                    <div class="form-group submit-group">
                        <button type="submit" class="submit-button">登録</button>
                    </div>
                </form>
            </main>
        </div>
    </body>
    </html>