<?php
/*
Userクラス

1. コンストラクタ
2. フォーム処理
   2.1 processPostメソッド
3. バリデーション
   3.1 _validateメソッド（private）
4. ログイン処理
   4.1 loginメソッド
5. ユーザー情報取得
   5.1 meメソッド
*/

class User extends Utils
{
    private $pdo;

    /**
     * コンストラクタ
     */
    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * フォーム処理
     */
    public function processPost()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return;
        }

        Token::validate();

        $action = filter_input(INPUT_POST, 'action');
        if ($action === 'login') {
            $this->login();
        }
    }

    /**
     * バリデーション
     */
    private function _validate()
    {
        $email = trim(filter_input(INPUT_POST, 'email') ?? '');
        $password = filter_input(INPUT_POST, 'password') ?? '';

        // 値を保持
        $this->setValues('email', $email);

        // メールアドレスとパスワードの両方が空欄チェック
        if (empty($email) && empty($password)) {
            $this->setErrors('email', 'メールアドレスおよびパスワードを入力してください');
            return false;
        }
        // メールアドレスの空欄チェック
        if (empty($email)) {
            $this->setErrors('email', 'メールアドレスを入力してください');
            return false;
        }
        // パスワードの空欄チェック
        if (empty($password)) {
            $this->setErrors('password', 'パスワードを入力してください');
            return false;
        }
        // メールアドレス形式チェック
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->setErrors('email', 'メールアドレス形式で入力してください');
            return false;
        }
        // パスワードの英数字チェック
        if (!preg_match('/^[a-zA-Z0-9]+$/', $password)) {
            $this->setErrors('password', 'パスワードは英数字のみです');
            return false;
        }

        return true;
    }

    /**
     * ログイン処理
     */
    public function login()
    {
        if (!$this->_validate()) {
            return false;
        }

        $email = trim(filter_input(INPUT_POST, 'email'));
        $password = filter_input(INPUT_POST, 'password');

        try {
            $stmt = $this->pdo->prepare("SELECT id, email, password_hash FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password_hash'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['email'] = $user['email'];
<<<<<<< HEAD
                header("Location: /student_list.php");
=======
                header("Location: /php/student_list.php");
>>>>>>> bfb5fd8 (fix_2_生徒管理システム)
                exit;
            } else {
                $this->setErrors('email', 'メールアドレスまたはパスワードが正しくありません。');
                return false;
            }
        } catch (PDOException $e) {
            $this->setErrors('email', 'システムエラーが発生しました');
            return false;
        }
    }

    /**
     * ログインユーザー情報取得
     */
    public function me()
    {
        if (!isset($_SESSION['user_id'])) {
            return null;
        }

        try {
            $stmt = $this->pdo->prepare("SELECT id, email FROM users WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            return null;
        }
    }
}

