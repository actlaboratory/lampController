<?php

namespace Util;

use Model\Dao\Software;
use Slim\Http\Body;

class ApiUtil{

	// エラーをresponse
	static function responseErrorJson($response, $code, $message){
		$data = ["code"=>$code, "reason"=>$message];
		$response->getBody()->write(json_encode($data));
		return $response->withHeader('Content-Type', 'application/json');
	}

	// apiバージョンが正しいか確認
	static function apiVersionCheck($path, $request){
		if ($path[1]==="api" && $path[2]==="v1"){
			$data = json_decode($request->getBody(), TRUE);
			if ($data["apiVersion"]==1){
				return TRUE;
			}
		}
		return FALSE;
	}

	static function apiSoftwareCheck($request, $db){
		$data = json_decode($request->getBody(), TRUE);
		$driveSerial = $data["software"]["driveSerialNo"];
		$pcName = $data["software"]["pcName"];
		$sTable = new Software($db);
		$info = $sTable->select(["drive_serial_no"=>$driveSerial, "pc_name"=>$pcName]);
		if ($info===false){
			return FALSE;
		} else{
			$data["softwareId"] = $info["id"];
			$body = new Body(fopen('php://temp', 'w+'));
			$body->write(json_encode($data, JSON_UNESCAPED_UNICODE));
			$request = $request->withBody($body);
			return TRUE;
		}
	}

}