<?php

use Slim\Http\Request;
use Slim\Http\Response;
use Model\Dao\User;

$app->get("/ctrl/file", function (request $request, response $response){
    if (!empty($request->getQueryParams()["f"])){
        $data = ["filePath"=> $request->getQueryParams()["f"]];
        $data["filePathR"] = explode("/", $request->getQueryParams()["f"]);
    } else{
        $data = ["filePath"=> ""];
    }

    // ユーザ情報取得
    $userTable = new User($this->db);
    $userData = $userTable->select([
        "id"=> $_SESSION["userId"]
    ]);
    if (!empty($userData)){
        $data["userDisplayName"] = $userData["display_name"];
    }

    return $this->view->render($response, 'ctrl/file.twig', $data);
});

