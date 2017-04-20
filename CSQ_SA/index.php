<?php
	define("QUIZZENGER_ROOT", dirname(__FILE__));

	use \quizzenger\controller\Controller as Controller;
	use \quizzenger\controller\AjaxController as AjaxController;

	load_includes();
	https_only();

	$GLOBALS["time_start"] = microtime(true);

	$log = new Logger();
	\quizzenger\logging\Log::set($log);

	$request = array_merge ( $_GET, $_POST );

	if (isset ( $request ['type'] ) && $request ['type'] == "ajax") {
		$controller = new AjaxController ( $request ,$log);
	} else {
		$controller = new Controller ( $request,$log );
	}
	
	// Security Headers
	header('X-XSS-Protection: 1; mode=block');
	header('Strict-Transport-Security: max-age=2592000');
	header('X-Frame-Options: DENY');
	header("Content-Security-Policy: default-src 'self'; style-src  'self' 'unsafe-inline';");

	echo $controller->render();



	// -----------------------


	function https_only(){ // can be turned off in settings
		if ((FORCE_HTTPS_CONNECTION && $_SERVER ['HTTPS'] != "on" )) {
			header ( "HTTP/1.1 301 Moved Permanently" );
			$redirect = "https://" . $_SERVER ['HTTP_HOST'] . $_SERVER ['REQUEST_URI'];
			header ( "Location: $redirect" );
			die ();
		}
	}

	function load_includes(){
		require_once('quizzenger/autoloader.php');
		require_once('includes/config.php');
		require_once('includes/logger.php');
		require_once('includes/sqlhelper.php');
		/*
		include('controller/controller.php');
		include('controller/ajaxController.php');
		//include('model/model.php');
		include('view/view.php');
		include('model/sessionmodel.php');
		include('model/ratingmodel.php');
		include('model/tagmodel.php');
		include('model/registrationmodel.php');
		include('model/questionlistmodel.php');
		include('model/questionmodel.php');
		include('model/categorymodel.php');
		include('model/usermodel.php');
		include('model/answermodel.php');
		include('model/quizlistmodel.php');
		include('model/quizmodel.php');
		include('model/userscoremodel.php');
		include('model/moderationmodel.php');
		include('model/reportmodel.php');
		include('model/reportingmodel.php'); */
	}

?>
