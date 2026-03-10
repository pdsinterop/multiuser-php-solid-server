<?php
	ini_set("log_errors", 1);
	ini_set('session.cookie_httponly', 1);
	ini_set('expose_php', 'off');

	require_once(__DIR__ . "/../../config.php");
	require_once(__DIR__ . "/../../vendor/autoload.php");

	use Pdsinterop\PhpSolid\Middleware;
	use Pdsinterop\PhpSolid\Routes\SolidStorageProvider;

	$request = explode("?", $_SERVER['REQUEST_URI'], 2)[0];
	$method = $_SERVER['REQUEST_METHOD'];

	Middleware::cors();

	switch($method) {
		case "GET":
			switch ($request) {
				default:
					header($_SERVER['SERVER_PROTOCOL'] . " 404 Not found");
				break;
			}
		break;
		case "POST":
			switch ($request) {
				case "/api/storage/new":
				case "/api/storage/new/":
					StorageServer::respondToStorageNew();
				break;
				default:
					header($_SERVER['SERVER_PROTOCOL'] . " 404 Not found");
				break;
			}
		break;
		case "OPTIONS":
		break;
		case "PUT":
		default:
			header($_SERVER['SERVER_PROTOCOL'] . " 405 Method not allowed");
		break;
	}
