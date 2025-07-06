<?php
namespace Pdsinterop\PhpSolid\Tests;

use Pdsinterop\PhpSolid\PasswordValidator;

class PasswordValidatorTest extends \PHPUnit\Framework\TestCase
{
    public function testBaseLower()
    {
        $password = "aaa";
        $this->assertEquals(26, PasswordValidator::getBase($password));
    }

    public function testBaseUpper()
    {
        $password = "AAA";
        $this->assertEquals(26, PasswordValidator::getBase($password));
    }

    public function testBaseNumbers()
    {
        $password = "123";
        $this->assertEquals(10, PasswordValidator::getBase($password));
    }

    public function testBaseSpecial()
    {
        $password = "!@#$";
        $this->assertEquals(32, PasswordValidator::getBase($password));
    }

    public function testBaseUpperAndLower()
    {
        $password = "aaaAAA";
        $this->assertEquals(52, PasswordValidator::getBase($password));
    }

    public function testLengthLower()
    {
        $password = "aaa";
        $this->assertEquals(2, PasswordValidator::getLength($password));
    }

    public function testLengthUpper()
    {
        $password = "AAA";
        $this->assertEquals(2, PasswordValidator::getLength($password));
    }

    public function testLengthNumbers()
    {
        $password = "123";
        $this->assertEquals(3, PasswordValidator::getLength($password));
    }

    public function testLengthSpecial()
    {
        $password = "!@#$";
        $this->assertEquals(4, PasswordValidator::getLength($password));
    }

    public function testLengthUpperAndLower()
    {
        $password = "aaaAAA";
        $this->assertEquals(4, PasswordValidator::getLength($password));
    }

    public function testSimplyEntropy()
    {
        $values = [
            ["password" => "aaa", "expected" => 6.52],
            ["password" => "abc", "expected" => 9.77]
        ];

        foreach ($values as $value) {
            $this->assertEquals($value['expected'], PasswordValidator::getEntropy($value['password']));
        }
    }
}
