<?php
namespace Pdsinterop\PhpSolid;

const DBPATH = ":memory:";
const TRUSTED_IPS = ['127.0.0.100'];
const BANNED_PASSWORDS = [];
const MINIMUM_PASSWORD_ENTROPY = 10;
const BASEDOMAIN = "solid.example.com";
const BASEURL = "https://solid.example.com";
const PUBSUB_SERVER = "https://localhost:1234";
const KEYDIR = "php://memory/";
const STORAGEBASE = ".";

function header($header) {
    if (class_exists('Pdsinterop\PhpSolid\MiddleWareTest')) {
        MiddleWareTest::$headers[] = $header;
    }
    if (class_exists('Pdsinterop\PhpSolid\ServerTest')) {
        ServerTest::$headers[] = $header;
    }
    if (class_exists('Pdsinterop\PhpSolid\StorageServerTest')) {
        StorageServerTest::$headers[] = $header;
    }
}

function file_get_contents($file) {
    if (class_exists('Pdsinterop\PhpSolid\ServerTest')) {
        if(!isset(ServerTest::$keys)) {
            ServerTest::$keys = Server::generateKeySet();
        }
        if (preg_match("/encryption/", $file)) {
            return ServerTest::$keys['encryptionKey'];
        }
        if (preg_match("/public/", $file)) {
            return ServerTest::$keys['publicKey'];
        }
        if (preg_match("/private/", $file)) {
            return ServerTest::$keys['privateKey'];
        }
    }
}

class MockBody {
    public function rewind() {
        return true;
    }
    public function getContents() {
        return json_encode(["Hello" => "world"]);
    }
}

class MockResponse {
    public function getStatusCode() {
        return 200;
    }
    public function getBody() {
        return new MockBody();
    }
    public function getHeaders() {
        return [
            "Foo" => ["Bar", "Blah"]
        ];
    }
}
