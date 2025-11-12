<?php
// --- Cookieを完全に削除する処理 ---

// 削除したいCookie名（複数ある場合は配列でもOK）
$cookies_to_delete = ['remember_token', 'PHPSESSID'];

// 1つずつ削除
foreach ($cookies_to_delete as $cookie_name) {
    if (isset($_COOKIE[$cookie_name])) {
        // Cookieの削除（ブラウザ側）
        setcookie($cookie_name, '', time() - 3600, '/');
        // PHPの$_COOKIE配列からも削除
        unset($_COOKIE[$cookie_name]);
    }
}

// --- セッションも完全に削除（任意） ---
session_start();
$_SESSION = [];
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}
session_destroy();

// --- 結果を表示 ---
echo "Cookieとセッションを完全に削除しました。";
?>
