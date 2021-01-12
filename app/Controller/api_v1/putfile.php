<?php

use Slim\Http\Request;
use Slim\Http\Response;
use Model\Dao\User;
use Model\Dao\Directory;
use Model\Dao\File;
use Util\ApiUtil;

// キーとファイル階層のJSONを受け取る
$app->post("/api/v1/putfile", function (request $request, Response $response){
    return json2arrayAndCallFileDatabaseFunction($this->db, $request, $response);
});


// JSONの検証とデータベース登録呼び出し
function json2arrayAndCallFileDatabaseFunction($db, $request, $response){
    $data = json_decode($request->getBody(), TRUE);
    if (!empty($data["file"])){
        $fileArray = $data["file"];
        ApiUtil::responseSuccessJson($response);
    } else{
        return ApiUtil::responseErrorJson($response, 200, "invalid json");
    }
    $dirTable = new Directory($db);
    $fileTable = new File($db);
    array2fileDatabase($fileArray, $dirTable, $fileTable, NULL);
}

function array2fileDatabase($fileArray, $dirTable, $fileTable, $parent){
    foreach ($fileArray as $f){
        if (is_array($f)){
            foreach ($f as $k=>$v){
                $p = $dirTable->insert([
                    "name"=>$k,
                    "parent_id"=>$parent,
                    "user_id"=>1
                ]);
                array2fileDatabase($v, $dirTable, $fileTable, $p);
            }
        } else{
            $fileTable->insert([
                "name"=>$f,
                "directory_id"=>$parent,
            ]);
        }
    }
}
