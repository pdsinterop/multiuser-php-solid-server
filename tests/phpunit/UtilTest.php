<?php
namespace Pdsinterop\PhpSolid\Tests;

use Pdsinterop\PhpSolid\Util;

class UtilTest extends \PHPUnit\Framework\TestCase
{
    public function testBase64EncodeDecode() {
        $string = "this is a test string with more stuffing";
        $encoded = Util::base64_url_encode($string);
        $decoded = Util::base64_url_decode($encoded);
        $this->assertEquals($string, $decoded);
    }
}
