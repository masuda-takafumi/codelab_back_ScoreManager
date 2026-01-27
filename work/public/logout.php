<?php
/*
ログアウト処理

1. 共通ファイル読み込み
2. セッション開始
3. POSTリクエスト処理
   3.1 CSRFトークン検証
   3.2 セッション破棄
   3.3 ログイン画面へリダイレクト
4. GETリクエスト処理
   4.1 ログイン画面へリダイレクト
*/
require_once '/work/app/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Token::validate();
    session_destroy();
    header('Location: /login.php');
    exit;
}

header('Location: /login.php');
exit;
