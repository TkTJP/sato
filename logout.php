<?php
// --- 先頭で処理開始（HTML出力前に必ず） ---

// セッション開始
session_start();

// --- 削除したいCookieを完全削除 ---
$cookies_to_delete = ['remember_token', 'PHPSESSID'];

foreach ($cookies_to_delete as $cookie_name) {
    if (isset($_COOKIE[$cookie_name])) {
        // ブラウザ側Cookie削除
        setcookie($cookie_name, '', time() - 3600, '/');
        // PHPの$_COOKIE配列からも削除
        unset($_COOKIE[$cookie_name]);
    }
}

// --- セッションを完全破棄 ---
$_SESSION = [];
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}
session_destroy();

// --- ログアウト後はログインページへリダイレクト ---
header("Location: top.php");
exit;
