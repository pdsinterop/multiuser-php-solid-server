<?php
	ini_set("log_errors", 1);
	ini_set('expose_php', 'off');

	require_once(__DIR__ . "/../../config.php");
	require_once(__DIR__ . "/../../vendor/autoload.php");

	use Pdsinterop\PhpSolid\Middleware;
	use Pdsinterop\PhpSolid\StorageServer;
	use Pdsinterop\PhpSolid\ClientRegistration;
	use Pdsinterop\PhpSolid\SolidNotifications;
	use Pdsinterop\Solid\Auth\WAC;
	use Pdsinterop\Solid\Resources\Server as ResourceServer;
	use Laminas\Diactoros\ServerRequestFactory;
	use Laminas\Diactoros\Response;
	
	$request = explode("?", $_SERVER['REQUEST_URI'], 2)[0];
	$method = $_SERVER['REQUEST_METHOD'];

	Middleware::cors();
	Middleware::pubsub();

	switch ($method) {
		case "OPTIONS":
			echo "OK";
			return;
		break;
	}

	$requestFactory = new ServerRequestFactory();
	$rawRequest = $requestFactory->fromGlobals($_SERVER, $_GET, $_POST, $_COOKIE, $_FILES);
	$response = new Response();

	StorageServer::initializeStorage();
	$filesystem = StorageServer::getFileSystem();

	$resourceServer = new ResourceServer($filesystem, $response, null);
	$solidNotifications = new SolidNotifications();
	$resourceServer->setNotifications($solidNotifications);

	$wac = new WAC($filesystem);
	
	$baseUrl = $_SERVER['REQUEST_SCHEME'] . "://" . $_SERVER['SERVER_NAME'];
	
	$resourceServer->setBaseUrl($baseUrl);
	$wac->setBaseUrl($baseUrl);

	$webId = StorageServer::getWebId($rawRequest);

	if (!isset($webId)) {
		$response = $resourceServer->getResponse()
			->withStatus(409, "Invalid token");
		StorageServer::respond($response);
		exit();
	}

	$origin = $rawRequest->getHeaderLine("Origin");

	// FIXME: Read allowed clients from the profile instead;
	$owner = StorageServer::getOwner();

	$allowedClients = $owner['allowedClients'] ?? [];
	$allowedOrigins = [];
	foreach ($allowedClients as $clientId) {
		$clientRegistration = ClientRegistration::getRegistration($clientId);
		if (isset($clientRegistration['client_name'])) {
			$allowedOrigins[] = $clientRegistration['client_name'];
		}
		if (isset($clientRegistration['origin'])) {
			$allowedOrigins[] = $clientRegistration['origin'];
		}
	}
	if ($origin =="") {
		$allowedOrigins[] = "app://unset"; // FIXME: this should not be here.
		$origin = "app://unset";
	}

	if (!$wac->isAllowed($rawRequest, $webId, $origin, $allowedOrigins)) {
		$response = new Response();
		$response = $response->withStatus(403, "Access denied!");
		StorageServer::respond($response);
		exit();
	}

	$response = $resourceServer->respondToRequest($rawRequest);
	$response = $wac->addWACHeaders($rawRequest, $response, $webId);
	StorageServer::respond($response);
