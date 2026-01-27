<?php
/*
 * 役割：生徒/成績の登録・更新・削除
 * 1) 共通読み込み/ログイン確認
 * 2) アクション取得
 * 3) 共通関数
 * 4) 入力バリデーション
 * 5) 写真保存
 * 6) 生徒の登録/更新/削除
 * 7) 成績の保存/削除
 * 8) 例外時のリダイレクト
 */

// 共通読み込み/ログイン確認
require_once '/work/app/config.php';
require_once '/work/app/core.php';

if (!Utils::isLoggedIn()) {
    header('Location: /login.php');
    exit;
}

// アクション取得
$action = $_POST['action'] ?? '';

// 共通関数
function redirectWithError($action, $student_id = '', $error = 'validation') {
    if (in_array($action, ['update_student', 'save_score', 'delete_score'], true) && $student_id !== '') {
        header("Location: student_detail.php?id=" . $student_id . "&error=" . $error);
        exit;
    }
    if ($action === 'register_student') {
        header("Location: student_list.php?error=" . $error . "&tab=register");
        exit;
    }
    header("Location: student_list.php?error=" . $error);
    exit;
}

function buildBirthDate($year, $month, $day) {
    $y = intval($year);
    $m = intval($month);
    $d = intval($day);
    if ($y <= 0 || $m <= 0 || $d <= 0) {
        return '';
    }
    if (!checkdate($m, $d, $y)) {
        return '';
    }
    return sprintf('%04d-%02d-%02d', $y, $m, $d);
}

// 入力バリデーション
function validateStudentFields($data, &$birth_date) {
    $required = ['last_name', 'first_name', 'last_name_kana', 'first_name_kana', 'class', 'class_no', 'gender', 'birth_year', 'birth_month', 'birth_day'];
    foreach ($required as $key) {
        if (!isset($data[$key]) || trim((string)$data[$key]) === '') {
            return false;
        }
    }
    $allowed_classes = ['A', 'B', 'C', 'D', 'E', 'F'];
    if (!in_array($data['class'], $allowed_classes, true)) {
        return false;
    }
    if (!ctype_digit((string)$data['class_no'])) {
        return false;
    }
    $class_no = (int)$data['class_no'];
    if ($class_no < 1 || $class_no > 31) {
        return false;
    }
    if (!in_array((string)$data['gender'], ['1', '2'], true)) {
        return false;
    }
    $birth_date = buildBirthDate($data['birth_year'], $data['birth_month'], $data['birth_day']);
    if ($birth_date === '') {
        return false;
    }
    return true;
}

function validateScores($scores, &$normalized_scores) {
    if (!is_array($scores)) {
        return false;
    }
    $allowed = ['english', 'math', 'japanese', 'science', 'social'];
    $normalized = [];
    foreach ($scores as $subject => $score) {
        if (!in_array($subject, $allowed, true)) {
            return false;
        }
        if ($score === '' || $score === null) {
            $normalized[$subject] = 0;
            continue;
        }
        if (!ctype_digit((string)$score)) {
            return false;
        }
        $value = (int)$score;
        if ($value < 0 || $value > 100) {
            return false;
        }
        $normalized[$subject] = $value;
    }
    $normalized_scores = $normalized;
    return true;
}

function validateTestDate($date) {
    if (!is_string($date) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        return false;
    }
    [$y, $m, $d] = explode('-', $date);
    return checkdate((int)$m, (int)$d, (int)$y);
}

// 写真保存
function saveStudentPhoto($student_id) {
    if (!isset($_FILES['photo']) || $_FILES['photo']['error'] === UPLOAD_ERR_NO_FILE) {
        return true;
    }
    if ($_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
        return false;
    }

    // 3MB上限
    if ($_FILES['photo']['size'] > 3 * 1024 * 1024) {
        return false;
    }
    $tmp = $_FILES['photo']['tmp_name'];
    $info = @getimagesize($tmp);

    // JPEGのみ許可
    if ($info === false || ($info['mime'] !== 'image/jpeg')) {
        return false;
    }
    $dir = '/work/public/img';
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }
    $dest = $dir . '/student_' . intval($student_id) . '.jpg';
    return move_uploaded_file($tmp, $dest);
}

