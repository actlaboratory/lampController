<?php

namespace Util;

use Model\Dao\User;
use Model\Dao\Session;

class SessionUtil{
    // セッション開始.
    static function setSession($db){
        $userTable = new User($db);
        $sessionTable = new Session($db);

        // 認証クッキーと有効なセッションがあれば自動ログイン
        // セッションIDがあればCOOKIE修復
        $authCookie = self::getCookie(\AUTH_COOKIE_NAME);
        if (!empty($_SESSION["id"]) && $sessionTable->select([
            "session_id"=> $_SESSION["id"]
        ])){
            self::setCookie(\AUTH_COOKIE_NAME, $_SESSION["id"]);
            $authData = $_SESSION["id"];
        } elseif (empty($authCookie)){
            return FALSE;
        } else{
            $authData = $authCookie[0];
        }
        $sessionData = $sessionTable->select([
            "session_id"=> $authData
        ]);
        if (empty($sessionData)){
            return FALSE;
        }
        $userData = $userTable->select([
            "id"=> $sessionData["user_id"]
        ]);
        if (empty($userData)){
            return FALSE;
        }
        $_SESSION["id"] = $sessionData["session_id"];
        $_SESSION["userId"] = $userData["id"];

        
        // クッキーとセッションの延長
        self::setCookie(\AUTH_COOKIE_NAME, $sessionData["session_id"]);
        $sessionTable->update([
            "id"=> $sessionData["id"],
            "last_logdin_at"=> time()
        ]);

        // 設定の適用
        self::setConfigFromCookie();
        return TRUE;
    }

    // 新規セッション
    static function newSession($db, $userId){
        $userTable = new User($db);
        if (empty($userTable->select([
            "id"=> $userId
        ]))){
            return FALSE;
        }
        $sessionId = hash('sha256', random_int(PHP_INT_MIN, PHP_INT_MAX));
        $sessionTable = new Session($db);
        $sessionTable->insert([
            "session_id"=> $sessionId,
            "user_id"=> $userId,
            "type"=> \SESSION_TYPE_BROWSER,
            "last_logdin_at"=> time()
        ]);
        $_SESSION["id"] = $sessionId;
        $_SESSION["userId"] = $userId;
        return TRUE;
    }
    
    // セッション破棄
    static function unsetSession($db){
        self::unsetCookie(\AUTH_COOKIE_NAME);
        if (empty($_SESSION["id"])){
            return FALSE;
        }
        $sessionTable = new Session($db);
        $sessionTable->delete([
            "session_id"=> $_SESSION["id"]
        ]);
        $_SESSION = [];
        return TRUE;
    }
    
    static function setConfigFromCookie(){
        
        $cookie = self::getCookie(\CONFIG_COOKIE_NAME);
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
        if (is_string($value)){
            $cookie = $value;
        } else{
            foreach($value as $v){
                if ($count===0){
                    $cookie .= $v;
                    $count += 1;
                } else{
                    $cookie .= ("@". $v);
                }
            }
        }
        setcookie($name, base64_encode($cookie),[
            "path"=> "/",
            "expires"=> time() + (60 * 60 * 24 * 400),
            "domain"=> preg_replace("/:[0-9]+$/", $_SERVER["HTTP_HOST"], ""),
            "samesite"=> "strict"]
        );
    }

    /// クッキー削除
    static function unsetCookie($name){
        setcookie($name, "", [
            "expires"=> time() - 3600,
            "path"=> "/",
            "domain"=> preg_replace("/:[0-9]+$/", $_SERVER["HTTP_HOST"], ""),
            "samesite"=> "strict"
        ]);
    }
}
