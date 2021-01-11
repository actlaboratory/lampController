<?php
// Application middleware
// e.g: $app->add(new \Slim\Csrf\Guard);

use Util\ApiUtil;

$app->add(new DataBaseTransactionHandler($app->getContainer()));
$app->add(new ApiJsonHandler($app->getContainer()));

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
		if(!ApiUtil::apiVersionCheck($path, $request)){
			return ApiUtil::responseErrorJson($response, 200, "different version");
		}
		if(ApiUtil::apiSoftwareCheck($request, $this->container->get("db"))){
			return $response = $next($request, $response);
		} else{
			return ApiUtil::responseErrorJson($response, 200, "software is incorrect");
		}
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
