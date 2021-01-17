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
        "session_id"=> $data["session_id"]
    ]);
    if (empty($sessionData)){
        return receiveDisconnectJson();
    }
    
    // ステータスjson取得
    $receiveTable = new Receive($this->db);
    $receiveData = $receiveTable->select([
        "user_id"=> $userData["id"],
        "software_id"=> $data["software_id"]
    ]);
    
    // 古いデータがあったときは、消した上で切断通知
    if (empty($receiveData)){
        return receiveDisconnectJson();
    } elseif ((time() - $receiveData["time"]) > 30){
        $receiveTable->delete(["id"=> $receiveData["id"]]);
        return receiveDisconnectJson();
    }
    $response->withHeader("Content-Type", "application/json");
    $data = json_decode($receiveData["json"]);
    return $response->getBody()->write(json_encode([
        "title"=> htmlspecialchars($data["status"]["fileTitle"]),
        "path"=> htmlspecialchars($data["status"]["filepath"]),
        "album"=> htmlspecialchars($data["status"]["fileAlbum"]),
        "albumArtist"=> htmlspecialchars($data["status"]["fileAlbumArtist"]),
        "artist"=> htmlspecialchars($data["status"]["fileArtist"]),
        "progressTime"=> $data["status"]["playbackTime"],
        "time"=> $data["time"],
        "length"=> $data["status"]["fileLength"],
        "status"=> htmlspecialchars($data["status"]["playbackStatus"])
    ]));
});

// 空のjson
function receiveDisconnectJson($db){
    $data = [
        "status"=> "disconnected"
    ];
    $response->withHeader("Content-Type", "application/json");
    return $response->getBody()->write(json_encode($data));
}
