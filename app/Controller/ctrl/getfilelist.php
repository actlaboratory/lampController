<?php

use Slim\Http\Request;
use Slim\Http\Response;
use Doctrine\DBAL\Query\QueryBuilder;
use Model\Dao\Session;
use Model\Dao\Directory;
use Model\Dao\File;
use Util\ApiUtil;

// ファイル、ディレクトリ一覧返却
$app->post("/ctrl/getfilelist", function (request $request, Response $response){
    return json2fileListJson($this->db, $request, $response);
});


// JSONの検証とファイル一覧返却
function json2fileListJson($db, $request, $response){
    $sessionTable = new Session($db);
    
    $data = json_decode($request->getBody(), TRUE);
    if (!isset($data["filePath"]) || empty($data["sessionId"])){
        return ApiUtil::responseErrorJson($response, 400, "invalid json");
    }
    $dirTable = new Directory($db);
    $fileTable = new File($db);
    
    // ユーザ認証
    $sessionData = $sessionTable->select([
        "session_id"=> $data["sessionId"]
    ]);
    if (empty($sessionData)){
        return ApiUtil::responseErrorJson($response, 400, "authentication error");
    }
    $userId = $sessionData["user_id"];

    $pathArray = explode("/", $data["filePath"]);
    if (empty($pathArray[0])){
        $fileList = topLevelDirectoryList($dirTable, $userId, $db);
    } else{
        $fileList = makeFileListFromPathArray($dirTable, $fileTable, $pathArray, $userId, $db);
    }
    // JSON返却
    $response->withHeader("Content-Type", "application/json");
    return $response->getBody()->write(json_encode($fileList));
}

// ファイル一覧取得
function makeFileListFromPathArray($dirTable, $fileTable, $pathArray, $userId, $db){
    $currentData = ["file"=> [], "directory"=> []];
    
    // 検証しながらカレントディレクトリまで掘る
    $parentId = NULL;
    foreach ($pathArray as $i=>$p){
        if ($i === 0){
            $queryBuilder = new QueryBuilder($db);
            $queryBuilder
                    ->select('*')
                    ->from("directory");
            $queryBuilder->andWhere("user_id". " LIKE :user_id");
            $queryBuilder->setParameter(":user_id", $userId);
            $queryBuilder->andWhere("name". " LIKE :name");
            $queryBuilder->setParameter(":name", $p);
            $queryBuilder->andWhere("parent_id IS NULL");
            $query = $queryBuilder->execute();
            $d = $query->Fetch();
            if (empty($d)){
                return FALSE;
            } else{
                $parentId = $d["id"];
            }
        } else{
            $d = $dirTable->select([
                "name"=> $p,
                "user_id"=> $userId,
                "parent_id"=> $parentId
            ]);
            if (empty($d)){
                return FALSE;
            } else{
                $parentId = $d["id"];
            }
        }
    }

    // フォルダ一覧格納
    $dl = $dirTable->select([
        "parent_id"=> $parentId
    ], "name", "ASC", "", TRUE);
    if (empty($dl)){
        $currentData["directory"] = [];
    } else{
        foreach ($dl as $o){
            array_push($currentData["directory"], $o["name"]);
        }
    }

    // ファイルを格納
    $fl = $fileTable->select([
        "directory_id"=> $parentId
    ], "name", "ASC", "", TRUE);
    if (empty($fl)){
        $currentData["file"] = [];
    } else{
        foreach ($fl as $o){
            array_push($currentData["file"], $o["name"]);
        }
    }
    return $currentData;
}

function topLevelDirectoryList($dirTable, $userId, $db){
    $queryBuilder = new QueryBuilder($db);
    $queryBuilder
            ->select('*')
            ->from("directory");
    $queryBuilder->andWhere("user_id". " LIKE :user_id");
    $queryBuilder->setParameter(":user_id", $userId);
    $queryBuilder->andWhere("parent_id IS NULL");
    $queryBuilder->orderBy("name", "ASC");
    $query = $queryBuilder->execute();
    $dl = $query->FetchALL();
    $ret = [];
    if (empty($dl)){
        return FALSE;
    } else{
        foreach ($dl as $o){
            array_push($ret, $o["name"]);
        }
    }
    return ["directory"=> $ret, "file"=> []];
}
