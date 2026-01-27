<?php
/*
アプリケーション設定ファイル

1. エラー表示設定
   1.1 ini_set('display_errors', 1)
2. クラスファイル読み込み
   2.1 Token.php
   2.2 Utils.php
   2.3 User.php
*/

// 1. エラー表示設定
ini_set('display_errors', 1);

// 2. クラスファイル読み込み
require_once __DIR__ . '/Token.php';
require_once __DIR__ . '/Utils.php';
require_once __DIR__ . '/User.php';
