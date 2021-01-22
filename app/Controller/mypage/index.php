<?php

use Slim\Http\Request;
use Slim\Http\Response;
use Model\Dao\User;
use Model\Dao\Software;
use Util\SessionUtil;

$app->get("/mypage", function (request $request, response $response){
    // マイページ表示
    return showMypage($this->view, $this->db, $response);
});

$app->post("/mypage", function (request $request, response $response){
    $input = $request->getParsedBody();
    $message = "";
    
    // 設定を反映
    if (!empty($input["defaultLamp"])){
        SessionUtil::setCookie(CONFIG_COOKIE_NAME, $input["defaultLamp"]);
        $_SESSION["defaultLamp"] = $input["defaultLamp;"];
        $message = "捜査対象のLAMPを設定しました。";
    }
    
    // マイページ再表示
    return showMypage($this->view, $this->db, $response, $message);
});

// マイページを表示
function showMypage($view, $db, $response, $message=""){
    $data = ["message"=> $message];
    
    // ユーザー情報を提示
    $userTable = new User($db);
    $userData = $userTable->select([
        "id"=> $_SESSION["userId"]
    ]);
    $data["userDisplayName"] = $userData["display_name"];

    // LAMP一覧を読み込み
    $softwareTable = new Software($db);
    $softwareData = $softwareTable->select([
        "user_id"=> $_SESSION["userId"]
    ], "display_name", "ASC", "", TRUE);
    $data["softwareList"] = $softwareData;

    // Render view
    return $view->render($response, 'mypage/index.twig', $data);
}

