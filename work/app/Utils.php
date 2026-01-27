<?php
/*
ユーティリティクラス

1. プロパティ
   1.1 $_errors（エラー配列）
   1.2 $_values（値の保持配列）
2. ログイン判定
   2.1 isLoggedInメソッド（static）
3. エラー管理
   3.1 setErrorsメソッド
   3.2 getErrorsメソッド
   3.3 hasErrorメソッド
4. 値の保持
   4.1 setValuesメソッド
   4.2 getValuesメソッド
5. HTMLエスケープ
   5.1 hメソッド（static）
*/

class Utils
{
    protected $_errors = [];
    protected $_values = [];

    /**
     * ログイン判定
     */
    public static function isLoggedIn()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        return isset($_SESSION['user_id']);
    }

    /**
     * エラーを設定
     */
    public function setErrors($key, $error)
    {
        $this->_errors[$key] = $error;
    }

    /**
     * エラーを取得
     */
    public function getErrors($key = null)
    {
        if ($key === null) {
            return $this->_errors;
        }
        return $this->_errors[$key] ?? '';
    }

    /**
     * エラーがあるかチェック
     */
    public function hasError()
    {
        return !empty($this->_errors);
    }

    /**
     * 値を設定
     */
    public function setValues($key, $value)
    {
        $this->_values[$key] = $value;
    }

    /**
     * 値を取得
     */
    public function getValues($key = null)
    {
        if ($key === null) {
            return $this->_values;
        }
        return $this->_values[$key] ?? '';
    }

    /**
     * HTMLエスケープ
     */
    public static function h($str)
    {
        return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
    }
}
