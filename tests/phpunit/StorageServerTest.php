<?php
    namespace Pdsinterop\PhpSolid;

    require_once(__DIR__ . "/test-config.php");

    use Pdsinterop\PhpSolid\StorageServer;
    
    class StorageServerTest extends \PHPUnit\Framework\TestCase
    {
        public static $headers = [];
        public static $createdUser;

        protected function setUp(): void
        {
            $statements = [
                'DROP TABLE IF EXISTS allowedClients',
                'DROP TABLE IF EXISTS userStorage',
                'DROP TABLE IF EXISTS users',
                'CREATE TABLE IF NOT EXISTS allowedClients (
                        userId VARCHAR(255) NOT NULL PRIMARY KEY,
                        clientId VARCHAR(255) NOT NULL
                )',
                'CREATE TABLE IF NOT EXISTS userStorage (
                        userId VARCHAR(255) NOT NULL PRIMARY KEY,
                        storageUrl VARCHAR(255) NOT NULL
                )',
                'CREATE TABLE IF NOT EXISTS users (
                        user_id VARCHAR(255) NOT NULL PRIMARY KEY,
                        email TEXT NOT NULL,
                        password TEXT NOT NULL,
                        data TEXT
                )',
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

            $newUser = [
                "password" => "hello123!@#ABC",
		"email" => "alice@example.com",
		"hello" => "world"
            ];
            self::$createdUser = User::createUser($newUser);
            $_SERVER['REQUEST_URI'] = "/test/";
            $_SERVER['REQUEST_SCHEME'] = "https";
            $_SERVER['SERVER_NAME'] = "storage-" . self::$createdUser['userId'] . ".example.com";
        }

        public function testGetFileSystem() {
            $filesystem = StorageServer::getFileSystem();
            $this->assertInstanceOf('\League\Flysystem\Filesystem', $filesystem);
        }

        
        public function testRespond() {
            $response = new MockResponse();
            ob_start();
            StorageServer::respond($response);
            $sentBody = ob_get_contents();
            ob_end_clean();
            $this->assertTrue(in_array("HTTP/1.1 200", StorageServerTest::$headers));
            $this->assertTrue(in_array("Foo:Bar", StorageServerTest::$headers));
            $this->assertTrue(in_array("Foo:Blah", StorageServerTest::$headers));
            
            $this->assertEquals($sentBody, "{\"Hello\":\"world\"}");
        }

        public function testGetOwner() {
            $owner = StorageServer::getOwner();
            $this->assertEquals(self::$createdUser['webId'], $owner['webId']);
            $this->assertEquals(self::$createdUser['email'], $owner['email']);
        }

        public function testGetOwnerWebId() {
            $webId = StorageServer::getOwnerWebId();
            $this->assertEquals(self::$createdUser['webId'], $webId);
        }

        public function testGenerateDefaultAcl() {
            $defaultAcl = StorageServer::generateDefaultAcl();
            $this->assertTrue(strpos($defaultAcl, self::$createdUser['webId']) > 0);
            $this->assertMatchesRegularExpression("/@prefix/", $defaultAcl);
        }

        public function testGeneratePublicAppendAcl() {
            $publicAppendAcl = StorageServer::generatePublicAppendAcl();
            $this->assertTrue(strpos($publicAppendAcl, self::$createdUser['webId']) > 0);
            $this->assertMatchesRegularExpression("/@prefix/", $publicAppendAcl);
        }

        public function testGeneratePublicReadAcl() {
            $publicReadAcl = StorageServer::generatePublicReadAcl();
            $this->assertTrue(strpos($publicReadAcl, self::$createdUser['webId']) > 0);
            $this->assertMatchesRegularExpression("/@prefix/", $publicReadAcl);
        }

        public function testGenerateDefaultPrivateTypeIndex() {
            $privateTypeIndex = StorageServer::generateDefaultPrivateTypeIndex();
            $this->assertTrue(strpos($privateTypeIndex, "UnlistedDocument") > 0);
            $this->assertMatchesRegularExpression("/@prefix/", $privateTypeIndex);
        }

        public function testGenerateDefaultPublicTypeIndex() {
            $publicTypeIndex = StorageServer::generateDefaultPublicTypeIndex();
            $this->assertTrue(strpos($publicTypeIndex, "ListedDocument") > 0);
            $this->assertMatchesRegularExpression("/@prefix/", $publicTypeIndex);
        }

        public function testGenerateDefaultPreferences() {
            $preferences = StorageServer::generateDefaultPreferences();
            $this->assertTrue(strpos($preferences, "ConfigurationFile") > 0);
            $this->assertMatchesRegularExpression("/@prefix/", $preferences);
        }

        /*
            Currently untested:
            public static function getWebId($rawRequest) {
            public static function initializeStorage() {
        */
    }

