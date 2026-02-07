<?php
	namespace Pdsinterop\PhpSolid\Routes;

	use Pdsinterop\PhpSolid\ProfileServer;
	use Pdsinterop\PhpSolid\ClientRegistration;
	use Pdsinterop\PhpSolid\SolidNotifications;
	use Pdsinterop\PhpSolid\Util;
	use Pdsinterop\Solid\Auth\WAC;
	use Pdsinterop\Solid\Resources\Server as ResourceServer;
	use Laminas\Diactoros\ServerRequestFactory;
	use Laminas\Diactoros\Response;

	class SolidUserProfile {
		public static function respondToProfile() {
			$requestFactory = new ServerRequestFactory();
			$serverData = $_SERVER;
			$serverData['REQUEST_URI'] = "/profile.ttl"; // Hardcoded so we can only ever return profile.ttl

			$rawRequest = $requestFactory->fromGlobals($serverData, $_GET, $_POST, $_COOKIE, $_FILES);
			ProfileServer::initializeProfile();
			$filesystem = ProfileServer::getFileSystem();

			$resourceServer = new ResourceServer($filesystem, new Response(), null);
			$solidNotifications = new SolidNotifications();
			$resourceServer->setNotifications($solidNotifications);

			$wac = new WAC($filesystem);

			$baseUrl = Util::getServerBaseUrl();
			$resourceServer->setBaseUrl($baseUrl);
			$wac->setBaseUrl($baseUrl);

			// use the original $_SERVER without modified path, otherwise the htu check for DPOP will fail
			$webId = ProfileServer::getWebId($requestFactory->fromGlobals($_SERVER, $_GET, $_POST, $_COOKIE, $_FILES));

			if (!isset($webId)) {
				$response = $resourceServer->getResponse()
					->withStatus(409, "Invalid token");
				ProfileServer::respond($response);
				exit();
			}

			$origin = $rawRequest->getHeaderLine("Origin");

			// FIXME: Read allowed clients from the profile instead;
			$owner = ProfileServer::getOwner();

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
			if (!isset($origin) || ($origin === "")) {
				$allowedOrigins[] = "app://unset"; // FIXME: this should not be here.
				$origin = "app://unset";
			}

			if (!$wac->isAllowed($rawRequest, $webId, $origin, $allowedOrigins)) {
				$response = new Response();
				$response = $response->withStatus(403, "Access denied!");
				ProfileServer::respond($response);
				exit();
			}

			$response = $resourceServer->respondToRequest($rawRequest);
			$response = $wac->addWACHeaders($rawRequest, $response, $webId);
			ProfileServer::respond($response);
		}
	}
			