<?php

ini_set('display_errors', 'off');
ini_set('expose_php', 'off');
ini_set('log_errors', 1);

require_once(__DIR__ . "/../../config.php");
require_once(__DIR__ . "/../../vendor/autoload.php");

use Pdsinterop\PhpSolid\Middleware;
use Pdsinterop\PhpSolid\Routes\SolidStorage;

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
