<?php
	namespace Pdsinterop\PhpSolid\Routes;

	use Pdsinterop\PhpSolid\StorageServer;

	class SolidStorageProvider {
		public static function respondToStorageNew() {
			try {
				$webId = StorageServer::getWebId($rawRequest);

				if (!isset($webId)) {
					$response = $resourceServer->getResponse()
						->withStatus(409, "Invalid token");
					StorageServer::respond($response);
					exit();
				}
			} catch (\Throwable $e) {
				$webId = $_POST['webId'];
				// FIXME: Check against a trusted remote party;
			}

			if (!isset($webId)) {
				header("HTTP/1.1 400 Bad Request");
				exit();
			}

			$createdStorage = StorageServer::createStorage($webId);
			if (!$createdStorage) {
				error_log("Failed to create storage");
				header("HTTP/1.1 400 Bad Request");
				exit();
			}

			//Mailer::sendStorageCreated($createdStoage);

                        $storageUrl = "https://storage-" . $createdStorage['storageId'] . "." . BASEDOMAIN . "/";

			$responseData = array(
				"storage" => $storageUrl
			);
			header("HTTP/1.1 201 Created");
			header("Content-type: application/json");
			echo json_encode($responseData, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);
		}
	}
			