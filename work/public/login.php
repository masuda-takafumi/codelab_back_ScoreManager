<!--
ログイン画面

1. PHP処理
   1.1 共通ファイル読み込み
   1.2 トークン生成
   1.3 データベース接続
   1.4 Userインスタンス作成
   1.5 フォーム処理
2. HTML構造
   2.1 ヘッダー
   2.2 メインコンテンツ
   2.3 ログインフォーム
   2.4 エラーメッセージ表示
   2.5 フッター
-->
<?php
require_once '/work/app/config.php';
require_once '/work/app/core.php';

Token::create();

$pdo = getDatabaseConnection();
$user = new User($pdo);
$user->processPost();
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>生徒管理システム - ログイン</title>
  <link rel="stylesheet" href="css/user.css">
</head>
<body>
  <header>
    <div class="logo-area">
      <img src="img/生徒管理ロゴ.png" alt="ロゴ" class="logo">
      <span class="title">生徒管理システム</span>
      <span class="subtitle">－Score Manager－</span>
    </div>
  </header>

  <main>
    <div class="container">
      <h2>認証</h2>
      <?php if ($user->hasError()): ?>
        <div class="error-message">
          <?php echo Utils::h($user->getErrors('email') ?: $user->getErrors('password')); ?>
        </div>
      <?php endif; ?>
      <form method="post">
        <input type="hidden" name="action" value="login">
        <input type="hidden" name="token" value="<?php echo Utils::h($_SESSION['token']); ?>">
        <label for="email">メールアドレス</label>
        <input type="email" name="email" id="email" value="<?php echo Utils::h($user->getValues('email')); ?>">
        <label for="password">パスワード</label>
        <input type="password" name="password" id="password">
        <button type="submit">ログイン</button>
      </form>
    </div>
  </main>

  <footer>
    <span>Copyright &copy; Vuetech corp . All Right Reserved</span>
  </footer>
</body>
</html>
