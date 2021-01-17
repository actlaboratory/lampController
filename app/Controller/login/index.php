<?php

use Slim\Http\Request;
use Slim\Http\Response;
use Model\Dao\User;
use Util\SessionUtil;

$app->get("/login", function (request $request, response $response){
    // とりあえずセッション没収
    SessionUtil::unsetSession($this->db);

    // ログインフォームを表示
    return showLoginForm($this->view, $response);
});

$app->post("/login", function (request $request, response $response){
    $inputData = $request->getParsedBody();
    
    // ログインを試行
    // エラーがあれば再入力、なけばログイン
    if (!loginFromInput($inputData["userName"], $inputData["password"], $this->db)){
        return showLoginForm($this->view, $response, $inputData, "ユーザー名、またはパスワードが謝っています。");
    }
    
    // ログインが完了したらリダイレクト
    return $response->withRedirect($request->getUri()->getBasePath()."/");
});

// ログインフォームを表示
// 再表示時は、前回入力データ、エラーメッセージを元にフォームを構成
function showLoginForm($view, $response, $previousData="", $errorMessage=""){
    if (empty($previousData)){
        $data = [];
    } else{
        $data = $previousData;
    }
    
    $data["errorMessage"] = $errorMessage;

    // Render view
    return $view->render($response, 'login/index.twig', $data);
}

// ログイン
function loginFromInput($userName, $password, $db){
    // ユーザー情報確認
    $userTable = new User($db);
    $userData = $userTable->select([
        "user_name"=> $userName
    ]);
    if (empty($userData)){
        return FALSE;
    }
    if (!password_verify($password, $userData["password_hash"])){
        return FALSE;
    }
    // ブラウザ長期セッションの開始
    SessionUtil::newSession($db, $userData["id"]);

    // クッキーから設定を読み込み
    SessionUtil::setConfigFromCookie();
    return TRUE;
}

