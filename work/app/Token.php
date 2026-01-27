<?php
/*
CSRF対策用Tokenクラス

1. トークン生成
   1.1 createメソッド（static）
2. トークン検証
   2.1 validateメソッド（static）
*/

class Token
{
    /**
     * トークンを生成してセッションに保存
     */
    public static function create()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['token'])) {
            $_SESSION['token'] = bin2hex(random_bytes(32));
        }
    }

    /**
     * トークンを検証
     */
    public static function validate()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (
            empty($_SESSION['token']) ||
            $_SESSION['token'] !== filter_input(INPUT_POST, 'token')
        ) {
<<<<<<< HEAD
            header('Location: /login.php');
            exit;
=======
            exit('Invalid post request');
>>>>>>> bfb5fd8 (fix_2_生徒管理システム)
        }
    }
}
