<<<<<<< HEAD
<?php
=======
﻿<?php
>>>>>>> bfb5fd8 (fix_2_生徒管理システム)
/*
 * 役割：共通関数（DB/認証/UI）
 * 1) DB接続
 * 2) 認証/セッション
 * 3) ログアウト
 * 4) リダイレクト
 * 5) 文字列エスケープ
 * 6) UI生成
 * 7) URL生成
 * 8) ページング補助
 */

session_start();

// DB接続
function getDatabaseConnection() {
    static $pdo = null;

    if ($pdo === null) {
        try {
            $host = 'db';
            $db   = 'scoremanager';
            $user = 'smuser';
            $pass = 'smpass';
            $charset = 'utf8mb4';

            $dsn = "mysql:host=$host;dbname=$db;charset=$charset";

            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];

            $pdo = new PDO($dsn, $user, $pass, $options);

            $pdo->query('SELECT 1');

        } catch (Exception $e) {
            throw new Exception('データベース接続に失敗しました');
        }
    }

    return $pdo;
}

// 認証/セッション
function requireAuth() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (!isset($_SESSION['user_id'])) {
        redirectTo('/login.php');
    }
}

// ログアウト
function logout() {
    session_destroy();
    header('Location: /login.php');
    exit;
}

function checkLogoutRequest() {
    if (isset($_POST['logout'])) {
        logout();
    }
}

// リダイレクト
function redirectTo($url) {
    header("Location: $url");
    exit;
}

// 文字列エスケープ
function h($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

// UI生成
function generateSelectOptions($start, $end, $selected = '', $placeholder = '') {
    $html = '';
    if ($placeholder) {
        $html .= "<option value=\"\">$placeholder</option>";
    }
    for ($i = $start; $i <= $end; $i++) {
        $selectedAttr = ($selected == $i) ? ' selected' : '';
        $html .= "<option value=\"$i\"$selectedAttr>$i</option>";
    }
    return $html;
}

function generateClassOptions($selected = '') {
    $classes = ['A', 'B', 'C', 'D', 'E', 'F'];
    $html = '<option value="">クラス</option>';
    foreach ($classes as $class) {
        $selectedAttr = ($selected === $class) ? ' selected' : '';
        $html .= "<option value=\"$class\"$selectedAttr>$class</option>";
    }
    return $html;
}

function generateGenderOptions($selected = '') {
    $genders = [
        '1' => '男',
        '2' => '女'
    ];
    $html = '<option value="">性別</option>';
    foreach ($genders as $value => $label) {
        $selectedAttr = ($selected == $value) ? ' selected' : '';
        $html .= "<option value=\"$value\"$selectedAttr>$label</option>";
    }
    return $html;
}

function generateFooter() {
    return '<span class="copyright">Copyright &copy; Vuetech corp . All Right Reserved</span>';
}

function generateHeader($title = '生徒管理システム', $subtitle = '－Score Manager－') {
    return '
    <div class="logo-area">
      <img src="../img/生徒管理ロゴ.png" alt="ロゴ" class="logo">
      <span class="title">' . h($title) . '</span>
      <span class="subtitle">' . h($subtitle) . '</span>
    </div>
    <button class="logout-button" id="logout-btn" style="display:none;">ログアウト</button>
    <img src="../img/ログアウト透過.png" alt="ログアウト" class="logout-logo" id="logout-logo" tabindex="0">';
}

function generateLogoutForm() {
    return '
    <form id="logout-form" method="POST" style="display:none;">
      <input type="hidden" name="logout" value="1">
    </form>';
}

// URL生成
function buildUrl($path, $params = []) {
    if (!$params) {
        return $path;
    }
    return $path . '?' . http_build_query($params);
}

// ページング補助
function getCircledNumber($number) {
    $circled = ['①','②','③','④','⑤','⑥','⑦','⑧','⑨','⑩','⑪','⑫','⑬','⑭','⑮','⑯','⑰','⑱','⑲','⑳'];
    if ($number >= 1 && $number <= 20) {
        return $circled[$number - 1];
    }
    return (string)$number;
}

function buildPaginationButtons($current_page, $total_pages) {
    if ($total_pages <= 1) {
        return [];
    }
    $page_numbers = [];
    if ($total_pages <= 5) {
        $page_numbers = range(1, $total_pages);
    } else {
        if ($current_page > 2) {
            $page_numbers[] = '...';
        }
        $start_page = max(1, $current_page - 1);
        $end_page = min($total_pages, $current_page + 1);
        for ($i = $start_page; $i <= $end_page; $i++) {
            $page_numbers[] = $i;
        }
        if ($current_page < $total_pages - 1) {
            $page_numbers[] = '...';
        }
    }

    $buttons = [];
    $buttons[] = [
        'label' => '最初へ',
        'attrs' => 'type="submit" name="page" value="1"' . ($current_page === 1 ? ' disabled' : '')
    ];

    foreach ($page_numbers as $page_item) {
        if (is_int($page_item)) {
            $attrs = 'type="submit" name="page" value="' . $page_item . '"';
            if ($page_item === $current_page) {
                $attrs .= ' class="active-page"';
            }
            $buttons[] = [
                'label' => getCircledNumber($page_item),
                'attrs' => $attrs
            ];
        } else {
            $buttons[] = [
                'label' => $page_item,
                'attrs' => 'type="button" disabled'
            ];
        }
    }

    $buttons[] = [
        'label' => '最後へ',
        'attrs' => 'type="submit" name="page" value="' . $total_pages . '"' . ($current_page === $total_pages ? ' disabled' : '')
    ];

    return $buttons;
}
?>


