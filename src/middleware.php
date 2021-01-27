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
		if (empty($data[1]) || !$path[1]==="api"){
			return $response = $next($request, $response);
		}
		if (strpos($request->getHeaderLine("Content-Type"), "application/json")===FALSE){
			return ApiUtil::responseErrorJson($response, 400, "not json");
		}
		if(!ApiUtil::apiVersionCheck($path, $request)){
			return ApiUtil::responseErrorJson($response, 400, "different version");
		}
		$data = json_decode($request->getBody());
		if (!empty($path[3]) && $path[3]==="entry"){
			if (ValidationUtil::checkJson($data, "sEntry")){
				return $response = $next($request, $response);
			} else{
				return ApiUtil::responseErrorJson($response, 400, "invalid json");
			}
		} elseif (!empty($path[3]) && $path[3]==="putfile"){
			if (!ValidationUtil::checkJson($data, "sPutfile")){
				return ApiUtil::responseErrorJson($response, 400, "invalid json");
			}
		} else{
			if (!ValidationUtil::checkJson($data, "sSoftware")){
				return ApiUtil::responseErrorJson($response, 400, "invalid json");
			}
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
			if (!empty($path[1]) && $path[1]==="ctrl"){
				return $response->withRedirect($request->getUri()->getBasePath());
			}
			if (!empty($path[1]) && $path[1]==="mypage"){
				return $response->withRedirect($request->getUri()->getBasePath());
			}
			return $next($request, $response);
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
