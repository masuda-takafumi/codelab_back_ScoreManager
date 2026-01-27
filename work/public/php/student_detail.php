<?php
/*
 * 役割：生徒詳細の表示・成績一覧・編集フォーム
 * 1) 共通読み込み/ログイン確認
 * 2) 入力取得と初期値
 * 3) 生徒情報取得
 * 4) 写真パス決定
 * 5) 成績取得（LEFT JOIN/COALESCE）
 * 6) 合計/平均と未受験判定
 * 7) 戻りURL作成
 * 8) HTML出力
 * 9) スクリプト読み込み
 */

// 共通読み込み/ログイン確認
require_once '/work/app/config.php';
require_once '/work/app/core.php';

if (!Utils::isLoggedIn()) {
    header('Location: /login.php');
    exit;
}

checkLogoutRequest();

// 入力取得と初期値
$student_id = $_GET['id'] ?? '';
$class = '';
$class_no = '';
$last_name = '';
$first_name = '';
$last_name_kana = '';
$first_name_kana = '';
$gender = '';
$birth_date = '';
$birth_year = '';
$birth_month = '';
$birth_day = '';
$gender_text = '';
$photo_path = '../img/ダミー生徒画像.png';

// 生徒情報取得
if ($student_id) {
    try {
        $pdo = getDatabaseConnection();
        $stmt = $pdo->prepare("SELECT * FROM students WHERE id = ?");
        $stmt->execute([$student_id]);
        $student = $stmt->fetch();

        if ($student) {
            $class = $student['class'];
            $class_no = $student['class_no'];
            $last_name = $student['last_name'];
            $first_name = $student['first_name'];
            $last_name_kana = $student['last_name_kana'];
            $first_name_kana = $student['first_name_kana'];
            $gender = $student['gender'];
            $birth_date = $student['birth_date'];
            $gender_text = ($gender == '1') ? '男' : (($gender == '2') ? '女' : '');

            $date_parts = explode('-', $birth_date);
            if (count($date_parts) === 3) {
                $birth_year = $date_parts[0];
                $birth_month = $date_parts[1];
                $birth_day = $date_parts[2];
            }
        }
    } catch (Exception $e) {

    }
}

// 写真パス決定
if ($student_id) {
    $photo_file = '/work/public/img/student_' . intval($student_id) . '.jpg';
    if (file_exists($photo_file)) {
        $photo_path = '../img/student_' . intval($student_id) . '.jpg';
    }
}

// 成績取得と集計
$existing_scores = [];
if ($student_id) {
    try {
        $pdo = getDatabaseConnection();

        // 未受験も含めて行を出すためLEFT JOIN、点数は0扱いで新しい順
        $sql = "SELECT 
                    t.id AS test_id,
                    t.test_date,
                    t.test_cd,
                    COUNT(sc.id) as score_count,
                    COALESCE(MAX(CASE WHEN s.id = 3 AND sc.student_id = ? THEN sc.score END), 0) as japanese,
                    COALESCE(MAX(CASE WHEN s.id = 2 AND sc.student_id = ? THEN sc.score END), 0) as math,
                    COALESCE(MAX(CASE WHEN s.id = 1 AND sc.student_id = ? THEN sc.score END), 0) as english,
                    COALESCE(MAX(CASE WHEN s.id = 4 AND sc.student_id = ? THEN sc.score END), 0) as science,
                    COALESCE(MAX(CASE WHEN s.id = 5 AND sc.student_id = ? THEN sc.score END), 0) as social
                FROM tests t
                LEFT JOIN scores sc ON t.id = sc.test_id AND sc.student_id = ?
                LEFT JOIN subjects s ON sc.subject_id = s.id
                GROUP BY t.id, t.test_date, t.test_cd
                ORDER BY t.test_date DESC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$student_id, $student_id, $student_id, $student_id, $student_id, $student_id]);
        $existing_scores = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($existing_scores as &$score) {
            $scores = array_filter(
                [
                    $score['japanese'] ?? 0,
                    $score['math'] ?? 0,
                    $score['english'] ?? 0,
                    $score['science'] ?? 0,
                    $score['social'] ?? 0
                ],
                function($v) { return $v !== null && $v !== ''; }
            );
            $score['total'] = array_sum($scores);
            $score['avg'] = count($scores) > 0 ? number_format($score['total'] / count($scores), 1) : '0.0';
            $score_count = (int)($score['score_count'] ?? 0);

            // score_count=0は未受験（削除後含む）
            if ($score_count === 0) {
                $score['test_type'] = '未受験';
            } else {
                if ((int)($score['test_cd'] ?? 0) === 1) {
                    $score['test_type'] = '期末試験';
                } elseif ((int)($score['test_cd'] ?? 0) === 2) {
                    $score['test_type'] = '中間試験';
                } else {
                    $score['test_type'] = '未受験';
                }
            }
        }
        unset($score);

    } catch (Exception $e) {
        $existing_scores = [];
    }
}

// 戻りURL作成
$return_params = [];
if (isset($_GET['q']) && $_GET['q'] !== '') {
    $return_params['q'] = $_GET['q'];
}
if (isset($_GET['page']) && ctype_digit((string)$_GET['page'])) {
    $return_params['page'] = $_GET['page'];
}
$return_url = buildUrl('/php/student_list.php', $return_params);
?>

