<?php

namespace Pdsinterop\PhpSolid;

use Laminas\Diactoros\Response;
use Pdsinterop\Solid\Auth\WAC;
use Pdsinterop\Solid\Resources\Server as ResourceServer;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class SolidStorageHandler
{
	public function handle(ServerRequestInterface $rawRequest): ResponseInterface
	{
		try {
			StorageServer::initializeStorage();
			$filesystem = StorageServer::getFileSystem();
		} catch (\Exception $e) {
			return (new Response())->withStatus(404, "Not found");
		}

		$resourceServer = new ResourceServer($filesystem, new Response(), null);

		$solidNotifications = new SolidNotifications();
		$resourceServer->setNotifications($solidNotifications);

		$wac = new WAC($filesystem);

		$baseUrl = Util::getServerBaseUrl();
		$resourceServer->setBaseUrl($baseUrl);
		$wac->setBaseUrl($baseUrl);

		try {
			$webId = StorageServer::getWebId($rawRequest);
		} catch (\Exception $e) {
			return $resourceServer->getResponse()
				->withStatus(400, "Bad request");
		}

		if (!isset($webId)) {
			return $resourceServer->getResponse()
				->withStatus(409, "Invalid token");
		}

		$origin = $rawRequest->getHeaderLine("Origin");

		// FIXME: Read allowed clients from the profile instead;
		$ownerWebId = StorageServer::getOwnerWebId();
		$owner = User::getUserByWebId($ownerWebId);
		$allowedClients = $owner['allowedClients'] ?? [];

		$allowedOrigins = array_merge(
			($owner['allowedOrigins'] ?? []),
			(TRUSTED_APPS ?? [])
		);
		$allowedOrigins = array_unique($allowedOrigins);

		if (!isset($origin) || ($origin === "")) {
			$allowedOrigins[] = "app://unset"; // FIXME: this should not be here.
			$origin = "app://unset";
		}

		if (!$wac->isAllowed($rawRequest, $webId, $origin, $allowedOrigins)) {
			return (new Response())->withStatus(403, "Access denied!");
		}

		$response = $resourceServer->respondToRequest($rawRequest);

		return $wac->addWACHeaders($rawRequest, $response, $webId);
	}
}
