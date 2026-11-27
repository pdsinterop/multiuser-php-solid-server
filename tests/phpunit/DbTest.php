<?php

namespace Pdsinterop\PhpSolid;

use Pdsinterop\PhpSolid\Db;

defined('DBPATH') || define('DBPATH', ":memory:");

class DbTest extends \PHPUnit\Framework\TestCase
{
	public function testConnect()
	{
		$db = new Db();
		$db->connect();
		$this->assertInstanceOf("PDO", $db::$pdo);
	}
}
