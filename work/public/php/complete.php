<?php
// 1. PHP処理 - ログイン確認とログアウト処理
// 1.1 共通ファイル読み込み
require_once '/work/app/config.php';
require_once '/work/app/core.php';

// 1.2 ログイン確認 - ログインしてないとダメ
if (!Utils::isLoggedIn()) {
    header('Location: /login.php');
    exit;
}
// 1.3 ログアウト処理 - ログアウトボタンが押されたら処理
checkLogoutRequest();
?>

<!--
完了画面

1. PHP処理
   1.1 共通ファイル読み込み
   1.2 ログイン確認
   1.3 ログアウト処理
2. HTML構造
   2.1 ヘッダー
   2.2 完了メッセージ
   2.3 戻るリンク
   2.4 フッター
   2.5 ログアウトフォーム
3. スクリプト読み込み
-->

<!-- 2. HTML構造 - 完了画面の表示 -->
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title> - 成績管理システム - 登録完了</title>
  <!-- CSS読み込み - 画面の見た目を整える -->
  <link rel="stylesheet" href="../css/complete.css">
</head>
<body>
  <!-- ヘッダー - ロゴとタイトル -->
  <header>
    <?php echo generateHeader(); ?>
  </header>

  <!-- 完了メッセージ -  -->
  <main class="complete-container">
    <div class="complete-box">
      <div class="circle-placeholder">✔</div>
      <h2>登録が完了しました。</h2>
    </div>
  </main>

  <!-- 戻るリンク - 一覧に戻るボタン -->
  <div class="back-list-wrapper">
    <a href="/php/student_list.php?new=1" class="back-list">←生徒一覧に戻る</a>
  </div>

  <!-- フッター - コピーライト -->
  <footer>
    <?php echo generateFooter(); ?>
  </footer>

  <!-- ログアウトフォーム -->
  <?php echo generateLogoutForm(); ?>

  <!-- 3. スクリプト読み込み - ログアウト機能 -->
  <!-- JavaScript読み込み - ログアウトボタンを動かす -->
  <script src="../js/complete.js"></script>
</body>
</html>
