<?php

use Slim\Http\Request;
use Slim\Http\Response;

$app->get("/ctrl/file", function (request $request, response $response){
    if (!empty($request->getQueryParams()["f"])){
        $data = ["filePath"=> $request->getQueryParams()["f"]];
    } else{
        $data = ["filePath"=> ""];
    }

    return $this->view->render($response, 'ctrl/file.twig', $data);
});

