<?php

use Slim\Http\Request;
use Slim\Http\Response;
use Util\SessionUtil;

$app->get("/ctrl/logout", function (request $request, response $response){
    // ログアウト
    SessionUtil::unsetSession($this->db);

    // ログアウト画面表示
    $data = [];
    return $this->view->render($response, 'ctrl/logout.twig', $data);
});