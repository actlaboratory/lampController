<?php

use Slim\Http\Request;
use Slim\Http\Response;
use Model\Dao\User;
use Model\dao\Software;
use Util\ApiUtil;

// ユーザ名、パスワード、ドライブシリアル、コンピュータ名を受け取って登録。
// 成功したらソフトウェアキーを返す
$app->post("/api/v1/entry", function (request $request, Response $response){
    try{
        return entrySoftwareFromJson($request, $response, $this->db);
    } catch(Exception $e) {
        return ApiUtil::responseErrorJson($response, 400, "invalid request");
    }
});

function entrySoftwareFromJson($request, $response, $db){
    $data = json_decode($request->getBody(), TRUE);
    $authArray = $data["authentication"];
    $softwareArray = $data["software"];
    $userTable = new User($db);
    $softwareTable = new Software($db);
    
    // ユーザ認証
    $user = $userTable->select(["user_name"=>$authArray["userName"]]);
    if (empty($user) || (!empty($user) && !password_verify($authArray["password"],$user["password_hash"]))){
        return ApiUtil::responseErrorJson($response, 400, "authentication faild");
    }
    
    // ソフトウェア重複チェック
    $software = $softwareTable->select(["drive_serial_no"=>$softwareArray["driveSerialNo"], "pc_name"=>$softwareArray["pcName"]]);
    if (!$software===FALSE){
        return ApiUtil::responseErrorJson($response, 400, "already entered");
    }
    
    // ソフトウェア登録
    $softwareTable->insert([
        "drive_serial_no"=>$softwareArray["driveSerialNo"],
        "pc_name"=>$softwareArray["pcName"],
        "display_name"=>$softwareArray["displayName"],
        "user_id"=>$user["id"],
        "entered_at"=>time()
    ]);
    $response->withHeader("Content-Type", "application/json");
    return $response->getBody()->write(json_encode([
        "code"=>200,
        "status"=>"success",
        "softwareKey"=>$user["software_key"]
    ]));
}
