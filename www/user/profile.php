<?php
	ini_set("log_errors", 1);
	ini_set('expose_php', 'off');

	require_once(__DIR__ . "/../../config.php");
	require_once(__DIR__ . "/../../vendor/autoload.php");

	use Pdsinterop\PhpSolid\Middleware;
	use Pdsinterop\PhpSolid\Routes\SolidUserProfile;

	$request = explode("?", $_SERVER['REQUEST_URI'], 2)[0];
	$method = $_SERVER['REQUEST_METHOD'];

	Middleware::cors();

	switch($method) {
		case "GET":
			switch ($request) {
				case "/":
					SolidUserProfile::respondToProfile();
				break;
			}
		break;
		case "OPTIONS":
		break;
		case "POST":
		case "PUT":
		default:
			header($_SERVER['SERVER_PROTOCOL'] . " 405 Method not allowed");
		break;
	}
		