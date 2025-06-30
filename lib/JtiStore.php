<?php
	namespace Pdsinterop\PhpSolid;
	
	class JtiStore {
		private static $pdo;
		private static function connect() {
			if (!isset(self::$pdo)) {
				self::$pdo = new \PDO("sqlite:" . DBPATH);
			}
		}
			
		public static function hasJti($jti) {
			self::connect();
			$now = new \DateTime();
			
			$query = self::$pdo->prepare(
				'SELECT jti FROM jti WHERE jti=:jti AND expires>:now'
			);
			$query->execute([
				':jti' => $jti,
				':now' => $now->getTimestamp()
			]);
			$result = $query->fetchAll();
			if (sizeof($result) === 1) {
				return true;
			}
			return false;
		}
		
		public static function saveJti($jti) {
			self::connect();
			$query = self::$pdo->prepare(
				'INSERT INTO jti VALUES(:jti, :expires)'
			);
			$expires = new \DateTime();
			$expires->add(new \DateInterval("PT1H"));
			$query->execute([
				':jti' => $jti,
				':expires' => $expires->getTimestamp()
			]);
		}
	}