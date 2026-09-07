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

			if (! isset($clientData['origin'])) {
				if (isset($clientData['client_uri'])) {
					$clientData['origin'] = rtrim($clientData['client_uri'], '/');
				} else {
					// In the OIDC Dynamic Client Registration spec 'client_uri' is optional.
					// @see https://openid.net/specs/openid-connect-registration-1_0.html
					// If it has not been provided, we fall back to the Client ID URI basedomain
					$url = parse_url($clientId);
					$clientData['origin'] = $url['scheme'] . '://' . $url['host'];
				}
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
