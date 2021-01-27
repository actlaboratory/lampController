<?php

use Slim\Http\Request;
use Slim\Http\Response;
use Doctrine\DBAL\Query\QueryBuilder;
use Model\Dao\User;
use Model\Dao\Software;
use Model\Dao\Receive;
use Model\Dao\Send_queue;
use Util\ApiUtil;

// キーとファイル階層のJSONを受け取る
$app->post("/api/v1/softwaremanage", function (request $request, Response $response){
    $data = json_decode($request->getBody(), TRUE);

    // データベースオブジェクト
    $userTable = new User($this->db);
    $softwareTable = new Software($this->db);
    
    // ユーザ認証
    $userData = $userTable->select([
        "user_name"=> $data["authentication"]["userName"],
        "software_key"=> $data["authentication"]["softwareKey"]
    ]);
    if (empty($userData)){
        return ApiUtil::responseErrorJson($response, 400, "user authentication faild");
    }
    $softwareData = $softwareTable->select([
        "drive_serial_no"=> $data["software"]["driveSerialNo"],
        "pc_name"=> $data["software"]["pcName"]
    ]);
    if (empty($softwareData)){
        return ApiUtil::responseErrorJson($response, 400, "software authentication faild");
    }
    
    // 送信データ成型
    $jsonArray = [
        "apiSecInterval"=> API_SEC_INTERVAL,
        "code"=> 200,
        "displayName"=> $softwareData["display_name"]
    ];

    // 解除が要求されたときはソフトウェアを削除
    if (!empty($data["operation"]) && $data["operation"]==="release"){
        $softwareTable->delete(["id"=> $softwareData["id"]]);
    }
    
    // JSON返却
    $response->withHeader("Content-Type", "application/json");
    return $response->getBody()->write(json_encode($jsonArray));
});
