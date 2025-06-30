<?php
	namespace Pdsinterop\PhpSolid;
	
	class User {
		private static $pdo;
                private static function connect() {
                        if (!isset(self::$pdo)) {
                                self::$pdo = new \PDO("sqlite:" . DBPATH);
                        }
                }

		public static function saveVerifyToken($token) {
			self::connect();
			$query = self::$pdo->prepare(
				'INSERT INTO verify VALUES(:code, :data)'
			);
			$query->execute([
				':code' => $token['code'],
				':data' => json_encode($token)
			]);
		}
		
		public static function getVerifyToken($code) {
			self::connect();
			$query = self::$pdo->prepare(
				'SELECT data FROM verify WHERE code=:code'
			);
			$query->execute([
				':code' => $code
			]);
			$result = $query->fetchAll();
			if (sizeof($result) === 1) {
				$result = json_decode($result[0]['data'], true);
				if (!self::isExpired($result)) {
					return $result;
				}
			}
			return false;
		}
		
		private static function isExpired($token) {
			$now = new \DateTime();
			if ($token['expires'] > $now->getTimestamp()) {
				return false;
			}
			
			return true;
		}
		
		public static function createUser($newUser) {
			self::connect();
			$generatedUserId = md5(random_bytes(32));
			while (self::userIdExists($generatedUserId)) {
				$generatedUserId = md5(random_bytes(32));
			}
			$query = self::$pdo->prepare(
				 'INSERT INTO users VALUES (:userId, :email, :passwordHash, :data)'
			);
			
			$queryParams = [];
			$queryParams[':userId'] = $generatedUserId;
			$queryParams[':email'] = $newUser['email'];
			$queryParams[':passwordHash'] = password_hash($newUser['password'], PASSWORD_BCRYPT);
			unset($newUser['password']);

			$newUser['webId'] = "https://id-" . $generatedUserId . "." . BASEDOMAIN . "/#me";
			$queryParams[':data'] = json_encode($newUser);
			$query->execute($queryParams);

			return [
				"userId" => $generatedUserId,
				"email" => $newUser['email'],
				"webId" => $newUser['webId']
			];
		}

		public static function allowClientForUser($clientId, $userId) {
			self::connect();
			$query = self::$pdo->prepare(
				'INSERT OR REPLACE INTO allowedClients VALUES(:userId, :clientId)'
			);
			$query->execute([
				':userId' => $userId,
				':clientId' => $clientId
			]);
			return true;
		}

		public static function getAllowedClients($userId) {
			self::connect();
			$query = self::$pdo->prepare(
				'SELECT clientId FROM allowedClients WHERE userId=:userId'
			);
			$query->execute([
				':userId' => $userId
			]);
			$result = [];
			while($row = $query->fetch()) {
				$result[] = $row['clientId'];
			}
			return $result;
		}

		public static function getUser($email) {
			self::connect();
			$query = self::$pdo->prepare(
				'SELECT user_id, data FROM users WHERE email=:email'
			);
			$query->execute([
				':email' => $email
			]);
			$result = $query->fetchAll();
			if (sizeof($result) === 1) {
				$userData = json_decode($result[0]['data'], true);
				$userData['userId'] = $result[0]['user_id'];
				
				$allowedClients = self::getAllowedClients($userData['userId']);
				$userData['allowedClients'] = $allowedClients;
				return $userData;
			}
			return false;			
		}

		
		public static function checkPassword($email, $password) {
			self::connect();
			$query = self::$pdo->prepare(
				'SELECT password FROM users WHERE email=:email'
			);
			$query->execute([
				':email' => $email
			]);
			$result = $query->fetchAll();
			if (sizeof($result) === 1) {
				if (password_verify($password, $result[0]['password'])) {
					session_start([
						'cookie_lifetime' => 24*60*60 // 1 day
					]);
					$_SESSION['username'] = $email;
					return true;
				}
			}
			return false;
		}

		public static function getLoggedInUser() {
			session_start();
			if (!isset($_SESSION['username'])) {
				return false;
			}
			return self::getUser($_SESSION['username']);
		}

		public static function userIdExists($userId) {
			self::connect();
			$query = self::$pdo->prepare(
				'SELECT user_id FROM users WHERE user_id=:userId'
			);
			$query->execute([
				':userId' => $userId
			]);
			$result = $query->fetchAll();
			if (sizeof($result) === 1) {
				return true;
			}
			return false;			
		}

		public static function userEmailExists($email) {
			self::connect();
			$query = self::$pdo->prepare(
				'SELECT user_id FROM users WHERE email=:email'
			);
			$query->execute([
				':email' => $email
			]);
			$result = $query->fetchAll();
			if (sizeof($result) === 1) {
				return true;
			}
			return false;			
		}		
	}		
