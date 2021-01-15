<?php

namespace Util;

use Dao\User;

class SessionUtil{
    const AUTH_COOKIE_NAME = "LAMP_Controller_auth";
    const CONFIG_COOKIE_NAME = "LAMP_Controller_config";
    
    // セッション開始
    static function setSession($db){
        session_start();

        // 認証クッキーがあれば自動ログイン
        // ログインできなければ、クッキーは没収
        $authCookie = self::getCookie(self::AUTH_COOKIE_NAME);
        if (empty($authCookie[0]) || empty($authCookie[1])){
            self::unsetCookie(self::AUTH_COOKIE_NAME);
            return FALSE;
        }
        $userTable = new User($db);
        $userData = $userTable->select([
            "user_name"=> $authCookie[0],
            "software_key"=> $authCookie[1]
        ]);
        if (empty($userData)){
            self::unsetCookie(self::AUTH_COOKIE_NAME);
            return FALSE;
        }
        $_SESSION["id"] = $userData["id"];
        
        // 設定の適用
        self::setConfigFromCookie();
        return TRUE;
    }

    static function setConfigFromCookie(){
        $cookie = self::getCookie(self::AUTH_COOKIE_NAME);
        if (empty($cookie)){
            return FALSE;
        }
        $_SESSION["defaultLamp"] = $cookie[0];
    }

    // クッキーバリューを配列で取得
    static function getCookie($name){
        if (empty($_COOKIE[$name])){
            return FALSE;
        }
        return explode("@", base64_decode($_COOKIE[$name]));
    }

    // クッキーをセット
    static function setCookie($name, $value){
        $cookie = "";
        $count = 0;
        foreach($value as $v){
            if (count===0){
                $cookie .= $v;
            } else{
                $cookie .= ("@". $v);
            }
        }
        setcookie($name, base64_encode($cookie), time() + (60 * 60 * 24 * 400), "/", $_SERVER["HTTP_HOST"]);
    }

    /// クッキー削除
    static function unsetCookie($name){
        setcookie($name, "", time() - 10000, "/", $_SERVER["HTTP_HOST"]);
    }
}
