<?php

use Slim\Http\Request;
use Slim\Http\Response;
use Doctrine\DBAL\Query\QueryBuilder;
use Model\Dao\User;
use Model\Dao\Software;
use Model\Dao\Directory;
use Util\SessionUtil;
use Util\ValidationUtil;
use Util\FileUtil;

$app->get("/mypage", function (request $request, response $response){
    // マイページ表示
    return showMypage($this->view, $this->db, $response);
});

$app->post("/mypage", function (request $request, response $response){
    $input = $request->getParsedBody();
    $message = "";

    // データベース
    $softwareTable = new Software($this->db);
    $dirTable = new Directory($this->db);
    
    // 設定を反映
    // 操作LAMP変更
    if (!empty($input["defaultLamp"])){
        SessionUtil::setCookie(CONFIG_COOKIE_NAME, $input["defaultLamp"]);
        $_SESSION["defaultLamp"] = $input["defaultLamp"];
        $message = "捜査対象のLAMPを設定しました。\nブラウザの戻る機能を利用する場合は、移動先のページを再読み込みしてください。";
    }

    // LAMP設定
    if (!empty($input["manageLamp"])){
        if ($input["manageLampType"]==="release"){
            $softwareTable->delete([
                "id"=> $input["manageLamp"],
                "user_id"=> $_SESSION["userId"]
            ]);
            $_SESSION["defaultLamp"] = NULL;
            $message = "LAMPの登録を解除しました。\nブラウザの戻る機能を利用する場合は、移動先のページを再読み込みしてください。";
        } elseif ($input["manageLampType"]==="name"){
            $message = ValidationUtil::checkString("lampDisplayName", $input["newName"]);
            if (empty($message)){
                $softwareData = $softwareTable->select([
                    "id"=> $input["manageLamp"],
                    "user_id"=> $_SESSION["userId"]
                ]);
                if (!empty($softwareData)){
                    $softwareTable->update([
                        "id"=> $input["manageLamp"],
                        "display_name"=> $input["newName"]
                    ]);
                    $message = "LAMPの名前を変更しました。";
                } else{
                    $message = "LAMPの名前を変更できませんでした。";
                }
            }

        }   
        
    }

    // フォルダ削除
    if (!empty($input["directory"])){
        set_time_limit(EXTEND_EXECUTE_TIME_LIMIT * 10);
        FileUtil::deleteDirectoryFromId($input["directory"], $this->db);
        $message = "フォルダを削除しました。\nブラウザの戻る機能を利用する場合は、移動先のページを再読み込みしてください。";
    }
    
    // マイページ再表示
    return showMypage($this->view, $this->db, $response, $message);
});

// マイページを表示
function showMypage($view, $db, $response, $message=""){
    $data = ["message"=> $message];
    
    // ユーザー情報を提示
    $userTable = new User($db);
    $userData = $userTable->select([
        "id"=> $_SESSION["userId"]
    ]);
    $data["userDisplayName"] = $userData["display_name"];

    // LAMP一覧を読み込み
    $softwareTable = new Software($db);
    $softwareData = $softwareTable->select([
        "user_id"=> $_SESSION["userId"]
    ], "display_name", "ASC", "", TRUE);
    $data["softwareList"] = $softwareData;

    // ディレクトリ一覧を読み込み
    $queryBuilder = new QueryBuilder($db);
    $queryBuilder
            ->select('*')
            ->from("directory");
    $queryBuilder->andWhere("user_id". " LIKE :user_id");
    $queryBuilder->setParameter(":user_id", $_SESSION["userId"]);
    $queryBuilder->andWhere("parent_id IS NULL");
    $queryBuilder->orderBy("name", "ASC");
    $query = $queryBuilder->execute();
    $dirData = $query->FetchALL();
    $data["directoryList"] = $dirData;

    // Render view
    return $view->render($response, 'mypage/index.twig', $data);
}
