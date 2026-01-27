<?php
/*
 * 役割：生徒一覧と新規登録フォーム
 * 1) 共通読み込み/ログイン確認
 * 2) 入力取得（検索/ページ）
 * 3) 一覧取得（検索条件）
 * 4) 件数取得とページング
 * 5) 一覧URL生成
 * 6) HTML出力（タブ/一覧/登録）
 * 7) 削除フォーム
 * 8) スクリプト読み込み
 */

// 共通読み込み/ログイン確認
require_once '/work/app/config.php';
require_once '/work/app/core.php';

if (!Utils::isLoggedIn()) {
    header('Location: /login.php');
    exit;
}

checkLogoutRequest();

// 入力取得（検索/ページ）
$students = [];
$search_query = trim($_GET['q'] ?? '');
$page_param = $_GET['page'] ?? '';
$new_param = $_GET['new'] ?? '';
$current_page = (ctype_digit((string)$page_param) && (int)$page_param > 0) ? (int)$page_param : 1;
$items_per_page = 10;
$total_students = 0;
$total_pages = 1;

// 生徒一覧取得/ページング
try {
    $pdo = getDatabaseConnection();
    $where_sql = '';
    $where_params = [];
    // GETの検索語を条件にする
    if ($search_query !== '') {
        $where_sql = "WHERE last_name LIKE ? OR first_name LIKE ? OR last_name_kana LIKE ? OR first_name_kana LIKE ?";
        $like_query = '%' . $search_query . '%';
        $where_params = [$like_query, $like_query, $like_query, $like_query];
    }

    // 件数取得とページング
    $count_stmt = $pdo->prepare("SELECT COUNT(*) FROM students $where_sql");
    $count_stmt->execute($where_params);
    $total_students = (int)$count_stmt->fetchColumn();
    $total_pages = (int)ceil($total_students / $items_per_page);
    if ($total_pages < 1) {
        $total_pages = 1;
    }
    if ($new_param === '1' && $page_param === '') {
        $current_page = $total_pages;
    }
    if ($current_page > $total_pages) {
        $current_page = $total_pages;
    }

    // 一覧URL生成
    $page_buttons = buildPaginationButtons($current_page, $total_pages);

    // LIMIT/OFFSETでページング
    $offset = ($current_page - 1) * $items_per_page;
    $stmt = $pdo->prepare("SELECT * FROM students $where_sql ORDER BY class, class_no LIMIT ? OFFSET ?");
    foreach ($where_params as $index => $value) {
        $stmt->bindValue($index + 1, $value, PDO::PARAM_STR);
    }
    $stmt->bindValue(count($where_params) + 1, $items_per_page, PDO::PARAM_INT);
    $stmt->bindValue(count($where_params) + 2, $offset, PDO::PARAM_INT);
    $stmt->execute();
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $students = [];
}
?>

<!-- HTML出力 -->
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <title>生徒管理システム - 生徒一覧</title>
  <link rel="stylesheet" href="../css/student_list.css">
