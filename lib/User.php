<?php
	namespace Pdsinterop\PhpSolid;

	use Pdsinterop\PhpSolid\PasswordValidator;
	use Pdsinterop\PhpSolid\Db;
	
	class User {
		private static function generateTokenCode() {
			$digits = 6;
			$code = random_int(0,1000000);
			$code = str_pad($code, $digits, '0', STR_PAD_LEFT);
			return $code;
		}

		private static function generateTokenHex() {
			return md5(random_bytes(32));
		}

		private static function generateExpiresTimestamp($lifetime) {
			$expires = new \DateTime();
			$expires->add(new \DateInterval($lifetime));
			return $expires->getTimestamp();
		}

		public static function saveVerifyToken($tokenType, $tokenData) {
			switch ($tokenType) {
				case "verify":
					$tokenData['code'] = self::generateTokenCode();
					$tokenData['expires'] = self::generateExpiresTimestamp('PT30M'); // expires after 30 minutes
				break;
				case "passwordReset":
				case "deleteAccount":
				default:
					$tokenData['code'] = self::generateTokenHex();
					$tokenData['expires'] = self::generateExpiresTimestamp('PT30M'); // expires after 30 minutes
				break;
			}

			Db::connect();
			$query = Db::$pdo->prepare(
				'INSERT INTO verify VALUES(:code, :data)'
			);
			$query->execute([
				':code' => $tokenData['code'],
				':data' => json_encode($tokenData)
			]);
			return $tokenData;
		}
		
		public static function getVerifyToken($code) {
			Db::connect();
			$query = Db::$pdo->prepare(
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
		
		public static function validatePasswordStrength($password) {
			$entropy = PasswordValidator::getEntropy($password, BANNED_PASSWORDS);
			$minimumEntropy = MINIMUM_PASSWORD_ENTROPY;
			if ($entropy < $minimumEntropy) {
				error_log("Entered pasword does not satisfy minimum entropy");
				return false;
			}
			return true;
		}

		public static function createUser($newUser) {
			Db::connect();
			if (!self::validatePasswordStrength($newUser['password'])) {
				return false;
			}
			$generatedUserId = md5(random_bytes(32));
			while (self::userIdExists($generatedUserId)) {
				$generatedUserId = md5(random_bytes(32));
			}
			$query = Db::$pdo->prepare(
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

		public static function setUserPassword($email, $newPassword) {
			if (!self::userEmailExists($email)) {
				return false;
			}
			if (!self::validatePasswordStrength($newPassword)) {
				return false;
			}
			Db::connect();
			$query = Db::$pdo->prepare(
				 'UPDATE users SET password=:passwordHash WHERE email=:email'
			);
			$queryParams = [];
			$queryParams[':email'] = $email;
			$queryParams[':passwordHash'] = password_hash($newPassword, PASSWORD_BCRYPT);

			$query->execute($queryParams);
			return true;
		}

		public static function allowClientForUser($clientId, $userId) {
			Db::connect();
			$query = Db::$pdo->prepare(
				'INSERT OR REPLACE INTO allowedClients VALUES(:userId, :clientId)'
			);
			$query->execute([
				':userId' => $userId,
				':clientId' => $clientId
			]);
			return true;
		}

		public static function getAllowedClients($userId) {
			Db::connect();
			$query = Db::$pdo->prepare(
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

		public static function getStorage($userId) {
			Db::connect();
			$query = Db::$pdo->prepare(
				'SELECT storageUrl FROM userStorage WHERE userId=:userId'
			);
			$query->execute([
				':userId' => $userId
			]);
			$result = [];
			while($row = $query->fetch()) {
				$result[] = $row['storageUrl'];
			}
			return $result;
		}

		public static function setStorage($userId, $storageUrl) {
			Db::connect();
			$query = Db::$pdo->prepare(
				'INSERT OR REPLACE INTO userStorage VALUES(:userId, :storageUrl)'
			);
			$query->execute([
				':userId' => $userId,
				':storageUrl' => $storageUrl
			]);
		}

		public static function getUser($email) {
			if (!isset($email)) {
				return false;
			}
			Db::connect();
			$query = Db::$pdo->prepare(
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
				$userData['issuer'] = BASEURL;
				$storage = self::getStorage($userData['userId']);
				if ($storage) {
					$userData['storage'] = $storage;
				}
				return $userData;
			}
			return false;
		}

		public static function getUserById($userId) {
			Db::connect();
			$query = Db::$pdo->prepare(
				'SELECT user_id, data FROM users WHERE user_id=:userId'
			);
			$query->execute([
				':userId' => $userId
			]);
			$result = $query->fetchAll();
			if (sizeof($result) === 1) {
				$userData = json_decode($result[0]['data'], true);
				$userData['userId'] = $result[0]['user_id'];

				$allowedClients = self::getAllowedClients($userData['userId']);
				$userData['allowedClients'] = $allowedClients;
				$userData['issuer'] = BASEURL;
				$storage = self::getStorage($userData['userId']);
				if ($storage) {
					$userData['storage'] = $storage;
				}
				return $userData;
			}
			return false;			
		}

		public static function checkPassword($email, $password) {
			Db::connect();
			$query = Db::$pdo->prepare(
				'SELECT password FROM users WHERE email=:email'
			);
			$query->execute([
				':email' => $email
			]);
			$result = $query->fetchAll();
			if (sizeof($result) === 1) {
				if (password_verify($password, $result[0]['password'])) {
					return true;
				}
			}
			return false;
		}

		public static function userIdExists($userId) {
			Db::connect();
			$query = Db::$pdo->prepare(
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
			Db::connect();
			$query = Db::$pdo->prepare(
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

		private static function deleteUser($email) {
			Db::connect();
			$query = Db::$pdo->prepare(
				'DELETE FROM users WHERE email=:email'
			);
			$query->execute([
				':email' => $email
			]);
		}

		private static function deleteAllowedClients($email) {
			$user = self::getUser($email);
			if (!$user) {
				return;
			}

			Db::connect();
			$query = Db::$pdo->prepare(
				'DELETE FROM allowedClients WHERE userId=:userId'
			);
			$query->execute([
				':userId' => $user['userId']
			]);
		}

		public static function deleteAccount($email) {
			if (!self::userEmailExists($email)) {
				return;
			}
			// FIXME: Delete storage;
			self::deleteAllowedClients($email);
			self::deleteUser($email);
		}

		public static function cleanupTokens() {
			Db::connect();

			$now = new \DateTime();
			$query = Db::$pdo->prepare(
				'DELETE FROM verify WHERE json_extract(data, \'$.expires\') < :now'
			);
			$query->execute([
				':now' => $now->getTimestamp()
			]);
		}
	}		