try {
    $pdo = getDatabaseConnection();

    // 生徒の登録/更新/削除
    switch ($action) {
        case 'register_student':
            // 生徒登録

            $last_name = $_POST['last_name'] ?? '';
            $first_name = $_POST['first_name'] ?? '';
            $last_name_kana = $_POST['last_name_kana'] ?? '';
            $first_name_kana = $_POST['first_name_kana'] ?? '';
            $class = $_POST['class'] ?? '';
            $class_no = $_POST['class_no'] ?? '';
            $gender = $_POST['gender'] ?? '';
            $birth_year = $_POST['birth_year'] ?? '';
            $birth_month = $_POST['birth_month'] ?? '';
            $birth_day = $_POST['birth_day'] ?? '';

            $birth_date = '';
            if (!validateStudentFields($_POST, $birth_date)) {
                redirectWithError($action, '', 'validation');
            }

            $pdo->beginTransaction();
            $stmt = $pdo->prepare("INSERT INTO students (last_name, first_name, last_name_kana, first_name_kana, class, class_no, gender, birth_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$last_name, $first_name, $last_name_kana, $first_name_kana, $class, $class_no, $gender, $birth_date]);
            $new_id = $pdo->lastInsertId();
            if (!saveStudentPhoto($new_id)) {
                $pdo->rollBack();
                redirectWithError($action, '', 'validation');
            }
            $pdo->commit();

            // PRG
            header("Location: complete.php");
            exit;

        case 'update_student':
            // 生徒更新

            $student_id = $_POST['student_id'] ?? '';
            if ($student_id === '' || !ctype_digit((string)$student_id)) {
                redirectWithError($action, '', 'validation');
            }
            $last_name = $_POST['last_name'] ?? '';
            $first_name = $_POST['first_name'] ?? '';
            $last_name_kana = $_POST['last_name_kana'] ?? '';
            $first_name_kana = $_POST['first_name_kana'] ?? '';
            $class = $_POST['class'] ?? '';
            $class_no = $_POST['class_no'] ?? '';
            $gender = $_POST['gender'] ?? '';
            $birth_year = $_POST['birth_year'] ?? '';
            $birth_month = $_POST['birth_month'] ?? '';
            $birth_day = $_POST['birth_day'] ?? '';

            $birth_date = '';
            if (!validateStudentFields($_POST, $birth_date)) {
                redirectWithError($action, $student_id, 'validation');
            }

            $stmt = $pdo->prepare("UPDATE students SET last_name = ?, first_name = ?, last_name_kana = ?, first_name_kana = ?, class = ?, class_no = ?, gender = ?, birth_date = ? WHERE id = ?");
            $stmt->execute([$last_name, $first_name, $last_name_kana, $first_name_kana, $class, $class_no, $gender, $birth_date, $student_id]);

            if (!saveStudentPhoto($student_id)) {
                redirectWithError($action, $student_id, 'validation');
            }

            // PRG
            header("Location: student_detail.php?id=" . $student_id . "&updated=1");
            exit;

        case 'delete_student':
            // 生徒削除

            $student_id = $_POST['student_id'] ?? '';
            if ($student_id === '' || !ctype_digit((string)$student_id)) {
                redirectWithError($action, '', 'validation');
            }

            $stmt = $pdo->prepare("DELETE FROM scores WHERE student_id = ?");
            $stmt->execute([$student_id]);

            $stmt = $pdo->prepare("DELETE FROM students WHERE id = ?");
            $stmt->execute([$student_id]);
            $photo_file = '/work/public/img/student_' . intval($student_id) . '.jpg';
            if (file_exists($photo_file)) {
                @unlink($photo_file);
            }

            header("Location: student_list.php");
            exit;

        // 成績の保存/削除
        case 'save_score':
            // 成績保存

            $student_id = $_POST['student_id'] ?? '';
            $test_id = $_POST['test_id'] ?? '';
            $test_date = $_POST['test_date'] ?? '';
            $test_type = $_POST['test_type'] ?? '';
            $scores = $_POST['score_inputs'] ?? null;

            // 入力チェック（ID/日付/種別/点数）
            if ($student_id === '' || !ctype_digit((string)$student_id)) {
                redirectWithError($action, '', 'validation');
            }

            if (!$test_id) {
                header("Location: student_detail.php?id=" . $student_id . "&error=test_id_required");
                exit;
            }
            if (!ctype_digit((string)$test_id)) {
                redirectWithError($action, $student_id, 'validation');
            }
            if (!validateTestDate($test_date)) {
                redirectWithError($action, $student_id, 'validation');
            }
            $allowed_test_types = ['未受験', '期末試験', '中間試験'];
            if (!in_array($test_type, $allowed_test_types, true)) {
                redirectWithError($action, $student_id, 'validation');
            }

            $normalized_scores = [];
            if (!validateScores($scores, $normalized_scores)) {
                redirectWithError($action, $student_id, 'validation');
            }

            $stmt = $pdo->prepare("DELETE FROM scores WHERE student_id = ? AND test_id = ?");
            $stmt->execute([$student_id, $test_id]);

            $subjects = [
                'english' => 1,
                'math' => 2,
                'japanese' => 3,
                'science' => 4,
                'social' => 5
            ];
            foreach ($normalized_scores as $subject => $score) {
                if (isset($subjects[$subject])) {
                    $score_value = $score;
                    $stmt = $pdo->prepare("INSERT INTO scores (student_id, test_id, subject_id, score) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$student_id, $test_id, $subjects[$subject], $score_value]);
                }
            }

            // PRG
            header("Location: student_detail.php?id=" . $student_id . "&saved=1");
            exit;

        case 'delete_score':
            // 成績削除

            $student_id = $_POST['student_id'] ?? '';
            $test_id = $_POST['test_id'] ?? '';

            // 入力チェック（ID）
            if ($student_id === '' || !ctype_digit((string)$student_id)) {
                redirectWithError($action, '', 'validation');
            }
            if ($test_id === '' || !ctype_digit((string)$test_id)) {
                redirectWithError($action, $student_id, 'validation');
            }

            $stmt = $pdo->prepare("DELETE FROM scores WHERE student_id = ? AND test_id = ?");
            $stmt->execute([$student_id, $test_id]);

            // PRG
            header("Location: student_detail.php?id=" . $student_id . "&deleted=1");
            exit;

        default:
            header("Location: student_list.php");
            exit;
    }

// 例外時のリダイレクト
} catch (Exception $e) {
    $student_id = $_POST['student_id'] ?? '';
    redirectWithError($action, $student_id, 'system');
}
?>


