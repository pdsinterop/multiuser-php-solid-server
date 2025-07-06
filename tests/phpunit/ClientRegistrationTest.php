<?php
namespace Pdsinterop\PhpSolid\Tests;

require_once(__DIR__ . "/test-config.php");

use Pdsinterop\PhpSolid\ClientRegistration;
use Pdsinterop\PhpSolid\Db;

class ClientRegistrationTest extends \PHPUnit\Framework\TestCase
{
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
            "client_id" => "12345",
            "origin" => "https://example.com",
            "client_name" => "Client name"
        ]);

        ClientRegistration::saveClientRegistration([
            "client_id" => "23456",
            "origin" => "https://example2.com"
        ]);

        ClientRegistration::saveClientRegistration([
            "client_id" => "34567",
            "origin" => "https://example2.com"
        ]);
    }

    public function testGetRegistration() {
        $storedData = ClientRegistration::getRegistration("12345");
        $this->assertEquals("12345", $storedData['client_id']);
        $this->assertEquals("https://example.com", $storedData['origin']);
        $this->assertEquals("Client name", $storedData['client_name']);
    }

    public function testClientNameAutofill() {
        $storedData = ClientRegistration::getRegistration("23456");
        $this->assertEquals("23456", $storedData['client_id']);
        $this->assertEquals("https://example2.com", $storedData['origin']);
        $this->assertEquals("https://example2.com", $storedData['client_name']);
    }

    public function testClientByOrigin() {
        $storedData = ClientRegistration::getClientByOrigin("https://example.com");
        $this->assertEquals("12345", $storedData['client_id']);
        $this->assertEquals("https://example.com", $storedData['origin']);
        $this->assertEquals("Client name", $storedData['client_name']);
    }

    public function testClientByDuplicateOrigin() {
        $storedData = ClientRegistration::getClientByOrigin("https://example2.com");
        $this->assertFalse($storedData); // false because we have 2 clients with the same origin
    }
}
