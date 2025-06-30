<?php
	namespace Pdsinterop\PhpSolid;
	
	class Util {
		public static function base64_url_encode($text) {
			return str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($text));
		}

		public static function base64_url_decode($text) {
			return base64_decode(str_replace(['-', '_', ''], ['+', '/', '='], $text));
		}
	}
