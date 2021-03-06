<?php

use Slim\Http\Request;
use Slim\Http\Response;
use Model\Dao\Receive;
use Model\Dao\Session;

$app->post("/ctrl/getstatus", function (request $request, response $response){
    $data = json_decode($request->getBody(), TRUE);
    
    // セッションID検査
    $sessionTable = new Session($this->db);
    $sessionData = $sessionTable->select([
        "session_id"=> $data["sessionId"]
    ]);
    if (empty($sessionData) || empty($data["softwareId"])){
        return receiveDisconnectJson($response);
    }
    
    // ステータスjson取得
    $receiveTable = new Receive($this->db);
    $receiveData = $receiveTable->select([
        "software_id"=> $data["softwareId"]
    ]);
    
    // 古いデータがあったときは、消した上で切断通知
    if (empty($receiveData)){
        return receiveDisconnectJson($response);
    } elseif ((time() - $receiveData["time"]) > 30){
        $receiveTable->delete(["id"=> $receiveData["id"]]);
        return receiveDisconnectJson($response);
    }
    $response->withHeader("Content-Type", "application/json");
    $data = (array)json_decode($receiveData["json"]);
    $dataStatus = (array)$data["status"];
    return $response->getBody()->write(json_encode([
        "title"=> htmlspecialchars($dataStatus["fileTitle"]),
        "path"=> htmlspecialchars($dataStatus["filePath"]),
        "album"=> htmlspecialchars($dataStatus["fileAlbum"]),
        "albumArtist"=> htmlspecialchars($dataStatus["fileAlbumArtist"]),
        "artist"=> htmlspecialchars($dataStatus["fileArtist"]),
        "playbackTime"=> $dataStatus["playbackTime"],
        "time"=> $receiveData["time"],
        "length"=> $dataStatus["fileLength"],
        "shuffle"=> $dataStatus["shuffle"],
        "repeatLoop"=> $dataStatus["repeatLoop"],
        "status"=> htmlspecialchars($dataStatus["playbackStatus"])
    ]));
});

// 空のjson
function receiveDisconnectJson($response){
    $data = [
        "title"=> "",
        "path"=> "",
        "album"=> "",
        "albumArtist"=> "",
        "artist"=> "",
        "playbackTime"=> 0,
        "time"=> 0,
        "length"=> 0,
        "shuffle"=> "",
        "repeatLoop"=> "",
        "status"=> "disconnected"
    ];
    $response->withHeader("Content-Type", "application/json");
    return $response->getBody()->write(json_encode($data));
}
