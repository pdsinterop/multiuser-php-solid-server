<?php
	namespace Pdsinterop\PhpSolid;

	class Session {
		private $cookieLifetime = 24*60*60;
		public static function start($username) {
			session_start([
				'cookie_lifetime' => 24*60*60 // 1 day
			]);
			$_SESSION['username'] = $username;
		}
		
		public static function getLoggedInUser() {
			session_start();
			if (!isset($_SESSION['username'])) {
				return false;
			}
			return $_SESSION['username'];
		}
	}
