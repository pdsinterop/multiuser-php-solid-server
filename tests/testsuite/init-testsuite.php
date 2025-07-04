<?php
	require_once(__DIR__ . "/../../config.php");

	class TestUser {
		private static $pdo;
				private static function connect() {
						if (!isset(self::$pdo)) {
								self::$pdo = new \PDO("sqlite:" . DBPATH);
						}
				}

		public static function createUser($testUser) {
			self::connect();
			$query = self::$pdo->prepare(
				 'INSERT INTO users VALUES (:userId, :email, :passwordHash, :data)'
			);
			
			$queryParams = [];
			$queryParams[':userId'] = $testUser['id'];
			$queryParams[':email'] = $testUser['email'];
			$queryParams[':passwordHash'] = password_hash($testUser['password'], PASSWORD_BCRYPT);

			$testUser['webId'] = "https://id-" . $testUser['id'] . "." . BASEDOMAIN . "/#me";
			$queryParams[':data'] = json_encode($testUser);
			$query->execute($queryParams);
		}
	}		

	TestUser::createUser([
		"id" => "alice",
		"password" => "alice123",
		"email" => "alice"
	]);

	TestUser::createUser([
		"id" => "bob",
		"password" => "bob345",
		"email" => "bob"
	]);
	