<?php
	namespace Pdsinterop\PhpSolid;
	
	class Util {
		public static function base64_url_encode($text) {
			return str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($text));
		}

		public static function base64_url_decode($text) {
			return base64_decode(str_replace(['-', '_', ''], ['+', '/', '='], $text));
		}
		public static function getServerName() {
			// FIXME: Depending on the setup, SERVER_NAME might not be the domain of the server.
			return $_SERVER['SERVER_NAME'];
		}
		public static function getServerUri() {
			$scheme = $_SERVER['REQUEST_SCHEME'];
			$domain = $_SERVER['SERVER_NAME'];
			$path = $_SERVER['REQUEST_URI'];
			return "{$scheme}://{$domain}{$path}";
		}
		public static function getServerBaseUrl() {
			$scheme = $_SERVER['REQUEST_SCHEME'];
			$domain = $_SERVER['SERVER_NAME'];
			return "{$scheme}://{$domain}";
		}
	}
