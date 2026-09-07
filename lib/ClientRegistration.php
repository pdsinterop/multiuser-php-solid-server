<?php

namespace Pdsinterop\PhpSolid;

use Pdsinterop\PhpSolid\Db;

class ClientRegistration
{
	public static function getRegistration($clientId)
	{
		Db::connect();
		$query = Db::$pdo->prepare(
			'SELECT clientData FROM clients WHERE clientId=:clientId'
		);
		$query->execute([
			':clientId' => $clientId
		]);
		$result = $query->fetchAll();
		if (sizeof($result) === 1) {
			return json_decode($result[0]['clientData'], true);
		}
		if (preg_match("/^http(s)?:/", $clientId)) {
			$clientData = self::getRemoteRegistration($clientId);
			if (!isset($clientData['origin']) && isset($clientData['client_uri'])) {
				$clientData['origin'] = rtrim($clientData['client_uri'], '/');
			}
			if (!isset($clientData['origin'])) {
				$parsedOrigin = parse_url($clientId);
				$origin = $parsedOrigin['scheme'] . '://' . $parsedOrigin['host'];
				if (isset($parsedOrigin['port'])) {
					$origin .= ":" . $parsedOrigin['port'];
				}
				$clientData['origin'] = $origin;
			}
			self::saveClientRegistration($clientData);
			return $clientData;
		}
		return false;
	}

	public static function getRemoteRegistration($url)
	{
		$clientDocument = file_get_contents($url);
		$clientRegistration = json_decode($clientDocument, true);
		if (!isset($clientRegistration['client_id'])) {
			throw new \Exception("No client ID found in client document");
		}
		if (!isset($clientRegistration['redirect_uris'])) {
			throw new \Exception("No redirect URIs found in client document");
		}
		return $clientRegistration;
	}

	public static function saveClientRegistration($clientData)
	{
		Db::connect();
		if (!isset($clientData['client_name'])) {
			$clientData['client_name'] = $clientData['origin'];
		}
		$query = Db::$pdo->prepare(
			'INSERT INTO clients VALUES(:clientId, :origin, :clientData)'
		);
		$query->execute([
			':clientId' => $clientData['client_id'],
			':origin' => $clientData['origin'],
			':clientData' => json_encode($clientData)
		]);
	}

	public static function getClientByOrigin($origin)
	{
		Db::connect();
		$query = Db::$pdo->prepare(
			'SELECT clientData FROM clients WHERE origin=:origin'
		);
		$query->execute([
			':origin' => $origin
		]);
		$result = $query->fetchAll();

		if (sizeof($result) === 1) {
			return json_decode($result[0]['clientData'], true);
		}
		return false;
	}
}
