<?php
/*
 * student detail page bootstrap
 */

require_once '/work/app/config.php';
require_once '/work/app/core.php';
require_once '/work/app/Models/ScoreModel.php';
require_once '/work/app/Models/StudentModel.php';

if (!Utils::isLoggedIn()) {
    header('Location: /login.php');
    exit;
}

checkLogoutRequest();

$detail_view_data = StudentModel::getDefaultDetailViewData($_GET);
try {
    $pdo = getDatabaseConnection();
    $detail_view_data = StudentModel::getDetailViewData($pdo, $_GET['id'] ?? '', $_GET);
} catch (Exception $e) {

}
$student = $detail_view_data['student'];
$photo_path = $detail_view_data['photo_path'];
$return_url = $detail_view_data['return_url'];

$existing_scores = [];
if ($student['id']) {
    try {
        $pdo = getDatabaseConnection();
        $rows = ScoreModel::getTestsWithScores($pdo, $student['id']);
        $existing_scores = ScoreModel::buildScoreViewData($rows);
    } catch (Exception $e) {
        $existing_scores = [];
    }
}
?>

<!-- HTML出力 -->
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <title>生徒管理システム - 生徒詳細</title>
  <link rel="stylesheet" href="/css/student_detail.css">
</head>
<body>

  <!-- ヘッダー -->
  <header>
    <?php echo generateHeader(); ?>
  </header>
  <h1 class="student-detail-title">生徒情報詳細</h1>

  <main>
    <section id="register-section">

      <!-- 生徒情報フォーム -->
      <form id="student-register-form" method="POST" action="student.sousa.php" enctype="multipart/form-data">
        <fieldset class="register-area">
          <legend class="hidden">新規生徒登録フォーム</legend>
          <input type="hidden" name="action" value="update_student">
          <input type="hidden" name="student_id" value="<?php echo htmlspecialchars($student['id']); ?>">

          <!-- 写真アップロード -->
          <section class="photo-upload">
            <img id="student-photo" src="<?php echo htmlspecialchars($photo_path); ?>" alt="写真" class="photo-preview">
            <input type="file" id="photo-input" name="photo" accept="image/jpeg,image/jpg" class="hidden">
            <button type="button" id="photo-btn">写真を挿入</button>
            <div id="photo-error" class="photo-error hidden"></div>
          </section>

          <!-- 基本情報入力 -->
          <section class="info-input">
            <div class="row">
              <label for="last-name">氏名(姓)</label>
              <input type="text" id="last-name" name="last_name" value="<?php echo htmlspecialchars($student['last_name']); ?>" placeholder="氏名(姓)" required>
              <label for="first-name">氏名(名)</label>
              <input type="text" id="first-name" name="first_name" value="<?php echo htmlspecialchars($student['first_name']); ?>" placeholder="氏名(名)" required>
            </div>
            <div class="row">
              <label for="last-name-kana">氏名(せい)</label>
              <input type="text" id="last-name-kana" name="last_name_kana" value="<?php echo htmlspecialchars($student['last_name_kana']); ?>" placeholder="氏名(せい)" required pattern="[ぁ-ん]+">
              <label for="first-name-kana">氏名(めい)</label>
              <input type="text" id="first-name-kana" name="first_name_kana" value="<?php echo htmlspecialchars($student['first_name_kana']); ?>" placeholder="氏名(めい)" required pattern="[ぁ-ん]+">
            </div>
            <div class="row">
              <label for="class-select">クラス</label>
              <select id="class-select" name="class" required>
                <?php echo generateClassOptions($student['class']); ?>
              </select>
              <label for="gender-select">性別</label>
              <select id="gender-select" name="gender" required>
                <?php echo generateGenderOptions($student['gender']); ?>
              </select>
            </div>
            <div class="row">
              <label for="class-number">クラス番号</label>
              <select id="class-number" name="class_no" required>
                <?php echo generateSelectOptions(1, 31, intval($student['class_no']), 'クラス番号'); ?>
              </select>
            </div>
            <div class="row">
              <label for="birth-year">生年月日</label>
              <select id="birth-year" name="birth_year" required>
                <?php echo generateSelectOptions(1990, 2020, intval($student['birth_year']), '年'); ?>
              </select>
              <span>年</span>
              <select id="birth-month" name="birth_month" required>
                <?php echo generateSelectOptions(1, 12, intval($student['birth_month']), '月'); ?>
              </select>
              <span>月</span>
              <select id="birth-day" name="birth_day" required>
                <?php echo generateSelectOptions(1, 31, intval($student['birth_day']), '日'); ?>
              </select>
              <span>日</span>
              <button type="submit" id="register-btn">更新</button>
            </div>

            <div id="validation-error" class="validation-error hidden">
              未入力の項目があります
            </div>
          </section>
        </fieldset>
      </form>

    </section>

    <!-- 成績一覧 -->
    <section class="score-area">
      <h2>成績一覧</h2>
      <table id="score-table">
        <thead>
          <tr>
            <th>実施日</th>
            <th>種別</th>
            <th>国語</th>
            <th>数学</th>
            <th>英語</th>
            <th>理科</th>
            <th>社会</th>
            <th>平均</th>
            <th>合計</th>
            <th>保存</th>
            <th>削除</th>
          </tr>
        </thead>
        <tbody id="score-table-body">

          <?php foreach ($existing_scores as $score): ?>
          <tr data-existing="true" data-test-id="<?php echo htmlspecialchars($score['test_id']); ?>">
            <td><span class="score-date score-display" style="width:120px;"><?php echo htmlspecialchars($score['test_date']); ?></span></td>
            <td><span class="score-type score-display"><?php echo htmlspecialchars($score['test_type']); ?></span></td>
            <td><input type="number" class="score-input" name="score_inputs[japanese]" form="score-save-form-<?php echo htmlspecialchars($score['test_id']); ?>" value="<?php echo htmlspecialchars($score['japanese'] ?? '0'); ?>" min="0" max="100" style="width:60px;"></td>
            <td><input type="number" class="score-input" name="score_inputs[math]" form="score-save-form-<?php echo htmlspecialchars($score['test_id']); ?>" value="<?php echo htmlspecialchars($score['math'] ?? '0'); ?>" min="0" max="100" style="width:60px;"></td>
            <td><input type="number" class="score-input" name="score_inputs[english]" form="score-save-form-<?php echo htmlspecialchars($score['test_id']); ?>" value="<?php echo htmlspecialchars($score['english'] ?? '0'); ?>" min="0" max="100" style="width:60px;"></td>
            <td><input type="number" class="score-input" name="score_inputs[science]" form="score-save-form-<?php echo htmlspecialchars($score['test_id']); ?>" value="<?php echo htmlspecialchars($score['science'] ?? '0'); ?>" min="0" max="100" style="width:60px;"></td>
            <td><input type="number" class="score-input" name="score_inputs[social]" form="score-save-form-<?php echo htmlspecialchars($score['test_id']); ?>" value="<?php echo htmlspecialchars($score['social'] ?? '0'); ?>" min="0" max="100" style="width:60px;"></td>
            <td><span class="score-avg score-display" style="width:60px;"><?php echo htmlspecialchars($score['avg']); ?></span></td>
            <td><span class="score-sum score-display" style="width:60px;"><?php echo htmlspecialchars($score['total']); ?></span></td>
            <td>
              <form method="POST" action="student.sousa.php" class="score-save-form" id="score-save-form-<?php echo htmlspecialchars($score['test_id']); ?>">
                <input type="hidden" name="action" value="save_score">
                <input type="hidden" name="student_id" class="score-student-id" value="<?php echo htmlspecialchars($student['id']); ?>">
                <input type="hidden" name="test_id" class="score-test-id" value="<?php echo htmlspecialchars($score['test_id']); ?>">
                <input type="hidden" name="test_date" class="score-test-date" value="<?php echo htmlspecialchars($score['test_date']); ?>">
                <input type="hidden" name="test_type" class="score-test-type" value="<?php echo htmlspecialchars($score['test_type']); ?>">
                <button type="submit" class="score-save action">保存</button>
              </form>
            </td>
            <td>
              <form method="POST" action="student.sousa.php" class="score-delete-form">
                <input type="hidden" name="action" value="delete_score">
                <input type="hidden" name="student_id" class="score-delete-student-id" value="<?php echo htmlspecialchars($student['id']); ?>">
                <input type="hidden" name="test_id" class="score-delete-test-id" value="<?php echo htmlspecialchars($score['test_id']); ?>">
                <button type="submit" class="score-delete action">削除</button>
              </form>
            </td>
          </tr>
          <?php endforeach; ?>

        </tbody>
      </table>
    </section>
    <div class="back-list-wrapper">
      <a href="<?php echo htmlspecialchars($return_url); ?>" class="back-list">←生徒一覧に戻る</a>
    </div>

  </main>

  <!-- フッター -->
  <footer>
    <?php echo generateFooter(); ?>
  </footer>

  <?php echo generateLogoutForm(); ?>

  <!-- スクリプト読み込み -->
  <script src="/js/student_detail.js"></script>
</body>
</html>
