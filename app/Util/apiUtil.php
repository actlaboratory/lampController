<?php

namespace Util;

use Model\Dao\Software;
use Model\Dao\User;
use Slim\Http\Body;

class ApiUtil{

	// エラーをresponse
	static function responseErrorJson($response, $code, $message){
		$data = ["code"=>$code, "reason"=>$message];
		$response->getBody()->write(json_encode($data));
		return $response->withHeader('Content-Type', 'application/json');
	}

	// 成功をレスポンス
	static function responseSuccessJson($response){
		$data = ["code"=>200, "status"=>"success"];
		$response->getBody()->write(json_encode($data));
		return $response->withHeader('Content-Type', 'application/json');
	}

	// apiバージョンが正しいか確認
	static function apiVersionCheck($path, $request){
		if ($path[1]==="api" && $path[2]==="v1"){
			$data = json_decode($request->getBody(), TRUE);
			if (!empty($data["apiVersion"]) && $data["apiVersion"]==1){
				return TRUE;
			}
		}
		return FALSE;
	}

	static function apiSoftwareCheck($request, $db){
		$data = json_decode($request->getBody(), TRUE);
		if (!empty($data["software"]["driveSerialNo"]) && !empty($data["software"]["pcName"]) && !empty($data["authentication"]["userName"]) && !empty($data["authentication"]["softwareKey"])){
			$driveSerial = $data["software"]["driveSerialNo"];
			$pcName = $data["software"]["pcName"];
			$userName = $data["authentication"]["userName"];
			$softwareKey = $data["authentication"]["softwareKey"];
		} else{
			return FALSE;
		}
		$uTable = new User($db);
		$uInfo = $uTable->select(["user_name"=> $userName, "software_key"=> $softwareKey]);
		$sTable = new Software($db);
		$info = $sTable->select(["drive_serial_no"=>$driveSerial, "pc_name"=>$pcName]);
		if (empty($info) || empty($uInfo)){
			return FALSE;
		} else{
			$_SESSION["softwareId"] = $info["id"];
			$_SESSION["userId"] = $uInfo["id"];
			return TRUE;
		}
	}

}