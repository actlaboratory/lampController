<?php

use Slim\Http\Request;
use Slim\Http\Response;
use Util\SessionUtil;
use Model\Dao\User;
use Model\Dao\Software;

$app->get("/ctrl/file", function (request $request, response $response){
    return showFileView($request, $response, $this->view, $this->db);
});

$app->post("/ctrl/file", function (request $request, response $response){
    $input = $request->getParsedBody();
        
    // 捜査対象LAMP変更
    if (!empty($input["defaultLamp"]) && empty($_SESSION["guestId"])){
        SessionUtil::setCookie(CONFIG_COOKIE_NAME, $input["defaultLamp"]);
        $_SESSION["defaultLamp"] = $input["defaultLamp"];
    }
    return showFileView($request, $response, $this->view, $this->db);
});

// ファイラ表示
function showFileView($request, $response, $view, $db){
    if (!empty($request->getQueryParams()["f"])){
        $data = ["filePath"=> $request->getQueryParams()["f"]];
        $data["filePathR"] = explode("/", $request->getQueryParams()["f"]);
    } else{
        $data = ["filePath"=> ""];
    }

    // ユーザ情報取得
    $userTable = new User($db);
    $userData = $userTable->select([
        "id"=> $_SESSION["userId"]
    ]);
    if (!empty($userData)){
        $data["userDisplayName"] = $userData["display_name"];
    }

    // LAMP一覧を読み込み
    $softwareTable = new Software($db);
    $softwareData = $softwareTable->select([
        "user_id"=> $_SESSION["userId"]
    ], "display_name", "ASC", "", TRUE);
    $data["softwareList"] = $softwareData;
    
    return $view->render($response, 'ctrl/file.twig', $data);
}