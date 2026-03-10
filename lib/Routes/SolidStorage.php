<?php
	namespace Pdsinterop\PhpSolid\Routes;

	use Pdsinterop\PhpSolid\User;
	use Pdsinterop\PhpSolid\StorageServer;
	use Pdsinterop\PhpSolid\ClientRegistration;
	use Pdsinterop\PhpSolid\SolidNotifications;
	use Pdsinterop\PhpSolid\Util;
	use Pdsinterop\Solid\Auth\WAC;
	use Pdsinterop\Solid\Resources\Server as ResourceServer;
	use Laminas\Diactoros\ServerRequestFactory;
	use Laminas\Diactoros\Response;

	class SolidStorage {
		public static function respondToStorage() {
			$requestFactory = new ServerRequestFactory();
			$rawRequest = $requestFactory->fromGlobals($_SERVER, $_GET, $_POST, $_COOKIE, $_FILES);

			try {
				StorageServer::initializeStorage();
				$filesystem = StorageServer::getFileSystem();
			} catch (\Exception $e) {
				$response = new Response();
				$response = $response->withStatus(404, "Not found");
				StorageServer::respond($response);
				exit();
			}

			$resourceServer = new ResourceServer($filesystem, new Response(), null);
			$solidNotifications = new SolidNotifications();
			$resourceServer->setNotifications($solidNotifications);

			$wac = new WAC($filesystem);
			
			$baseUrl = Util::getServerBaseUrl();
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
			// $owner = StorageServer::getOwner();
			$ownerWebId = StorageServer::getOwnerWebId();
			$owner = User::getUserByWebId($ownerWebId);
			$allowedOrigins = ($owner['allowedOrigins'] ?? []) + (TRUSTED_APPS ?? []);

			if (!isset($origin) || ($origin === "")) {
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
		}
	}
			