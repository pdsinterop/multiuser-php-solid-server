<?php
	namespace Pdsinterop\PhpSolid;
	
	class IpAttempts {
		private static $pdo;
		private static function connect() {
			if (!isset(self::$pdo)) {
				self::$pdo = new \PDO("sqlite:" . DBPATH);
			}
		}

		public static function logFailedAttempt($ip, $type, $expires) {
			self::connect();
			
			$query = self::$pdo->prepare(
				'INSERT INTO ipAttempts VALUES(:ip, :type, :expires)'
			);
			$query->execute([
				':ip' => $ip,
				':type' => $type,
				':expires' => $expires->getTimestamp()
			]);
		}

		public static function getAttemptsCount($ip, $type) {
			self::connect();

			$now = new \DateTime();
			$query = self::$pdo->prepare(
				'SELECT count(ip) as count FROM ipAttempts WHERE ip=:ip AND type=:type AND expires > :now'
			);
			$query->execute([
				':ip' => $ip,
				':type' => $type,
				':now' => $now->getTimestamp()
			]);
			$result = $query->fetch();
			if (isset($result['count'])) {
				return $result['count'];
			}
			return 0;
		}
		public static function cleanupAttempts() {
			self::connect();
			
			$now = new \DateTime();
			$query = self::$pdo->prepare(
				'DELETE FROM ipAttempts WHERE expires < :now'
			);
			$query->execute([
				':now' => $now->getTimestamp()
			]);
		}
	}
