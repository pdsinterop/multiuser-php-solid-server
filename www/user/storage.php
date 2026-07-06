<?php

ini_set("log_errors", 1);
ini_set('expose_php', 'off');

require_once(__DIR__ . "/../../config.php");
require_once(__DIR__ . "/../../vendor/autoload.php");

use Pdsinterop\PhpSolid\Middleware;
use Pdsinterop\PhpSolid\Routes\SolidStorage;

$method = $_SERVER['REQUEST_METHOD'];

Middleware::cors();
Middleware::pubsub();

try {
	switch ($method) {
		case "OPTIONS":
			echo "OK";
			return;
		break;
		default:
			SolidStorage::respondToStorage();
		break;
	}
} catch (\Throwable $e) {
	// Catch all so we don't leak information to the client.
	header($_SERVER['SERVER_PROTOCOL'] . " 500 Internal server error");
}
