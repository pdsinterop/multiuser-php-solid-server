<?php

// phpcs:disable PSR1.Files.SideEffects.FoundWithSymbols

namespace Pdsinterop\PhpSolid\Tests;

require_once(__DIR__ . "/test-config.php");

use Pdsinterop\PhpSolid\JtiStore;
use Pdsinterop\PhpSolid\Db;

class JtiStoreTest extends \PHPUnit\Framework\TestCase
{
	protected function setUp(): void
	{
		$statements = [
			'DROP TABLE IF EXISTS jti',
		'CREATE TABLE IF NOT EXISTS jti (
		jti VARCHAR(255) NOT NULL PRIMARY KEY,
		expires TEXT
		)'
		];

		$db = new Db();
		$db->connect();
		try {
			// create tables
			foreach ($statements as $statement) {
				$db::$pdo->exec($statement);
			}
		} catch (\PDOException $e) {
			echo $e->getMessage();
		}
	}

	public function testNonExistingJti()
	{
		$jti = "123";
		$jtiStore = new JtiStore();
		$found = $jtiStore->hasJti($jti);
		$this->assertFalse($found);
	}

	public function testExistingJti()
	{
		$jti = "123";
		$jtiStore = new JtiStore();
		$jtiStore->saveJti($jti, time() + 3600);
		$found = $jtiStore->hasJti($jti);
		$this->assertTrue($found);
	}

	public function testExpiredJti()
	{
		$jti = "123";
		$jtiStore = new JtiStore();
		$jtiStore->saveJti($jti);
		$db = new Db();
		$query = $db::$pdo->prepare('UPDATE jti SET expires=:expires WHERE jti=:jti');
		$query->execute([
			'expires' => time() - 10,
			'jti' => $jti
		]);
		$found = $jtiStore->hasJti($jti);
		$this->assertFalse($found);
	}

	public function testCleanup()
	{
		$jtiStore = new JtiStore();
		$jtiStore->saveJti("123");
		$jtiStore->saveJti("234");

		$db = new Db();
		$query = $db::$pdo->prepare('UPDATE jti SET expires=:expires WHERE jti=:jti');
		$query->execute([
			'expires' => time() - 10,
			'jti' => "123"
		]);
		$query = $db::$pdo->prepare('UPDATE jti SET expires=:expires WHERE jti=:jti');
		$query->execute([
			'expires' => time() - 10,
			'jti' => "234"
		]);

		$query = $db::$pdo->prepare('SELECT count(*) AS count FROM jti');
		$query->execute();
		$result = $query->fetchAll();
		$beforeCleanup = $result[0]['count'];
		$this->assertEquals(2, $beforeCleanup);


		$jtiStore->cleanupJti();
		$query = $db::$pdo->prepare('SELECT count(*) AS count FROM jti');
		$query->execute();
		$result = $query->fetchAll();
		$afterCleanup = $result[0]['count'];

		$this->assertEquals(0, $afterCleanup);
	}
}
