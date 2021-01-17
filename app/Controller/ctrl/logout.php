<?php

use Slim\Http\Request;
use Slim\Http\Response;
use Util\SessionUtil;

$app->get("/ctrl/logout", function (request $request, response $response){
    // ログアウト
    SessionUtil::unsetSession($this->db);

    // トップページにリダイレクト
    return $response->withRedirect($request->getUri()->getBasePath(). "/");
});