<?php
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

            Db::connect();
            try {
                // create tables
                foreach($statements as $statement){
                    Db::$pdo->exec($statement);
                }
            } catch(\PDOException $e) {
                echo $e->getMessage();
            }

            ClientRegistration::saveClientRegistration([
                "client_id" => "1234",
                "origin" => "https://example.com",
                "redirect_uris" => ["https://example.com"],
                "client_name" => "Client name"
            ]);
        }

        public function testGenerateKeySet() {
            $keys = Server::generateKeySet();
            $this->assertTrue(isset($keys['encryptionKey']));
            $this->assertTrue(isset($keys['publicKey']));
            $this->assertTrue(isset($keys['privateKey']));
            $this->assertMatchesRegularExpression("/BEGIN PUBLIC KEY/", $keys['publicKey']);
            $this->assertMatchesRegularExpression("/BEGIN PRIVATE KEY/", $keys['privateKey']);
        }
        

        public function testGetAuthServer() {
            $authServer = Server::getAuthServer();
            $this->assertInstanceOf('\Pdsinterop\Solid\Auth\Server', $authServer);
        }

        public function testGetAuthServerConfig() {
            $authServerConfig = Server::getAuthServerConfig();
            $this->assertInstanceOf('\Pdsinterop\Solid\Auth\Config', $authServerConfig);
        }

        public function testGetConfigClient() {
            $configClient = Server::getConfigClient();
            $this->assertInstanceOf('\Pdsinterop\Solid\Auth\Config\Client', $configClient);
        }

        public function testGetConfigClientWithGetId() {
            $_GET['client_id'] = '1234';
            $configClient = Server::getConfigClient();
            $this->assertInstanceOf('\Pdsinterop\Solid\Auth\Config\Client', $configClient);
        }

        public function testGetConfigClientWithPostd() {
            $_POST['client_id'] = '1234';
            $configClient = Server::getConfigClient();
            $this->assertInstanceOf('\Pdsinterop\Solid\Auth\Config\Client', $configClient);
        }
        public function testGetDpop() {
            $dpop = Server::getDpop();
            $this->assertInstanceOf('\Pdsinterop\Solid\Auth\Utils\Dpop', $dpop);
        }
        public function testGetBearer() {
            $bearer = Server::getBearer();
            $this->assertInstanceOf('\Pdsinterop\Solid\Auth\Utils\Bearer', $bearer);
        }
        public function testGetEndpoints() {
            $endpoints = Server::getEndpoints();
            $this->assertEquals($endpoints["issuer"], "https://solid.example.com");
            $this->assertEquals($endpoints["jwks_uri"], "https://solid.example.com/jwks/");
            $this->assertEquals($endpoints["check_session_iframe"], "https://solid.example.com/session/");
            $this->assertEquals($endpoints["end_session_endpoint"], "https://solid.example.com/logout/");
            $this->assertEquals($endpoints["authorization_endpoint"], "https://solid.example.com/authorize/");
            $this->assertEquals($endpoints["token_endpoint"], "https://solid.example.com/token/");
            $this->assertEquals($endpoints["userinfo_endpoint"], "https://solid.example.com/userinfo/");
            $this->assertEquals($endpoints["registration_endpoint"], "https://solid.example.com/register/");
        }

        public function testGetKeys() {
            $keys = Server::getKeys();
            $this->assertTrue(isset($keys['encryptionKey']));
            $this->assertTrue(isset($keys['publicKey']));
            $this->assertTrue(isset($keys['privateKey']));
            $this->assertMatchesRegularExpression("/BEGIN PUBLIC KEY/", $keys['publicKey']);
            $this->assertMatchesRegularExpression("/BEGIN PRIVATE KEY/", $keys['privateKey']);
        }
        
        public function testGetTokenGenerator() {
            $tokenGenerator = Server::getTokenGenerator();
            $this->assertInstanceOf('\Pdsinterop\Solid\Auth\TokenGenerator', $tokenGenerator);
        }
        
        public function testRespond() {
            $response = new MockResponse();
            ob_start();
            Server::respond($response);
            $sentBody = ob_get_contents();
            ob_end_clean();
            $this->assertTrue(in_array("HTTP/1.1 200", ServerTest::$headers));
            $this->assertTrue(in_array("Foo:Bar", ServerTest::$headers));
            $this->assertTrue(in_array("Foo:Blah", ServerTest::$headers));
            
            $this->assertEquals($sentBody, "{\n    \"Hello\": \"world\"\n}");
        }
    }

