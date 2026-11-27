<?php

// phpcs:disable Generic.Files.LineLength.TooLong

namespace Pdsinterop\PhpSolid;

class User
{
	private function generateTokenCode()
	{
		$digits = 6;

		$this->cleanupTokens();
		$existingTokens = $this->getExistingVerifyTokens();

		$code = random_int(0, 1000000);
		$code = str_pad($code, $digits, '0', STR_PAD_LEFT);

		while (in_array($code, $existingTokens)) { // make sure we have no collissions;
			$code = random_int(0, 1000000);
			$code = str_pad($code, $digits, '0', STR_PAD_LEFT);
		}

		return $code;
	}

	private function generateTokenHex()
	{
		return bin2hex(random_bytes(16));
	}

	private function generateExpiresTimestamp($lifetime)
	{
		$expires = new \DateTime();
		$expires->add(new \DateInterval($lifetime));
		return $expires->getTimestamp();
	}

	public function saveVerifyToken($tokenType, $tokenData)
	{
		switch ($tokenType) {
			case "verify":
				$tokenData['code'] = $this->generateTokenCode();
				$tokenData['expires'] = $this->generateExpiresTimestamp('PT30M'); // expires after 30 minutes
			break;
			case "passwordReset":
			case "deleteAccount":
			default:
				$tokenData['code'] = $this->generateTokenHex();
				$tokenData['expires'] = $this->generateExpiresTimestamp('PT30M'); // expires after 30 minutes
			break;
		}

		$db = new Db();
		$db->connect();
		$query = $db::$pdo->prepare(
			'INSERT INTO verify VALUES(:code, :data)'
		);
		$query->execute([
			':code' => $tokenData['code'],
			':data' => json_encode($tokenData)
		]);
		return $tokenData;
	}

	public function getVerifyToken($code)
	{
		$db = new Db();
		$db->connect();
		$query = $db::$pdo->prepare(
			'SELECT data FROM verify WHERE code=:code'
		);
		$query->execute([
			':code' => $code
		]);
		$result = $query->fetchAll();
		if (sizeof($result) === 1) {
			$result = json_decode($result[0]['data'], true);
			if (! $this->isExpired($result)) {
				return $result;
			}
		}
		return false;
	}

	private function isExpired($token)
	{
		$now = new \DateTime();
		if ($token['expires'] > $now->getTimestamp()) {
			return false;
		}

		return true;
	}

	public function validatePasswordStrength($password)
	{
		$passwordValidator = new PasswordValidator();
		$entropy = $passwordValidator->getEntropy($password, BANNED_PASSWORDS);
		$minimumEntropy = MINIMUM_PASSWORD_ENTROPY;
		if ($entropy < $minimumEntropy) {
			error_log("Entered password does not satisfy minimum entropy");
			return false;
		}
		return true;
	}