</head>
<body>

  <!-- ヘッダー -->
  <header>
    <?php echo generateHeader(); ?>
  </header>

  <main>

    <!-- タブ -->
    <nav class="tabs">
      <button class="tab active" id="tab-list">生徒一覧</button>
      <button class="tab" id="tab-register">新規生徒登録</button>
    </nav>

    <!-- 生徒一覧 -->
    <section id="tab-content-list" class="tab-content">

      <!-- 検索 -->
      <form class="search-box" method="get" action="student_list.php">
        <div class="search-input-wrapper">
        <input type="text" id="search-name" class="pill-input" name="q" value="<?php echo htmlspecialchars($search_query); ?>" placeholder="生徒名（漢字・かな）">
          <button type="button" id="search-clear" class="search-clear" aria-label="検索をクリア">×</button>
        </div>
        <button id="search-btn" class="search-btn" type="submit">検索</button>
      </form>

      <!-- 生徒テーブル -->
      <table class="student-list-table student-table">
        <thead>
          <tr>
            <th>クラス</th>
            <th>クラス番号</th>
            <th>性別</th>
            <th>生年月日</th>
            <th>氏名</th>
            <th>かな</th>
            <th>詳細</th>
            <th>削除</th>
          </tr>
        </thead>
        <tbody id="student-table-body">
          <?php foreach ($students as $student): ?>
            <tr>
              <td><?php echo htmlspecialchars($student['class']); ?></td>
              <td><?php echo htmlspecialchars($student['class_no']); ?></td>
              <td><?php echo $student['gender'] == 1 ? '男' : '女'; ?></td>
              <td><?php echo htmlspecialchars($student['birth_date']); ?></td>
              <td><?php echo htmlspecialchars($student['last_name'] . ' ' . $student['first_name']); ?></td>
              <td><?php echo htmlspecialchars($student['last_name_kana'] . ' ' . $student['first_name_kana']); ?></td>
              <td>
                <?php
                  $detail_params = ['id' => $student['id']];
                  if ($search_query !== '') $detail_params['q'] = $search_query;
                  if ($current_page > 1) $detail_params['page'] = $current_page;
                  $detail_url = buildUrl('/php/student_detail.php', $detail_params);
                ?>
                <a href="<?php echo htmlspecialchars($detail_url); ?>" class="detail-link pill-button">詳細</a>
              </td>
              <td>
                <button type="button" class="delete-btn pill-button" data-student-id="<?php echo $student['id']; ?>">削除</button>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <div class="pagination" id="pege-btn">
        <?php if ($total_students > $items_per_page): ?>
          <form method="get" action="student_list.php">
            <input type="hidden" name="q" value="<?php echo htmlspecialchars($search_query); ?>">
            <?php foreach ($page_buttons as $button): ?>
              <button <?php echo $button['attrs']; ?>><?php echo htmlspecialchars($button['label']); ?></button>
            <?php endforeach; ?>
          </form>
        <?php endif; ?>
      </div>

      <!-- 削除フォーム -->
      <form id="delete-student-form" method="POST" action="student.sousa.php" style="display: none;">
        <input type="hidden" name="action" value="delete_student">
        <input type="hidden" name="student_id" id="delete-student-id">
      </form>
    </section>

    <!-- 新規登録 -->
    <section id="tab-content-register" class="hidden">
      <form id="student-register-form" method="POST" action="student.sousa.php" enctype="multipart/form-data">
        <fieldset class="register-area">
            <legend class="hidden">新規生徒登録フォーム</legend>
            <input type="hidden" name="action" value="register_student">

          <!-- 写真アップロード -->
          <section class="photo-upload">
            <img id="student-photo" src="../img/ダミー生徒画像.png" alt="写真" class="photo-preview">
            <input type="file" id="photo-input" name="photo" accept="image/jpeg,image/jpg" class="hidden">
            <button type="button" id="photo-btn">写真を挿入</button>
            <div id="photo-error" class="photo-error hidden"></div>
          </section>

          <!-- 基本情報入力 -->
          <section class="info-input">
            <div class="row">
              <label for="last-name">氏名(姓)</label>
              <input type="text" id="last-name" class="pill-input" name="last_name" placeholder="氏名(姓)" required>
              <label for="first-name">氏名(名)</label>
              <input type="text" id="first-name" class="pill-input" name="first_name" placeholder="氏名(名)" required>
            </div>
            <div class="row">
              <label for="last-name-kana">氏名(せい)</label>
              <input type="text" id="last-name-kana" class="pill-input" name="last_name_kana" placeholder="氏名(せい)" required pattern="[ぁ-ん]+">
              <label for="first-name-kana">氏名(めい)</label>
              <input type="text" id="first-name-kana" class="pill-input" name="first_name_kana" placeholder="氏名(めい)" required pattern="[ぁ-ん]+">
            </div>
            <div class="row">
              <label for="class-select">クラス</label>
              <select id="class-select" class="pill-input" name="class" required>
                <?php echo generateClassOptions(); ?>
              </select>
              <label for="gender-select">性別</label>
              <select id="gender-select" class="pill-input" name="gender" required>
                <?php echo generateGenderOptions(); ?>
              </select>
            </div>
            <div class="row">
              <label for="class-number">クラス番号</label>
              <select id="class-number" class="pill-input" name="class_no" required>
                <?php echo generateSelectOptions(1, 31, '', 'クラス番号'); ?>
              </select>
            </div>
            <div class="row">
              <label for="birth-year">生年月日</label>
              <select id="birth-year" class="pill-input" name="birth_year" required>
                <?php echo generateSelectOptions(1990, 2020, '', '年'); ?>
              </select>
              <span>年</span>
              <select id="birth-month" class="pill-input" name="birth_month" required>
                <?php echo generateSelectOptions(1, 12, '', '月'); ?>
              </select>
              <span>月</span>
              <select id="birth-day" class="pill-input" name="birth_day" required>
                <?php echo generateSelectOptions(1, 31, '', '日'); ?>
              </select>
              <span>日</span>
              <button type="submit" id="register-btn">登録</button>
            </div>
        <div id="validation-error" class="validation-error hidden">
          未入力の項目があります
        </div>
          </section>
        </fieldset>
      </form>
      <div class="back-list-wrapper">
        <a href="/php/student_list.php" class="back-list">←生徒一覧に戻る</a>
      </div>

    </section>
  </main>

  <!-- フッター -->
  <footer>
    <?php echo generateFooter(); ?>
  </footer>

  <?php echo generateLogoutForm(); ?>

  <!-- スクリプト読み込み -->
  <script src="../js/student_list.js?v=<?php echo time(); ?>"></script>
</body>
</html>


