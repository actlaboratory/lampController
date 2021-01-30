<?php
// Application middleware
// e.g: $app->add(new \Slim\Csrf\Guard);

use Util\ApiUtil;
use Util\SessionUtil;
use Util\ValidationUtil;

$app->add(new DataBaseTransactionHandler($app->getContainer()));
$app->add(new ApiJsonHandler($app->getContainer()));
$app->add(new siteMainHandler($app->getContainer()));

class DataBaseTransactionHandler{

	private $container;

	public function __construct($container) {
		$this->container = $container;
	}

	//DBのトランザクションの開始・停止を行う
	public function __invoke($request, $response, $next){
		$this->container->get("db")->beginTransaction();
		$this->container->get("db")->setAutoCommit(false);
		$this->container->get("logger")->info("DB: start transaction");
		$response = $next($request, $response);
		$this->container->get("db")->commit();
		$this->container->get("logger")->info("DB: commit transaction");
		return $response;
	}

}

class ApiJsonHandler{

	private $container;

	public function __construct($container) {
		$this->container = $container;
	}

	//api用jsonの検証
	public function __invoke($request, $response, $next){
		$path = explode("/",$request->getUri()->getPath());
		if (empty($path[1]) || $path[1]!=="api"){
			return $response = $next($request, $response);
		}
		if (strpos($request->getHeaderLine("Content-Type"), "application/json")===FALSE){
			return ApiUtil::responseErrorJson($response, 400, "not json");
		}
		
		// JSONバリデーション
		$data = json_decode($request->getBody());
		if (!empty($path[3]) && $path[3]==="entry"){
			$valid = ValidationUtil::checkJson($data, "sEntry");
			if ($valid!==""){
				return ApiUtil::responseErrorJson($response, 400, "$valid");
			}
		} elseif (!empty($path[3]) && $path[3]==="putfile"){
			$valid = ValidationUtil::checkJson($data, "sPutfile");
			if ($valid!==""){
				return ApiUtil::responseErrorJson($response, 400, $valid);
			}
		} else{
			$valid = ValidationUtil::checkJson($data, "sSoftware");
			if ($valid!==""){
				return ApiUtil::responseErrorJson($response, 400, $valid);
			}
		}
		
		if(!ApiUtil::apiVersionCheck($path, $request)){
			return ApiUtil::responseErrorJson($response, 400, "different version");
		}
		if (!empty($path[3]) && $path[3]==="entry"){
			return $response = $next($request, $response);
		}
		if(ApiUtil::apiSoftwareCheck($request, $this->container->get("db"))){
			return $response = $next($request, $response);
		} else{
			return ApiUtil::responseErrorJson($response, 400, "software is incorrect");
		}
	}
}

class SiteMainHandler{

	private $container;

	public function __construct($container) {
		$this->container = $container;
	}

	//COOKIE等全体に関わる認証
	public function __invoke($request, $response, $next){
		$path = explode("/",$request->getUri()->getPath());
		if (!empty($path[1]) && $path[1]==="login"){
			return $response = $next($request, $response);
		}
		// セッションスタート
		if (!SessionUtil::setSession($this->container->get("db"))){
			SessionUtil::unsetSession($this->container->get("db"));
			if (!empty($path[1]) && ($path[1]==="ctrl" || $path[1]==="mypage")){
				return $response->withRedirect($request->getUri()->getBasePath());
			}
			return $next($request, $response);
		}
		if (!empty($path[1]) && $path[1]==="mypage" && !empty($_SESSION["guestId"])){
			return $response->withRedirect($request->getUri()->getBasePath());
		}
		return $next($request, $response);
	}
}


set_error_handler(function(int $errno, string $errstr, string $errfile, int $errline, array $errcontext){
	$GLOBALS["app"]->getContainer()->get("logger")->error("ERROR lv.".$errno." ".$errstr." at ".$errfile." line:".$errline);
	if ($GLOBALS["app"]->getContainer()->get("db")->isTransactionActive()){
		$GLOBALS["app"]->getContainer()->get("logger")->info("DB rollback");
		$GLOBALS["app"]->getContainer()->get("db")->rollBack();
	}
	return false;
});