<!-- HTML出力 -->
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <title>生徒管理システム - 生徒詳細</title>
  <link rel="stylesheet" href="../css/student_detail.css">
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
          <input type="hidden" name="student_id" value="<?php echo htmlspecialchars($student_id); ?>">
          
          
          
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
              <input type="text" id="last-name" name="last_name" value="<?php echo htmlspecialchars($last_name); ?>" placeholder="氏名(姓)" required>
              <label for="first-name">氏名(名)</label>
              <input type="text" id="first-name" name="first_name" value="<?php echo htmlspecialchars($first_name); ?>" placeholder="氏名(名)" required>
            </div>
            <div class="row">
              <label for="last-name-kana">氏名(せい)</label>
              <input type="text" id="last-name-kana" name="last_name_kana" value="<?php echo htmlspecialchars($last_name_kana); ?>" placeholder="氏名(せい)" required pattern="[ぁ-ん]+">
              <label for="first-name-kana">氏名(めい)</label>
              <input type="text" id="first-name-kana" name="first_name_kana" value="<?php echo htmlspecialchars($first_name_kana); ?>" placeholder="氏名(めい)" required pattern="[ぁ-ん]+">
            </div>
            <div class="row">
                <label for="class-select">クラス</label>
                <select id="class-select" name="class" required>
                <?php echo generateClassOptions($class); ?>
                </select>
                <label for="gender-select">性別</label>
                <select id="gender-select" name="gender" required>
                <?php echo generateGenderOptions($gender); ?>
                </select>
            </div>
            <div class="row">
              <label for="class-number">クラス番号</label>
              <select id="class-number" name="class_no" required>
                <?php echo generateSelectOptions(1, 31, intval($class_no), 'クラス番号'); ?>
              </select>
            </div>
            <div class="row">
                <label for="birth-year">生年月日</label>
                <select id="birth-year" name="birth_year" required>
                    <?php echo generateSelectOptions(1990, 2020, intval($birth_year), '年'); ?>
                </select>
                <span>年</span>
                <select id="birth-month" name="birth_month" required>
                    <?php echo generateSelectOptions(1, 12, intval($birth_month), '月'); ?>
                </select>
                <span>月</span>
                <select id="birth-day" name="birth_day" required>
                    <?php echo generateSelectOptions(1, 31, intval($birth_day), '日'); ?>
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
              <td><input type="date" class="score-date" value="<?php echo htmlspecialchars($score['test_date']); ?>" style="width:120px;" disabled></td>
              <td>
                <select class="score-type">
                  <option value="未受験" <?php echo $score['test_type'] === '未受験' ? 'selected' : ''; ?>>未受験</option>
                  <option value="期末試験" <?php echo $score['test_type'] === '期末試験' ? 'selected' : ''; ?>>期末試験</option>
                  <option value="中間試験" <?php echo $score['test_type'] === '中間試験' ? 'selected' : ''; ?>>中間試験</option>
                </select>
              </td>
              <td><input type="number" class="score-input" name="score_inputs[japanese]" form="score-save-form-<?php echo htmlspecialchars($score['test_id']); ?>" value="<?php echo htmlspecialchars($score['japanese'] ?? '0'); ?>" min="0" max="100" style="width:60px;"></td>
              <td><input type="number" class="score-input" name="score_inputs[math]" form="score-save-form-<?php echo htmlspecialchars($score['test_id']); ?>" value="<?php echo htmlspecialchars($score['math'] ?? '0'); ?>" min="0" max="100" style="width:60px;"></td>
              <td><input type="number" class="score-input" name="score_inputs[english]" form="score-save-form-<?php echo htmlspecialchars($score['test_id']); ?>" value="<?php echo htmlspecialchars($score['english'] ?? '0'); ?>" min="0" max="100" style="width:60px;"></td>
              <td><input type="number" class="score-input" name="score_inputs[science]" form="score-save-form-<?php echo htmlspecialchars($score['test_id']); ?>" value="<?php echo htmlspecialchars($score['science'] ?? '0'); ?>" min="0" max="100" style="width:60px;"></td>
              <td><input type="number" class="score-input" name="score_inputs[social]" form="score-save-form-<?php echo htmlspecialchars($score['test_id']); ?>" value="<?php echo htmlspecialchars($score['social'] ?? '0'); ?>" min="0" max="100" style="width:60px;"></td>
              <td><input type="text" class="score-avg" readonly style="width:60px;" value="<?php echo htmlspecialchars($score['avg']); ?>"></td>
              <td><input type="text" class="score-sum" readonly style="width:60px;" value="<?php echo htmlspecialchars($score['total']); ?>"></td>
              <td>
                <form method="POST" action="student.sousa.php" class="score-save-form" id="score-save-form-<?php echo htmlspecialchars($score['test_id']); ?>">
                  <input type="hidden" name="action" value="save_score">
                  <input type="hidden" name="student_id" class="score-student-id" value="<?php echo htmlspecialchars($student_id); ?>">
                  <input type="hidden" name="test_id" class="score-test-id" value="<?php echo htmlspecialchars($score['test_id']); ?>">
                  <input type="hidden" name="test_date" class="score-test-date" value="<?php echo htmlspecialchars($score['test_date']); ?>">
                  <input type="hidden" name="test_type" class="score-test-type" value="<?php echo htmlspecialchars($score['test_type']); ?>">
                  <button type="submit" class="score-save action">保存</button>
                </form>
              </td>
              <td>
                <form method="POST" action="student.sousa.php" class="score-delete-form">
                  <input type="hidden" name="action" value="delete_score">
                  <input type="hidden" name="student_id" class="score-delete-student-id" value="<?php echo htmlspecialchars($student_id); ?>">
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

    </section>
  </main>

  
  
  
  <!-- フッター -->
  <footer>
    <?php echo generateFooter(); ?>
  </footer>

  <?php echo generateLogoutForm(); ?>

  
  
  
  <!-- スクリプト読み込み -->
  <script src="../js/student_detail.js"></script>
</body>
</html>


