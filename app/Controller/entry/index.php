<?php

use Slim\Http\Request;
use Slim\Http\Response;
use Util\ValidationUtil;
use Model\Dao\User;

use Util\CtrlUtil;

$app->get("/entry", function (request $request, response $response){
    // ユーザ登録空フォームを表示
    showUserEntryForm($this->view, $response);
});

$app->post("/entry", function (request $request, response $response){
    $inputData = $request->getParsedBody();
    // ユーザの入力情報をバリデート
    $errorMessage = checkUserEntryForm($inputData, $this->db);

    // エラーがあれば再入力、なければ登録
    if ($errorMessage!==""){
        return showUserEntryForm($this->view, $response, $inputData, $errorMessage);
    } else{
        $softwareKey = setUserEntryForm2db($inputData, $this->db);
        if ($softwareKey===FALSE){ // 失敗したときはやり直し
            return showUserEntryForm($view, $response, $inputData, "登録に失敗しました。もう一度、やり直してください。");
        } else{
            $inputData["softwareKey"] = $softwareKey;
            // Render view
            return $this->view->render($response, 'entry/success.twig', $inputData);
        }
    }
});

// ユーザ登録フォームを表示
// 再表示時は、前回入力データ、エラーメッセージを元にフォームを構成
function showUserEntryForm($view, $response, $previousData="", $errorMessage=""){
    if (empty($previousData)){
        $data = [];
    } else{
        $data = $previousData;
    }
    
    $data["errorMessage"] = $errorMessage;

    // Render view
    return $view->render($response, 'entry/index.twig', $data);
}

// ユーザ入力情報のチェック
function checkUserEntryForm($inputData, $db){
    $errorMessage = "";

    // ユーザ名チェック
    $errorMessage = $errorMessage. ValidationUtil::checkString("userName", $inputData["userName"], "・", "\n");
    $userTable = new User($db);
    $userData = $userTable->select(["user_name"=>$inputData["userName"]]);
    if (!empty($userData)){
        $errorMessage = $errorMessage. "・指定されたユーザー名は、すでに使用されています。\n";
    }

    // 表示名チェック
    $errorMessage = $errorMessage. ValidationUtil::checkString("userDisplayName", $inputData["displayName"], "・", "\n");

    // パスワードチェック
    $errorMessage = $errorMessage. ValidationUtil::checkString("userPassword", $inputData["password"], "・", "\n");
    if (!($inputData["password"]===$inputData["confirmPassword"])){
        $errorMessage = $errorMessage. "・パスワードと、パスワードの確認が一致しません。";
    }

    return $errorMessage;
}

// ユーザ登録の書き込み。発行されたソフトウェアキーを返す
function setUserEntryForm2db($inputData, $db){
    $softwareKey = hash('sha256', random_int(PHP_INT_MIN, PHP_INT_MAX));
    $userTable = new User($db);
    $result = $userTable->insert([
        "user_name"=> $inputData["userName"],
        "password_hash"=> password_hash($inputData["password"], PASSWORD_DEFAULT),
        "display_name"=> $inputData["displayName"],
        "software_key"=> $softwareKey,
        "last_logdin_at"=> time(),
        "last_updated_at"=> time()
    ]);
    if ($result===FALSE){
        return FALSE;
    } else{
        return $softwareKey;
    }
}