	public function createUser($newUser)
	{
		$db = new Db();
		$db->connect();
		if (! $this->validatePasswordStrength($newUser['password'])) {
			return false;
		}
		$generatedUserId = bin2hex(random_bytes(16));
		while ($this->userIdExists($generatedUserId)) {
			$generatedUserId = bin2hex(random_bytes(16));
		}
		$query = $db::$pdo->prepare(
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

	public function setUserPassword($email, $newPassword)
	{
		if (! $this->userEmailExists($email)) {
			return false;
		}
		if (! $this->validatePasswordStrength($newPassword)) {
			return false;
		}
		$db = new Db();
		$db->connect();
		$query = $db::$pdo->prepare(
			'UPDATE users SET password=:passwordHash WHERE email=:email'
		);
		$queryParams = [];
		$queryParams[':email'] = $email;
		$queryParams[':passwordHash'] = password_hash($newPassword, PASSWORD_BCRYPT);

		$query->execute($queryParams);
		return true;
	}

	public function allowClientForUser($clientId, $userId)
	{
		$db = new Db();
		$db->connect();
		$query = $db::$pdo->prepare(
			'INSERT OR REPLACE INTO allowedClients VALUES(:userId, :clientId)'
		);
		$query->execute([
			':userId' => $userId,
			':clientId' => $clientId
		]);
		return true;
	}

	public function getAllowedClients($userId)
	{
		$db = new Db();
		$db->connect();
		$query = $db::$pdo->prepare(
			'SELECT clientId FROM allowedClients WHERE userId=:userId'
		);
		$query->execute([
			':userId' => $userId
		]);
		$result = [];
		while ($row = $query->fetch()) {
			$result[] = $row['clientId'];
		}
		return $result;
	}

	public function getAllowedOrigins($userId)
	{
		$db = new Db();
		$db->connect();
		$query = $db::$pdo->prepare(
			'SELECT origin from clients LEFT JOIN allowedClients ON clients.clientId=allowedClients.clientId where allowedClients.userId=:userId'
		);
		$query->execute([
			':userId' => $userId
		]);
		$result = [];
		while ($row = $query->fetch()) {
			$result[] = $row['origin'];
		}
		return $result;
	}

	public function getStorage($userId)
	{
		$db = new Db();
		$db->connect();
		$query = $db::$pdo->prepare(
			'SELECT storageUrl FROM userStorage WHERE userId=:userId'
		);
		$query->execute([
			':userId' => $userId
		]);
		$result = [];
		while ($row = $query->fetch()) {
			$result[] = $row['storageUrl'];
		}
		return $result;
	}

	public function setStorage($userId, $storageUrl)
	{
		$db = new Db();
		$db->connect();
		$query = $db::$pdo->prepare(
			'INSERT OR REPLACE INTO userStorage VALUES(:userId, :storageUrl)'
		);
		$query->execute([
			':userId' => $userId,
			':storageUrl' => $storageUrl
		]);
	}

	public function getUser($email)
	{
		if (!isset($email)) {
			return false;
		}
		$db = new Db();
		$db->connect();
		$query = $db::$pdo->prepare(
			'SELECT user_id, data FROM users WHERE email=:email'
		);
		$query->execute([
			':email' => $email
		]);
		$result = $query->fetchAll();
		if (sizeof($result) === 1) {
			$userData = json_decode($result[0]['data'], true);
			$userData['userId'] = $result[0]['user_id'];

			$allowedClients = $this->getAllowedClients($userData['userId']);
			$userData['allowedClients'] = $allowedClients;
			$allowedOrigins = $this->getAllowedOrigins($userData['userId']);
			$userData['allowedOrigins'] = $allowedOrigins;
			$userData['issuer'] = BASEURL;
			$storage = $this->getStorage($userData['userId']);
			if ($storage) {
				$userData['storage'] = $storage;
			}
			return $userData;
		}
		return false;
	}

	public function getUserById($userId)
	{
		$db = new Db();
		$db->connect();
		$query = $db::$pdo->prepare(
			'SELECT user_id, data FROM users WHERE user_id=:userId'
		);
		$query->execute([
			':userId' => $userId
		]);
		$result = $query->fetchAll();
		if (sizeof($result) === 1) {
			$userData = json_decode($result[0]['data'], true);
			$userData['userId'] = $result[0]['user_id'];

			$allowedClients = $this->getAllowedClients($userData['userId']);
			$userData['allowedClients'] = $allowedClients;
			$allowedOrigins = $this->getAllowedOrigins($userData['userId']);
			$userData['allowedOrigins'] = $allowedOrigins;
			$userData['issuer'] = BASEURL;
			$storage = $this->getStorage($userData['userId']);
			if ($storage) {
				$userData['storage'] = $storage;
			}
			return $userData;
		}
		return false;
	}

	public function getUserByWebId($webId)
	{
		$webIdParts = parse_url($webId);
		$idParts = explode(".", $webIdParts['host'], 2);
		if ($idParts[1] !== BASEDOMAIN) {
			return false;
		}
		$userId = preg_replace("/^.*?id-/", "", $idParts[0]);

		return $this->getUserById($userId);
	}

	public function checkPassword($email, $password)
	{
		$db = new Db();
		$db->connect();
		$query = $db::$pdo->prepare(
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

	public function userIdExists($userId)
	{
		$db = new Db();
		$db->connect();
		$query = $db::$pdo->prepare(
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

	public function userEmailExists($email)
	{
		$db = new Db();
		$db->connect();
		$query = $db::$pdo->prepare(
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

	private function deleteUser($email)
	{
		$db = new Db();
		$db->connect();
		$query = $db::$pdo->prepare(
			'DELETE FROM users WHERE email=:email'
		);
		$query->execute([
			':email' => $email
		]);
	}

	private function deleteAllowedClients($email)
	{
		$user = $this->getUser($email);
		if (!$user) {
			return;
		}

		$db = new Db();
		$db->connect();
		$query = $db::$pdo->prepare(
			'DELETE FROM allowedClients WHERE userId=:userId'
		);
		$query->execute([
			':userId' => $user['userId']
		]);
	}

	public function deleteAccount($email)
	{
		if (! $this->userEmailExists($email)) {
			return;
		}
		// FIXME: Delete storage;
		$this->deleteAllowedClients($email);
		$this->deleteUser($email);
	}

	public function cleanupTokens()
	{
		$db = new Db();
		$db->connect();

		$now = new \DateTime();
		$query = $db::$pdo->prepare(
			'DELETE FROM verify WHERE json_extract(data, \'$.expires\') < :now'
		);
		$query->execute([
			':now' => $now->getTimestamp()
		]);
	}

	public function getExistingVerifyTokens()
	{
		$db = new Db();
		$db->connect();
		$query = $db::$pdo->prepare(
			'SELECT code FROM verify'
		);
		$query->execute();
		$existingTokens = $query->fetchAll();
		return $existingTokens;
	}
}
