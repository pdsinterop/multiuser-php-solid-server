<?php

namespace Pdsinterop\PhpSolid;

use Pdsinterop\PhpSolid\Db;

class IpAttempts
{
	public function logFailedAttempt($ip, $type, $expires)
	{
		if (in_array($ip, TRUSTED_IPS)) {
			return;
		}

		$db = new Db();
		$db->connect();

		$query = $db::$pdo->prepare(
			'INSERT INTO ipAttempts VALUES(:ip, :type, :expires)'
		);
		$query->execute([
			':ip' => $ip,
			':type' => $type,
			':expires' => $expires
		]);
	}

	public function getAttemptsCount($ip, $type)
	{
		if (in_array($ip, TRUSTED_IPS)) {
			return 0;
		}

		$db = new Db();
		$db->connect();

		$now = new \DateTime();
		$query = $db::$pdo->prepare(
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
	public function cleanupAttempts()
	{
		$db = new Db();
		$db->connect();

		$now = new \DateTime();
		$query = $db::$pdo->prepare(
			'DELETE FROM ipAttempts WHERE expires < :now'
		);
		$query->execute([
			':now' => $now->getTimestamp()
		]);
	}
}
