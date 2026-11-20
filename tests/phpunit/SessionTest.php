<?php

namespace Pdsinterop\PhpSolid;

use Pdsinterop\PhpSolid\Session;

function session_start($options = [])
{
	SessionTest::$sessionOptions = $options;
}

class SessionTest extends \PHPUnit\Framework\TestCase
{
	public static $sessionOptions = [];
	public function testStart()
	{
		$session = new Session();
		$session->start("alice");
		$this->assertEquals($_SESSION['username'], "alice");
		$this->assertEquals(self::$sessionOptions, ['cookie_lifetime' => 86400]);
	}

	public function testGetLoggedInUser()
	{
		$session = new Session();
		$session->start("alice");
		$user = $session->getLoggedInUser();
		$this->assertEquals($user, "alice");
	}
}
