<?php

namespace Util;

class ValidationUtil{

	// URLの正規表現パターン
	const URL_PATTERN = "@^https?://([a-zA-Z0-9]+[\\.\\-])+[a-zA-Z0-9]+/.*$@";
	const USER_NAME_PATTERN = "@^[a-z0-9._\\-]{6,30}$@";
	const USER_DISPLAY_NAME_PATTERN = "@^.{1,30}$@u";
	const USER_PASSWORD_PATTERN = "@^[a-zA-Z0-9\\.,_\\-\\(\\)\\[\\]]{8,30}$@";

	const PATTERN_ARRAY = [
		"userName"=>[self::USER_NAME_PATTERN, "ユーザー名は、6～30文字で指定してください。英小文字、数字、記号（._-）が使用できます。"],
		"userDisplayName"=> [self::USER_DISPLAY_NAME_PATTERN, "表示名は、1文字以上30文字以内で指定してください。"],
		"userPassword"=>[self::USER_PASSWORD_PATTERN, "パスワードは、8文字以上30文字以内で指定してください。英数字、記号（.,_-()[]）が使用できます。大文字、小文字は区別されます。"]
	];
	// 文字列を検査してエラーメッセージか""を返す
	static function checkString($key, $word, $prefix="", $sufix=""){
		if (preg_match(self::PATTERN_ARRAY[$key][0], $word)){
			return "";
		} else{
			return $prefix. self::PATTERN_ARRAY[$key][1]. $sufix;
		}
	}
	
	//必要な情報が正しい形式で含まれていることを確認する
	static function checkParam(array $data,array $ptn){
		foreach($ptn as $key=>$p){
			if(!isset($data[$key])){
				return false;
			}
			if (\preg_match($p,$data[$key])!=1){
				return false;
			}
		}
		return true;
	}

}
