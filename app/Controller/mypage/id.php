<?php

use Slim\Http\Request;
use Slim\Http\Response;
use Model\Dao\User;
use Model\Dao\Software;
use Model\Dao\Receive;
use Model\Dao\Send_queue;
use Model\Dao\Session;
use Util\SessionUtil;
use Util\ValidationUtil;

$app->post("/mypage/id", function (request $request, response $response){
    $input = $request->getParsedBody();
    $message = "";

    // データベース
    $userTable = new User($this->db);
    $sessionTable = new Session($this->db);
    $softwareTable = new Software($this->db);
    $receiveTable = new Receive($this->db);
    $send_queueTable = new Send_queue($this->db);
    
    // ユーザ認証
    $userData = $userTable->select([
        "id"=> $_SESSION["userId"]
    ]);
    if (empty($userData)){
        return showIdConfigMessageView($this->view, $response, "エラーが発生しました。\nブラウザで前のページに戻り、もう一度やり直してください。");
    }
    
    // 単純表示
    if (!empty($input["password"])){
        if (password_verify($input["password"], $userData["password_hash"])){
            $_SESSION["idConfigTime"] = time();
            return showIdConfigView($this->view, $this->db, $response, "このページで設定を行った場合、ブラウザの戻る機能は正常に動作しません。");
        } else{
            return showIdConfigMessageView($this->view, $response, "パスワードが謝っています。\nブラウザで前のページに戻り、もう一度やり直してください。");
        }
    }

    // 5分経過でタイムアウト処理
    if (!empty($_SESSION["idConfigTime"]) && (time() - $_SESSION["idConfigTime"]) <= 300){
        $_SESSION["idConfigTime"] = time(); //時間延長
    } else{
        return showIdConfigMessageView($this->view, $response, "タイムアウトしました。アカウント設定を継続するには、再度、マイページでのパスワード入力が必要です。");
    }

    // 表示名変更
    if (!empty($input["newDisplayName"])){
        $message = ValidationUtil::checkString("userDisplayName", $input["newDisplayName"]);
        if (!empty($message)){
            return showIdConfigView($this->view, $this->db, $response, $message);
        }
        $userTable->update([
            "id"=> $_SESSION["userId"],
            "display_name"=> $input["newDisplayName"]
        ]);
        return showIdConfigView($this->view, $this->db, $response, "表示名を変更しました。");

    // パスワード変更
    } elseif (!empty($input["newPassword"]) && !empty($input["confirmNewPassword"])){
        if ($input["newPassword"]!==$input["confirmNewPassword"]){
            return showIdConfigView($this->view, $this->db, $response, "新しいパスワードと、パスワードの確認が一致しません。");
        }
        $message = ValidationUtil::checkString("userPassword", $input["newPassword"]);
        if (!empty($message)){
            return showIdConfigView($this->view, $this->db, $response, $message);
        }
        $softwareTable->delete(["user_id"=> $_SESSION["userId"]]);
        $sessionTable->delete(["user_id"=> $_SESSION["userId"]]);
        $receiveTable->delete(["user_id"=> $_SESSION["userId"]]);
        $send_queueTable->delete(["user_id"=> $_SESSION["userId"]]);
        $softwareKey = hash('sha256', random_int(PHP_INT_MIN, PHP_INT_MAX));
        $userTable->update([
            "id"=> $_SESSION["userId"],
            "password_hash"=> password_hash($input["newPassword"], PASSWORD_DEFAULT),
            "software_key"=> $softwareKey
        ]);
        return showIdConfigMessageView($this->view, $response, "パスワードを変更しました。すべてのセッションは切断され、このブラウザもログアウトされました。\nまた、ソフトウェアキーは、以下の値に変更されました。これまでのキーは利用できませんので、再度、ご利用になるLAMPを登録してください。\n\n 新しいソフトウェアキー: ".$softwareKey.
    "\n\n※ソフトウェアキーは、LAMPがコントローラと通信する場合や、パスワードを忘れた場合などに利用しますので、他人に知られないように十分ご注意ください。また、LAMPがコントローラに登録されたときに自動でダウンロードされます。");

    // アカウント削除
    } elseif (!empty($input["userName"]) && !empty($input["deleteConfirmPassword"]) && !empty($input["deleteConfirm"])){
        if (password_verify($input["deleteConfirmPassword"], $userData["password_hash"]) && $input["deleteConfirm"]==="yes"){
            set_time_limit(EXTEND_EXECUTE_TIME_LIMIT * 5);
            $userTable->delete(["id"=> $_SESSION["userId"], "user_name"=> $input["userName"]]);
            return showIdConfigMessageView($this->view, $response, "アカウントの削除が完了しました。");
        } else{
            return showIdConfigView($this->view, $this->db, $response, "アカウントを削除できません。入力情報を確認してください。");
        }
    }
    return showIdConfigMessageView($this->view, $response, "エラーが発生しました。前のページに戻り、もう一度やり直してください。");
});

// アカウント設定を表示
function showIdConfigView($view, $db, $response, $message=""){
    $data = ["message"=> $message];
    
    // ユーザー情報を取得
    $userTable = new User($db);
    $userData = $userTable->select([
        "id"=> $_SESSION["userId"]
    ]);
    $data["userDisplayName"] = $userData["display_name"];
    $data["softwareKey"] = $userData["software_key"];

    // Render view
    return $view->render($response, 'mypage/id.twig', $data);
}

// メッセージビュー
function showIdConfigMessageView($view, $response, $message=""){
    $data = ["message"=> $message];
    
    // Render view
    return $view->render($response, 'mypage/message.twig', $data);
}
