<?php

use Slim\Http\Request;
use Slim\Http\Response;
use Model\Dao\User;
use Util\SessionUtil;

// コントローラ表示
$app->get("/ctrl", function (request $request, response $response){
    $data=[];
    $userTable = new User($this->db);
    $userData = $userTable->select([
        "id"=> $_SESSION["user_id"]
    ]);
    $data = [
        "userDisplayName"=> $userData["display_name"]
    ];
    // Render view
    return $this->view->render($response, 'ctrl/index.twig', $data);
});
