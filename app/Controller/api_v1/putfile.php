<?php

use Slim\Http\Request;
use Slim\Http\Response;
use Model\Dao\User;
use Model\Dao\Directory;
use Model\Dao\File;
use Util\ApiUtil;
use Util\FileUtil;

// キーとファイル階層のJSONを受け取る
$app->post("/api/v1/putfile", function (request $request, Response $response){
    return json2arrayAndCallFileDatabaseFunction($this->db, $request, $response);
});


// JSONの検証とデータベース登録呼び出し
function json2arrayAndCallFileDatabaseFunction($db, $request, $response){
    $data = json_decode($request->getBody(), TRUE);
    if (!empty($data["file"])){
        $fileArray = $data["file"];
    } else{
        return ApiUtil::responseErrorJson($response, 400, "invalid json");
    }
    $dirTable = new Directory($db);
    $fileTable = new File($db);
    $dirData = $dirTable->select([
        "name"=> key($fileArray[0]),
        "user_id"=> $_SESSION["userId"],
        "parent_id"=>NULL
    ]);
    if (!empty($dirData)){
        if ($data["overWrite"]===TRUE){
            set_time_limit(EXTEND_EXECUTE_TIME_LIMIT * 10);
            FileUtil::deleteDirectoryFromId($dirData["id"], $db);
            set_time_limit(EXTEND_EXECUTE_TIME_LIMIT);
            array2fileDatabase($fileArray, $dirTable, $fileTable, NULL, $_SESSION["userId"]);
            return ApiUtil::responseSuccessJson($response);
        } else{
            return ApiUtil::responseErrorJson($response, 400, "already entered");
        }
    }
    set_time_limit(EXTEND_EXECUTE_TIME_LIMIT);
    array2fileDatabase($fileArray, $dirTable, $fileTable, NULL, $_SESSION["userId"]);
    ApiUtil::responseSuccessJson($response);
}

function array2fileDatabase($fileArray, $dirTable, $fileTable, $parent, $userId){
    foreach ($fileArray as $f){
        if (is_array($f)){
            foreach ($f as $k=>$v){
                $p = $dirTable->insert([
                    "name"=>$k,
                    "parent_id"=>$parent,
                    "user_id"=>$userId
                ]);
                array2fileDatabase($v, $dirTable, $fileTable, $p, $userId);
            }
        } else{
            $fileTable->insert([
                "name"=>$f,
                "directory_id"=>$parent,
            ]);
        }
    }
}
