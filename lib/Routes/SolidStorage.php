<?php

namespace Pdsinterop\PhpSolid\Routes;

use Laminas\Diactoros\ServerRequestFactory;
use Pdsinterop\PhpSolid\SolidStorageHandler;
use Pdsinterop\PhpSolid\StorageServer;

class SolidStorage
{
	public static function respondToStorage()
	{
		$requestFactory = new ServerRequestFactory();
		$rawRequest = $requestFactory->fromGlobals($_SERVER, $_GET, $_POST, $_COOKIE, $_FILES);

		$handler = new SolidStorageHandler();
		$response = $handler->handle($rawRequest);

		StorageServer::respond($response);
	}
}
