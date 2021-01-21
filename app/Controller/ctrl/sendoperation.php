<?php

use Slim\Http\Request;
use Slim\Http\Response;
use Model\Dao\Session;
use Model\Dao\Send_queue;
use Util\ApiUtil;

$app->post("/ctrl/sendoperation", function (request $request, response $response){
    $data = json_decode($request->getBody(), TRUE);
    
    // セッションID検査
    $sessionTable = new Session($this->db);
    $sessionData = $sessionTable->select([
        "session_id"=> $data["sessionId"]
    ]);
    if (empty($sessionData) || empty($data["softwareId"])){
        return;
    }
    
    // コマンドキューに追加
    $sendTable = new Send_queue($this->db);
    $sendTable->insert([
        "milli_time"=> (int)(microtime(TRUE)*1000),
        "software_id"=> $data["softwareId"],
        "user_id"=> $sessionData["user_id"],
        "json"=> json_encode(["operation"=> $data["operation"]])
    ]);

    ApiUtil::responseSuccessJson($response);
});
