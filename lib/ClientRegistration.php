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

		if ($clientDocument === false && error_get_last() !== null) {
			throw new \Exception(vsprintf('Unable to fetch URL: %s: ', [$url, var_export(error_get_last(), true),]));
		} elseif (! is_string($clientDocument) || $clientDocument === '') {
			throw new \Exception('Unable to fetch URL: ' . $url);
		}

		try {
			$clientRegistration = json_decode($clientDocument, true, 512, JSON_THROW_ON_ERROR);
		} catch (\Throwable $e) {
			$message = vsprintf('Could not JSON decode payload from URL "%s": %s (%s)', [
				$url,
				$e->getMessage(),
				$e->getCode()
			]);
			throw new \Exception($message);
		}

		if (! is_array($clientRegistration)) {
			throw new \Exception('Invalid JSON payload from URL: ' . $url);
		} elseif (! isset($clientRegistration['client_id'])) {
			throw new \Exception('No client ID found in client document');
		} elseif (! isset($clientRegistration['redirect_uris'])) {
			throw new \Exception('No redirect URIs found in client document');
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
