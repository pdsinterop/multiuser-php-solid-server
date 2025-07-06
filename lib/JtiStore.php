<?php
	namespace Pdsinterop\PhpSolid;

	use Pdsinterop\PhpSolid\Db;
	class JtiStore {
		public static function hasJti($jti) {
			Db::connect();
			$now = new \DateTime();
			$query = Db::$pdo->prepare(
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
			Db::connect();
			$query = Db::$pdo->prepare(
				'INSERT INTO jti VALUES(:jti, :expires)'
			);
			$expires = new \DateTime();
			$expires->add(new \DateInterval("PT1H"));
			$query->execute([
				':jti' => $jti,
				':expires' => $expires->getTimestamp()
			]);
		}

		public static function cleanupJti() {
			Db::connect();
			$now = new \DateTime();
			$query = Db::$pdo->prepare(
				'DELETE FROM jti WHERE expires < :now'
			);
			$query->execute([
				':now' => $now->getTimestamp()
			]);
		}
	}