<?php

// phpcs:disable PSR1.Files.SideEffects.FoundWithSymbols

namespace Pdsinterop\PhpSolid;

require_once(__DIR__ . "/test-config.php");

use Pdsinterop\PhpSolid\Server;

class ServerTest extends \PHPUnit\Framework\TestCase
{
	public static $headers = [];
	public static $keys;

	protected function setUp(): void
	{
		$statements = [
			'DROP TABLE IF EXISTS clients',
			'CREATE TABLE clients (
				clientId VARCHAR(255) NOT NULL PRIMARY KEY,
				origin TEXT NOT NULL,
				clientData TEXT NOT NULL
			)'
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

		$clientRegistration = new ClientRegistration();
		$clientRegistration->saveClientRegistration([
			"client_id" => "1234",
			"origin" => "https://example.com",
			"redirect_uris" => ["https://example.com"],
			"client_name" => "Client name"
		]);
	}

	public function testGenerateKeySet()
	{
		$server = new Server();
		$keys = $server->generateKeySet();
		$this->assertTrue(isset($keys['encryptionKey']));
		$this->assertTrue(isset($keys['publicKey']));
		$this->assertTrue(isset($keys['privateKey']));
		$this->assertMatchesRegularExpression("/BEGIN PUBLIC KEY/", $keys['publicKey']);
		$this->assertMatchesRegularExpression("/BEGIN PRIVATE KEY/", $keys['privateKey']);
	}


	public function testGetAuthServer()
	{
		$server = new Server();
		$authServer = $server->getAuthServer();
		$this->assertInstanceOf('\Pdsinterop\Solid\Auth\Server', $authServer);
	}

	public function testGetAuthServerConfig()
	{
		$server = new Server();
		$authServerConfig = $server->getAuthServerConfig();
		$this->assertInstanceOf('\Pdsinterop\Solid\Auth\Config', $authServerConfig);
	}

	public function testGetConfigClient()
	{
		$server = new Server();
		$configClient = $server->getConfigClient();
		$this->assertInstanceOf('\Pdsinterop\Solid\Auth\Config\Client', $configClient);
	}

	public function testGetConfigClientWithGetId()
	{
		$_GET['client_id'] = '1234';
		$server = new Server();
		$configClient = $server->getConfigClient();
		$this->assertInstanceOf('\Pdsinterop\Solid\Auth\Config\Client', $configClient);
	}

	public function testGetConfigClientWithPostd()
	{
		$_POST['client_id'] = '1234';
		$server = new Server();
		$configClient = $server->getConfigClient();
		$this->assertInstanceOf('\Pdsinterop\Solid\Auth\Config\Client', $configClient);
	}
	public function testGetDpop()
	{
		$server = new Server();
		$dpop = $server->getDpop();
		$this->assertInstanceOf('\Pdsinterop\Solid\Auth\Utils\Dpop', $dpop);
	}
	public function testGetBearer()
	{
		$server = new Server();
		$bearer = $server->getBearer();
		$this->assertInstanceOf('\Pdsinterop\Solid\Auth\Utils\Bearer', $bearer);
	}
	public function testGetEndpoints()
	{
		$server = new Server();
		$endpoints = $server->getEndpoints();
		$this->assertEquals($endpoints["issuer"], "https://solid.example.com");
		$this->assertEquals($endpoints["jwks_uri"], "https://solid.example.com/jwks/");
		$this->assertEquals($endpoints["check_session_iframe"], "https://solid.example.com/session/");
		$this->assertEquals($endpoints["end_session_endpoint"], "https://solid.example.com/logout/");
		$this->assertEquals($endpoints["authorization_endpoint"], "https://solid.example.com/authorize/");
		$this->assertEquals($endpoints["token_endpoint"], "https://solid.example.com/token/");
		$this->assertEquals($endpoints["userinfo_endpoint"], "https://solid.example.com/userinfo/");
		$this->assertEquals($endpoints["registration_endpoint"], "https://solid.example.com/register/");
	}

	public function testGetKeys()
	{
		$server = new Server();
		$keys = $server->getKeys();
		$this->assertTrue(isset($keys['encryptionKey']));
		$this->assertTrue(isset($keys['publicKey']));
		$this->assertTrue(isset($keys['privateKey']));
		$this->assertMatchesRegularExpression("/BEGIN PUBLIC KEY/", $keys['publicKey']);
		$this->assertMatchesRegularExpression("/BEGIN PRIVATE KEY/", $keys['privateKey']);
	}

	public function testGetTokenGenerator()
	{
		$server = new Server();
		$tokenGenerator = $server->getTokenGenerator();
		$this->assertInstanceOf('\Pdsinterop\Solid\Auth\TokenGenerator', $tokenGenerator);
	}

	public function testRespond()
	{
		$response = new MockResponse();
		ob_start();
		$server = new Server();
		$server->respond($response);
		$sentBody = ob_get_contents();
		ob_end_clean();
		$this->assertTrue(in_array("HTTP/1.1 200", ServerTest::$headers));
		$this->assertTrue(in_array("Foo:Bar", ServerTest::$headers));
		$this->assertTrue(in_array("Foo:Blah", ServerTest::$headers));

		$this->assertEquals($sentBody, "{\n    \"Hello\": \"world\"\n}");
	}
}
