<?php
    namespace Pdsinterop\PhpSolid;

    use Pdsinterop\PhpSolid\Db;

    const DBPATH = ":memory:";
    class DbTest extends \PHPUnit\Framework\TestCase
    {
        public function testConnect() {
            Db::connect();
            $this->assertInstanceOf("PDO", Db::$pdo);
        }
    }
