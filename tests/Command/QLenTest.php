<?php
namespace Disque\Test\Command;

use PHPUnit_Framework_TestCase;
use Disque\Command\CommandInterface;
use Disque\Command\QLen;
use Disque\Exception\InvalidCommandArgumentException;
use Disque\Exception\InvalidCommandResponseException;

class QLenTest extends PHPUnit_Framework_TestCase
{
    public function testInstance()
    {
        $c = new QLen();
        $this->assertInstanceOf(CommandInterface::class, $c);
    }

    public function testBuildInvalidArgumentsEmpty()
    {
        $this->setExpectedException(InvalidCommandArgumentException::class, 'Invalid command arguments. Arguments for command Disque\\Command\\QLen: []');
        $c = new QLen();
        $c->build([]);
    }

    public function testBuildInvalidArgumentsEmptyTooMany()
    {
        $this->setExpectedException(InvalidCommandArgumentException::class, 'Invalid command arguments. Arguments for command Disque\\Command\\QLen: ["test","stuff"]');
        $c = new QLen();
        $c->build(['test', 'stuff']);
    }

    public function testBuildInvalidArgumentsEmptyNonNumeric()
    {
        $this->setExpectedException(InvalidCommandArgumentException::class, 'Invalid command arguments. Arguments for command Disque\\Command\\QLen: {"test":"stuff"}');
        $c = new QLen();
        $c->build(['test' => 'stuff']);
    }

    public function testBuildInvalidArgumentsNumericNon0()
    {
        $this->setExpectedException(InvalidCommandArgumentException::class, 'Invalid command arguments. Arguments for command Disque\\Command\\QLen: {"1":"stuff"}');
        $c = new QLen();
        $c->build([1 => 'stuff']);
    }

    public function testBuildInvalidArgumentsNonString()
    {
        $this->setExpectedException(InvalidCommandArgumentException::class, 'Invalid command arguments. Arguments for command Disque\\Command\\QLen: [false]');
        $c = new QLen();
        $c->build([false]);
    }

    public function testBuild()
    {
        $c = new QLen();
        $result = $c->build(['test']);
        $this->assertSame(['QLEN', 'test'], $result);
    }

    public function testParseInvalidNonNumericArray()
    {
        $this->setExpectedException(InvalidCommandResponseException::class, 'Invalid command response. Command Disque\\Command\\QLen got: ["test"]');
        $c = new QLen();
        $c->parse(['test']);
    }

    public function testParseInvalidNonNumericString()
    {
        $this->setExpectedException(InvalidCommandResponseException::class, 'Invalid command response. Command Disque\\Command\\QLen got: "test"');
        $c = new QLen();
        $c->parse('test');
    }

    public function testParse()
    {
        $c = new QLen();
        $result = $c->parse('128');
        $this->assertSame(128, $result);
    }
}