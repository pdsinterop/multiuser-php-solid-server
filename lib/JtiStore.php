<?php

namespace Pdsinterop\PhpSolid;

class JtiStore
{
	public function hasJti($jti)
	{
		$db = new Db();
		$db->connect();
		$now = new \DateTime();
		$query = $db::$pdo->prepare(
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

	public function saveJti($jti)
	{
		$db = new Db();
		$db->connect();
		$query = $db::$pdo->prepare(
			'INSERT INTO jti VALUES(:jti, :expires)'
		);
		$expires = new \DateTime();
		$expires->add(new \DateInterval("PT1H"));
		$query->execute([
			':jti' => $jti,
			':expires' => $expires->getTimestamp()
		]);
	}

	public function cleanupJti()
	{
		$db = new Db();
		$db->connect();
		$now = new \DateTime();
		$query = $db::$pdo->prepare(
			'DELETE FROM jti WHERE expires < :now'
		);
		$query->execute([
			':now' => $now->getTimestamp()
		]);
	}
}
