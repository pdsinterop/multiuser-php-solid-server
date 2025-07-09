<?php
    namespace Pdsinterop\PhpSolid;

    require_once(__DIR__ . "/test-config.php");

    use Pdsinterop\PhpSolid\Middleware;

    class MiddlewareTest extends \PHPUnit\Framework\TestCase
    {
        public static $headers = [];
        public function testCors() {
            Middleware::cors();
            $this->assertTrue(in_array("Access-Control-Allow-Origin: *", self::$headers));
            $this->assertTrue(in_array("Access-Control-Allow-Headers: *, allow, accept, authorization, content-type, dpop, slug, link", self::$headers));
            $this->assertTrue(in_array("Access-Control-Allow-Methods: GET, PUT, POST, OPTIONS, DELETE, PATCH", self::$headers));
            $this->assertTrue(in_array("Access-Control-Max-Age: 1728000", self::$headers));
            $this->assertTrue(in_array("Access-Control-Allow-Credentials: true", self::$headers));
            $this->assertTrue(in_array("Accept-Patch: text/n3", self::$headers));
            $this->assertTrue(in_array("Access-Control-Expose-Headers: Authorization, User, Location, Link, Vary, Last-Modified, ETag, Accept-Patch, Accept-Post, Updates-Via, Allow, WAC-Allow, Content-Length, WWW-Authenticate, MS-Author-Via", self::$headers));
        }

        public function testCorsWithOrigin() {
            $origin = "https://example.com";
            $_REQUEST['HTTP_ORIGIN'] = $origin;

            Middleware::cors();
            $this->assertTrue(in_array("Access-Control-Allow-Origin: $origin", self::$headers));
            $this->assertTrue(in_array("Access-Control-Allow-Headers: *, allow, accept, authorization, content-type, dpop, slug, link", self::$headers));
            $this->assertTrue(in_array("Access-Control-Allow-Methods: GET, PUT, POST, OPTIONS, DELETE, PATCH", self::$headers));
            $this->assertTrue(in_array("Access-Control-Max-Age: 1728000", self::$headers));
            $this->assertTrue(in_array("Access-Control-Allow-Credentials: true", self::$headers));
            $this->assertTrue(in_array("Accept-Patch: text/n3", self::$headers));
            $this->assertTrue(in_array("Access-Control-Expose-Headers: Authorization, User, Location, Link, Vary, Last-Modified, ETag, Accept-Patch, Accept-Post, Updates-Via, Allow, WAC-Allow, Content-Length, WWW-Authenticate, MS-Author-Via", self::$headers));
        }

        public function testPubSub() {
            Middleware::pubsub();
            $this->assertTrue(in_array("updates-via: " . PUBSUB_SERVER, self::$headers));
        }
    }
