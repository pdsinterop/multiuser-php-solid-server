<?php

// phpcs:disable Generic.Files.LineLength.TooLong
// phpcs:disable PSR1.Files.SideEffects.FoundWithSymbols

namespace Pdsinterop\PhpSolid\Tests;

require_once(__DIR__ . "/test-config.php");

use Pdsinterop\PhpSolid\User;
use Pdsinterop\PhpSolid\Db;

class UserTest extends \PHPUnit\Framework\TestCase
{
	protected function setUp(): void
	{
		$futureTimestamp = new \DateTime();
		$futureTimestamp->add(new \DateInterval('P120M'));
		$statements = [
			'DROP TABLE IF EXISTS allowedClients',
			'DROP TABLE IF EXISTS userStorage',
			'DROP TABLE IF EXISTS verify',
			'DROP TABLE IF EXISTS users',
			'CREATE TABLE IF NOT EXISTS allowedClients (
				userId VARCHAR(255) NOT NULL PRIMARY KEY,
				clientId VARCHAR(255) NOT NULL
			)',
			'CREATE TABLE IF NOT EXISTS userStorage (
				userId VARCHAR(255) NOT NULL PRIMARY KEY,
				storageUrl VARCHAR(255) NOT NULL
			)',
			'CREATE TABLE IF NOT EXISTS verify (
				code VARCHAR(255) NOT NULL PRIMARY KEY,
				data TEXT NOT NULL
			)',
			'CREATE TABLE IF NOT EXISTS users (
				user_id VARCHAR(255) NOT NULL PRIMARY KEY,
				email TEXT NOT NULL,
				password TEXT NOT NULL,
				data TEXT
			)',
			'INSERT INTO verify VALUES("test1", \'{"expires": 0, "hello": "world", "code": "test1"}\')',
			'INSERT INTO verify VALUES("test2", \'{"expires": ' . $futureTimestamp->getTimestamp() . ', "hello": "world", "code": "test2"}\')'
		];

		$db = new Db();
		$db->connect();
		try {
			// create tables
			foreach ($statements as $statement) {
				$db::$pdo->exec($statement);
			}
		} catch (\PDOException $e) {
			echo $e->getMessage();
		}
	}

	public function testSaveVerifyToken()
	{
		$beforeExpires = new \DateTime();
		$beforeExpires->add(new \DateInterval('PT29M'));

		$afterExpires = new \DateTime();
		$afterExpires->add(new \DateInterval('PT31M'));
		$user = new User();
		$token = $user->saveVerifyToken("verify", [
			"hello" => "world"
		]);
		$this->assertTrue($token['expires'] > $beforeExpires->getTimestamp());
		$this->assertTrue($token['expires'] < $afterExpires->getTimestamp());

		$storedToken = $user->getVerifyToken($token['code']);
		$this->assertEquals($storedToken['hello'], "world");
	}

	public function testSavePasswordResetToken()
	{
		$beforeExpires = new \DateTime();
		$beforeExpires->add(new \DateInterval('PT29M'));

		$afterExpires = new \DateTime();
		$afterExpires->add(new \DateInterval('PT31M'));
		$user = new User();
		$token = $user->saveVerifyToken("verify", [
			"hello" => "world"
		]);
		$this->assertTrue($token['expires'] > $beforeExpires->getTimestamp());
		$this->assertTrue($token['expires'] < $afterExpires->getTimestamp());

		$storedToken = $user->getVerifyToken($token['code']);
		$this->assertEquals($storedToken['hello'], "world");
	}

	public function testSaveAccountDeleteToken()
	{
		$beforeExpires = new \DateTime();
		$beforeExpires->add(new \DateInterval('PT29M'));

		$afterExpires = new \DateTime();
		$afterExpires->add(new \DateInterval('PT31M'));
		$user = new User();
		$token = $user->saveVerifyToken("verify", [
			"hello" => "world"
		]);
		$this->assertTrue($token['expires'] > $beforeExpires->getTimestamp());
		$this->assertTrue($token['expires'] < $afterExpires->getTimestamp());

		$storedToken = $user->getVerifyToken($token['code']);
		$this->assertEquals($storedToken['hello'], "world");
	}

	public function testExpiredToken()
	{
		$user = new User();
		$token = $user->getVerifyToken("test1");
		$this->assertFalse($token);
	}

	public function testNonExpiredToken()
	{
		$user = new User();
		$token = $user->getVerifyToken("test2");
		$this->assertEquals($token['hello'], "world");
	}

	public function testCreateUser()
	{
		$newUser = [
			"password" => "hello123!@#ABC",
			"email" => "user@example.com",
			"hello" => "world"
		];
		$user = new User();
		$createdUser = $user->createUser($newUser);
		$this->assertEquals($createdUser['email'], "user@example.com");
		$this->assertTrue(isset($createdUser['webId']));
		$this->assertTrue(isset($createdUser['userId']));
		$this->assertTrue(strlen($createdUser['userId']) === 32);

		$canLogIn = $user->checkPassword($newUser['email'], $newUser['password']);
		$this->assertTrue($canLogIn);
	}

	public function testGetUser()
	{
		$newUser = [
			"password" => "hello123!@#ABC",
			"email" => "user2@example.com",
			"hello" => "world"
		];
		$user = new User();
		$createdUser = $user->createUser($newUser);

		$userByEmail = $user->getUser($newUser['email']);
		$this->assertEquals($userByEmail['webId'], $createdUser['webId']);
		$this->assertEquals($userByEmail['hello'], 'world');
		$this->assertTrue(isset($userByEmail['allowedClients']));
		$this->assertEquals($userByEmail['issuer'], "https://solid.example.com");
	}

	public function testGetUserById()
	{
		$newUser = [
			"password" => "hello123!@#ABC",
			"email" => "user3@example.com",
			"hello" => "world"
		];
		$user = new User();
		$createdUser = $user->createUser($newUser);

		$userById = $user->getUserById($createdUser['userId']);
		$this->assertEquals($userById['webId'], $createdUser['webId']);
		$this->assertEquals($userById['hello'], 'world');
		$this->assertTrue(isset($userById['allowedClients']));
		$this->assertEquals($userById['issuer'], "https://solid.example.com");
	}

	public function testSetPasswordNonExistingUser()
	{
		$user = new User();
		$result = $user->setUserPassword("not_here@example.com", "hello123!@#ABC");
		$this->assertFalse($result);
	}

	public function testSetWeakPassword()
	{
		$newUser = [
			"password" => "hello123!@#ABC",
			"email" => "user4@example.com",
			"hello" => "world"
		];
		$user = new User();
		$createdUser = $user->createUser($newUser);
		$result = $user->setUserPassword($newUser['email'], "a");
		$this->assertFalse($result);
	}

	public function testLogin()
	{
		$newUser = [
			"password" => "hello123!@#ABC",
			"email" => "user5@example.com",
			"hello" => "world"
		];
		$user = new User();
		$createdUser = $user->createUser($newUser);

		$canLogIn = $user->checkPassword($newUser['email'], $newUser['password']);
		$this->assertTrue($canLogIn);
	}

	public function testLoginFailed()
	{
		$newUser = [
			"password" => "hello123!@#ABC",
			"email" => "user6@example.com",
			"hello" => "world"
		];
		$user = new User();
		$createdUser = $user->createUser($newUser);

		$canLogIn = $user->checkPassword($newUser['email'], "something else");
		$this->assertFalse($canLogIn);
	}

	public function testSetStrongPassword()
	{
		$newUser = [
			"password" => "hello123!@#ABC",
			"email" => "user7@example.com",
			"hello" => "world"
		];
		$user = new User();
		$createdUser = $user->createUser($newUser);

		$result = $user->setUserPassword($newUser['email'], "this is a strong password because it is long enough");
		$this->assertTrue($result);
	}

	public function testLoginAfterChange()
	{
		$newUser = [
			"password" => "hello123!@#ABC",
			"email" => "user8@example.com",
			"hello" => "world"
		];
		$user = new User();
		$createdUser = $user->createUser($newUser);
		$canLogIn = $user->checkPassword($newUser['email'], $newUser['password']);
		$this->assertTrue($canLogIn);

		$newPassword = "this is a strong password because it is long enough";
		$result = $user->setUserPassword($newUser['email'], $newPassword);
		$this->assertTrue($result);

		$canLogIn = $user->checkPassword($newUser['email'], "something else");
		$this->assertFalse($canLogIn);

		$canLogIn = $user->checkPassword($newUser['email'], $newUser['password']);
		$this->assertFalse($canLogIn);

		$canLogIn = $user->checkPassword($newUser['email'], $newPassword);
		$this->assertTrue($canLogIn);
	}

	public function testUserStorage()
	{
		$newUser = [
			"password" => "hello123!@#ABC",
			"email" => "user9@example.com",
			"hello" => "world"
		];
		$userObject = new User();
		$createdUser = $userObject->createUser($newUser);
		$storageUrl = "https://storage.example.com";
		$userObject->setStorage($createdUser['userId'], $storageUrl);

		$savedStorage = $userObject->getStorage($createdUser['userId']);

		$this->assertTrue(in_array($storageUrl, $savedStorage));

		$user = $userObject->getUser($newUser['email']);
		$this->assertTrue(in_array($storageUrl, $user['storage']));
	}

	public function testUserExistsById()
	{
		$newUser = [
			"password" => "hello123!@#ABC",
			"email" => "user10@example.com",
			"hello" => "world"
		];
		$user = new User();
		$createdUser = $user->createUser($newUser);

		$userExists = $user->userIdExists($createdUser['userId']);
		$this->assertTrue($userExists);
	}

	public function testUserDoesNotExistsById()
	{
		$user = new User();
		$userExists = $user->userIdExists("foo");
		$this->assertFalse($userExists);
	}

	public function testUserExistsByEmail()
	{
		$newUser = [
			"password" => "hello123!@#ABC",
			"email" => "user11@example.com",
			"hello" => "world"
		];
		$user = new User();
		$createdUser = $user->createUser($newUser);

		$userExists = $user->userEmailExists($newUser['email']);
		$this->assertTrue($userExists);
	}

	public function testUserDoesNotExistsByEmail()
	{
		$user = new User();
		$userExists = $user->userEmailExists("foo@example.com");
		$this->assertFalse($userExists);
	}

	public function testAllowClientForUser()
	{
		$newUser = [
			"password" => "hello123!@#ABC",
			"email" => "user11@example.com",
			"hello" => "world"
		];
		$userObject = new User();
		$createdUser = $userObject->createUser($newUser);

		$clientId = "12345";
		$result = $userObject->allowClientForUser($clientId, $createdUser['userId']);
		$this->assertTrue($result);

		$allowedClients = $userObject->getAllowedClients($createdUser['userId']);
		$this->assertTrue(in_array($clientId, $allowedClients));

		$user = $userObject->getUser($newUser['email']);
		$this->assertTrue(in_array($clientId, $user['allowedClients']));
	}

	public function testDeleteAccount()
	{
		$newUser = [
			"password" => "hello123!@#ABC",
			"email" => "user11@example.com",
			"hello" => "world"
		];
		$user = new User();
		$createdUser = $user->createUser($newUser);
		$clientId = "12345";
		$result = $user->allowClientForUser($clientId, $createdUser['userId']);

		$this->assertTrue($user->userIdExists($createdUser['userId']));
		$this->assertTrue($user->userEmailExists($newUser['email']));
		$allowedClients = $user->getAllowedClients($createdUser['userId']);
		$this->assertTrue(in_array($clientId, $allowedClients));

		$user->deleteAccount($newUser['email']);

		$this->assertFalse($user->userIdExists($createdUser['userId']));
		$this->assertFalse($user->userEmailExists($newUser['email']));
		$allowedClients = $user->getAllowedClients($createdUser['userId']);
		$this->assertEmpty($allowedClients);
	}

	public function testCleanup()
	{
		// empty the verify table first so we have dependable numbers
		$db = new Db();
		$query = $db::$pdo->prepare('DELETE FROM verify WHERE NOT code=""');
		$query->execute();

		$user = new User();
		$token1 = $user->saveVerifyToken("verify", [
			"hello" => "world",
			"expires" => time() - 10
		]);
		$query = $db::$pdo->prepare('SELECT count(*) AS count FROM verify');
		$query->execute();
		$result = $query->fetchAll();
		$beforeCleanup = $result[0]['count'];
		$this->assertEquals(1, $beforeCleanup);
		$user->cleanupTokens();
		$query = $db::$pdo->prepare('SELECT count(*) AS count FROM verify');
		$query->execute();
		$result = $query->fetchAll();
		$afterCleanup = $result[0]['count'];

		$this->assertEquals(0, $afterCleanup);
	}
}
