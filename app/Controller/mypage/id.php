<?php

use Slim\Http\Request;
use Slim\Http\Response;
use Model\Dao\User;
use Model\Dao\Software;
use Model\Dao\Receive;
use Model\Dao\Send_queue;
use Model\Dao\Session;
use Util\SessionUtil;

$app->post("/mypage/id", function (request $request, response $response){
    $input = $request->getParsedBody();
    $message = "";

    // データベース
    $userTable = new User($this->db);
    $softwareTable = new Software($this->db);
    $receiveTable = new Receive($this->db);
    $send_queueTable = new Send_queue($this->db);
    
    // 単純表示
    if (!empty($input["password"])){
        $userData = $userTable->select([
            "id"=> $_SESSION["userId"]
        ]);
        if (!empty($userData) && password_verify($input["password"], $userData["password_hash"])){
            return showIdConfigView($this->view, $this->db, $response, "このページで設定を行った場合、ブラウザの戻る機能は正常に動作しません。");
        } else{
            return showIdConfigMessageView($this->view, $response, "パスワードが謝っています。\nブラウザで前のページに戻り、もう一度やり直してください。");
        }

    // アカウント削除
    } elseif (!empty($input["userName"]) && !empty($input["deleteConfirmPassword"]) && !empty($input["deleteConfirm"])){
        $userData = $userTable->select([
            "id"=> $_SESSION["userId"],
            "user_name"=> $input["userName"]
        ]);
        if (!empty($userData) && password_verify($input["deleteConfirmPassword"], $userData["password_hash"]) && $input["deleteConfirm"]==="yes"){
            set_time_limit(EXTEND_EXECUTE_TIME_LIMIT);
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
