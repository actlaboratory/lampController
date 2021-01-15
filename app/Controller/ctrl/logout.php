<?php

use Slim\Http\Request;
use Slim\Http\Response;
use Util\SessionUtil;

$app->get("/ctrl/logout", function (request $request, response $response){
    // ログアウト
    session_cache_limiter('nocache');
    session_destroy();
    $_SESSION = [];
    SessionUtil::unsetCookie(SessionUtil::AUTH_COOKIE_NAME);

    // トップページにリダイレクト
    return $response->withRedirect($request->getUri()->getBasePath(). "/");
});