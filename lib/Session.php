<?php

namespace Pdsinterop\PhpSolid;

class Session
{
	public function start($username)
	{
		if (session_status() === PHP_SESSION_NONE) {
			session_start([
				'cookie_lifetime' => 24 * 60 * 60 // 1 day
			]);
		}
		$_SESSION['username'] = $username;
	}

	public function getLoggedInUser()
	{
		if (session_status() === PHP_SESSION_NONE) {
			session_start();
		}
		if (!isset($_SESSION['username'])) {
			return false;
		}
		return $_SESSION['username'];
	}
}
