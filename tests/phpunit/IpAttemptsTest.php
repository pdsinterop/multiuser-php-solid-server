<?php
namespace Pdsinterop\PhpSolid\Tests;

require_once(__DIR__ . "/test-config.php");

use Pdsinterop\PhpSolid\IpAttempts;
use Pdsinterop\PhpSolid\Db;

class IpAttemptsTest extends \PHPUnit\Framework\TestCase
{
    protected function setUp(): void
    {
        $statements = [
            'DROP TABLE IF EXISTS ipAttempts',
	    'CREATE TABLE IF NOT EXISTS ipAttempts (
		ip VARCHAR(255) NOT NULL,
		type VARCHAR(255) NOT NULL,
		expires NOT NULL
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
    }

    public function testGetAttemptsCountZero() {
        $ip = "10.0.0.1";
        $count = IpAttempts::getAttemptsCount($ip, "test");
        $this->assertEquals(0, $count);
    }

    public function testGetAttemptsCountOne() {
        $ip = "10.0.0.1";
        
        IpAttempts::logFailedAttempt($ip, "test", time() + 3600);
        $count = IpAttempts::getAttemptsCount($ip, "test");
        $this->assertEquals(1, $count);
    }

    public function testGetAttemptsCountTwo() {
        $ip = "10.0.0.1";
        
        IpAttempts::logFailedAttempt($ip, "test", time() + 3600);
        IpAttempts::logFailedAttempt($ip, "test", time() + 3600);

        $count = IpAttempts::getAttemptsCount($ip, "test");
        $this->assertEquals(2, $count);
    }

    public function testGetAttemptsCountExpired() {
        $ip = "10.0.0.1";
        
        IpAttempts::logFailedAttempt($ip, "test", time() - 1);
        IpAttempts::logFailedAttempt($ip, "test", time() - 1);

        $count = IpAttempts::getAttemptsCount($ip, "test");
        $this->assertEquals(0, $count);
    }

    public function testGetAttemptsCountOneExpired() {
        $ip = "10.0.0.1";
        
        IpAttempts::logFailedAttempt($ip, "test", time() + 10);
        IpAttempts::logFailedAttempt($ip, "test", time() - 1);

        $count = IpAttempts::getAttemptsCount($ip, "test");
        $this->assertEquals(1, $count);
    }

    public function testCleanup() {
        $ip = "10.0.0.1";
        
        IpAttempts::logFailedAttempt($ip, "test", time() - 1);
        IpAttempts::logFailedAttempt($ip, "test", time() - 1);
        IpAttempts::logFailedAttempt($ip, "test", time() + 3600);
        IpAttempts::logFailedAttempt($ip, "test", time() + 3600);

        $query = Db::$pdo->prepare('SELECT count(*) AS count FROM ipAttempts');
        $query->execute();
        $result = $query->fetchAll();
        $beforeCleanup = $result[0]['count'];

        $this->assertEquals(4, $beforeCleanup);
        
        IpAttempts::cleanupAttempts();
        $query = Db::$pdo->prepare('SELECT count(*) AS count FROM ipAttempts');
        $query->execute();
        $result = $query->fetchAll();
        $afterCleanup = $result[0]['count'];

        $this->assertEquals(2, $afterCleanup);
    }

    public function testTrustedIpGetAttempts() {
        $ip = "127.0.0.100"; // trusted IP

        IpAttempts::logFailedAttempt($ip, "test", time() + 3600);
        IpAttempts::logFailedAttempt($ip, "test", time() + 3600);

        $count = IpAttempts::getAttemptsCount($ip, "test");
        $this->assertEquals(0, $count);
    }

    public function testTrustedIpGetAttemptsSkipsDb() {
        $ip = "127.0.0.100"; // trusted IP

        IpAttempts::logFailedAttempt($ip, "test", time() + 3600);
        IpAttempts::logFailedAttempt($ip, "test", time() + 3600);

        $query = Db::$pdo->prepare('SELECT count(*) AS count FROM ipAttempts');
        $query->execute();
        $result = $query->fetchAll();
        $count = $result[0]['count'];
        $this->assertEquals(0, $count);
    }
}
