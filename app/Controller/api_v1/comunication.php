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
$app->post("/api/v1/comunication", function (request $request, Response $response){
    $data = json_decode($request->getBody(), TRUE);

    // データベースオブジェクト
    $userTable = new User($this->db);
    $softwareTable = new Software($this->db);
    $receiveTable = new Receive($this->db);
    $sendTable = new Send_queue($this->db);
    
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
    
    // 古いデータは削除
    $receiveTable->delete([
        "software_id"=> $softwareData["id"],
        "user_id"=> $userData["id"]
    ]);
    
    //レシーブテーブルに登録
    $receiveTable->insert([
        "software_id"=> $softwareData["id"],
        "user_id"=> $userData["id"],
        "time"=> time(),
        "json"=> json_encode($data)
    ]);

    // ネット操作情報を取得
    $queryBuilder = new QueryBuilder($this->db);
    $queryBuilder
			->select('*')
			->from("send_queue");
    $queryBuilder->andWhere("user_id". " LIKE :user_id");
    $queryBuilder->setParameter(":user_id", $userData["id"]);
    $queryBuilder->andWhere("software_id". " LIKE :software_id");
    $queryBuilder->setParameter(":software_id", $softwareData["id"]);
    $queryBuilder->andWhere("milli_time > ". (int)((microtime(TRUE) * 1000) - 10000));
    $queryBuilder->orderBy("milli_time", "DESC");
    $query = $queryBuilder->execute();
    $sendData = $query->FetchALL();

    // 送信データ成型
    $jsonArray = [
        "apiSecInterval"=> API_SEC_INTERVAL,
        "code"=> 200,
        "operation"=> []
    ];
    foreach ($sendData as $d){
        array_push($jsonArray["operation"], json_decode($d["json"], TRUE)["operation"]);
    }

    // 送信キューをクリーンアップ
    $sendTable->delete([
        "user_id"=> $userData["id"],
        "software_id"=> $softwareData["id"]
    ]);
    
    // JSON返却
    $response->withHeader("Content-Type", "application/json");
    return $response->getBody()->write(json_encode($jsonArray));
});
