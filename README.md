# LAMP Controller

## 概要
- LAMPをWEB上から遠隔操作するためのサイト
- レンタルサーバではcomposer使えない場合があるためあえてvenderディレクトリもアップロードしている。composerの動作検証は行っていない。

## 動作環境
- PHP7.4
- Apache 2.4
- Mysql5系

## 手元で動かすには
1. ここをクローン
1. mysqlでデータベースを作成
1. ウェブサーバを設定する。Apache2.4の場合は、.htaccessファイルを作成し、次項のとおり設定
1. このファイルで示す、必要な環境変数を設定

## .htaccessファイル設定例
<IfModule mod_rewrite.c>
	RewriteEngine On
	RewriteCond %{REQUEST_URI} !(^/public/)
	RewriteRule ^ index.php [QSA,L]


## 設定が必要な環境変数
- 設定方法はOSにより異なるが、ウェブサーバがApacheで、.htaccessが使えるなら上記設定と合わせて以下のように書く。
	SetEnv DB_HOST 'localhost'
	SetEnv DB_USER 'user_name'
	SetEnv DB_NAME 'lamp_controller'
	SetEnv DB_PASS '********'
- その他の環境については上記を適宜読み替える。

## 利用ライブラリ
- slim/twig-view
- twig/extensions
- doctorine/dbal
- bryanjhv/slim-session
- Bootstrap4
