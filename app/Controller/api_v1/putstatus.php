<?php

use Slim\Http\Request;
use Slim\Http\Response;
use Model\Dao\User;
use Model\Dao\Software;
use Model\Dao\Receive;
use Util\ApiUtil;

// キーとファイル階層のJSONを受け取る
$app->post("/api/v1/putstatus", function (request $request, Response $response){
    $data = json_decode($request->getBody(), TRUE);

    // データベースオブジェクト
    $userTable = new User($this->db);
    $softwareTable = new Software($this->db);
    $receiveTable = new Receive($this->db);
    
    // ユーザ認証
    if (empty($userTable->select([
        "user_name"=> $data["authentication"]["userName"],
        "software_key"=> $data["authentication"]["softwareKey"]
    ]))){
        return ApiUtil::responseErrorJson($response, 400, "user authentication faild");
    }
    $softwareData = $softwareTable->select([
        "drive_serial_no"=> $data["software"]["driveSerialNo"],
        "pc_name"=> $data["software"]["pcName"]
    ]);
    if (empty($softwareData)){
        return ApiUtil::responseErrorJson($response, 400, "software authentication faild");
    }
    
    //レシーブテーブルに登録
    $receiveTable->insert([
        "software_id"=> $softwareData["id"],
        "time"=> time(),
        "json"=> json_encode($data)
    ]);

    // 成功を返す
    return ApiUtil::responseSuccessJson($response);
});
