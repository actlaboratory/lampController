<?php

use Slim\Http\Request;
use Slim\Http\Response;
use Model\Dao\User;
use Model\Dao\Software;
use Util\SessionUtil;

// コントローラ表示
$app->get("/ctrl", function (request $request, response $response){
    return showControllerView($response, $this->view, $this->db);
});


$app->post("/ctrl", function (request $request, response $response){
    $input = $request->getParsedBody();
    
    // 捜査対象LAMP変更
    if (!empty($input["defaultLamp"]) && empty($_SESSION["guestId"])){
        SessionUtil::setCookie(CONFIG_COOKIE_NAME, $input["defaultLamp"]);
        $_SESSION["defaultLamp"] = $input["defaultLamp"];
    }
    return showControllerView($response, $this->view, $this->db);
});

// コントローラ表示
function showControllerView($response, $view, $db){
    $data=[];
    $userTable = new User($db);
    $userData = $userTable->select([
        "id"=> $_SESSION["userId"]
    ]);
    $data = [
        "userDisplayName"=> $userData["display_name"]
    ];
    
    // LAMP一覧を読み込み
    $softwareTable = new Software($db);
    $softwareData = $softwareTable->select([
        "user_id"=> $_SESSION["userId"]
    ], "display_name", "ASC", "", TRUE);
    $data["softwareList"] = $softwareData;

    // Render view
    return $view->render($response, 'ctrl/index.twig', $data);
}
