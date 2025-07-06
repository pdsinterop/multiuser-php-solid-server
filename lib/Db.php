<?php
	namespace Pdsinterop\PhpSolid;
	
	class Db {
		public static $pdo;
		public static function connect() {
			if (!isset(self::$pdo)) {
				self::$pdo = new \PDO("sqlite:" . DBPATH);
			}
		}
	}
