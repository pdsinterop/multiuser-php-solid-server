<?php
	namespace Pdsinterop\PhpSolid\Routes;

	use Pdsinterop\PhpSolid\StorageServer;
	use Laminas\Diactoros\ServerRequestFactory;

	class SolidStorageProvider {
		public static function respondToStorageNew() {
			$requestFactory = new ServerRequestFactory();
			$rawRequest = $requestFactory->fromGlobals($_SERVER, $_GET, $_POST, $_COOKIE, $_FILES);
			$webId = StorageServer::getWebId($rawRequest);

			if (!isset($webId) || $webId === "public") {
				header("HTTP/1.1 400 Bad Request");
				exit();
			}

			// FIXME: Get the webID issuer and validate that we allow storage creation for that issuer

			$createdStorage = StorageServer::createStorage($webId);
			if (!$createdStorage) {
				error_log("Failed to create storage");
				header("HTTP/1.1 400 Bad Request");
				exit();
			}

			$responseData = array(
				"storage" => $createdStorage['storageUrl']
			);
			header("HTTP/1.1 201 Created");
			header("Content-type: application/json");
			echo json_encode($responseData, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);
		}

		public static function respondToPodCount() {
			$requestFactory = new ServerRequestFactory();
			$rawRequest = $requestFactory->fromGlobals($_SERVER, $_GET, $_POST, $_COOKIE, $_FILES);

			if (!defined('POD_COUNT_KEY')) {
				header("HTTP/1.1 404 Not found");
				exit();
			}
			if (!isset($_SERVER['HTTP_POD_COUNT_KEY'])) {
				header("HTTP/1.1 404 Not found");
				exit();
			}
			if ($_SERVER['HTTP_POD_COUNT_KEY'] !== POD_COUNT_KEY) {
				header("HTTP/1.1 400 Bad Request");
				exit();
			}

			$podCount = StorageServer::getPodCount();
			$responseData = array(
				"count" => $podCount
			);
			header("HTTP/1.1 200 OK");
			header("Content-type: application/json");
			echo json_encode($responseData, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);
		}
	}
			