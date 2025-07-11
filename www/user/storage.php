<?php
	ini_set("log_errors", 1);
	ini_set('expose_php', 'off');

	require_once(__DIR__ . "/../../config.php");
	require_once(__DIR__ . "/../../vendor/autoload.php");

	use Pdsinterop\PhpSolid\Middleware;
	use Pdsinterop\PhpSolid\Api\SolidStorage;
	
	$method = $_SERVER['REQUEST_METHOD'];

	Middleware::cors();
	Middleware::pubsub();

	switch ($method) {
		case "OPTIONS":
			echo "OK";
			return;
		break;
		default:
			SolidStorage::respondToStorage();
		break;
	}
