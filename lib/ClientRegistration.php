<?php
	namespace Pdsinterop\PhpSolid;

	use Pdsinterop\PhpSolid\Db;

	class ClientRegistration {
		public static function getRegistration($clientId) {
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
			return false;
		}
		
		public static function saveClientRegistration($clientData) {
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
		
		public static function getClientByOrigin($origin) {
			Db::connect();
			$query = Db::$pdo->prepare(
				'SELECT clientData FROM clients WHERE origin=:origin'
			);
			$query->execute([
				':origin' => $origin
			]);
			$result = $query->fetchAll();
			
			if (sizeof($result)=== 1) {
				return json_decode($result[0]['clientData'], true);
			}
			return false;
		}
	}