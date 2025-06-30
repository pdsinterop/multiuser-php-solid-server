<?php
	namespace Pdsinterop\PhpSolid;
	
	class ClientRegistration {
		private static $pdo;
		private static function connect() {
			if (!isset(self::$pdo)) {
				self::$pdo = new \PDO("sqlite:" . DBPATH);
			}
		}
			
		public static function getRegistration($clientId) {
			self::connect();
			$query = self::$pdo->prepare(
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
			self::connect();
			$query = self::$pdo->prepare(
				'INSERT INTO clients VALUES(:clientId, :origin, :clientData)'
			);
			$query->execute([
				':clientId' => $clientData['client_id'],
				':origin' => $clientData['origin'],
				':clientData' => json_encode($clientData)
			]);
		}
		
		public static function getClientByOrigin($origin) {
			self::connect();
			$query = self::$pdo->prepare(
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