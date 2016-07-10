<?php
/**
 * Created by PhpStorm.
 * User: tom
 * Date: 09/07/2016
 * Time: 23:22
 */

namespace twhiston\tg\tests;

use twhiston\tg\Command\SymfonyTest;
use twhiston\tg\CommandLoader;

class CommandLoaderTest extends \PHPUnit_Framework_TestCase
{

    protected $app;

    public function setUp()
    {
        $app = $this->createMock('Symfony\\Component\\Console\\Application');
        $app->method('add');
        $this->app = $app;
    }

    public function testAddSymfonyCommands()
    {
        $cl = new CommandLoader($this->app);
        $st = new SymfonyTest();
        $commands = $cl->createSymfonyCommands([$st]);
        $this->assertCount(1, $commands);
        $this->assertInstanceOf('twhiston\tg\Command\SymfonyTest', $commands[0]);
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage Command not found: /i/dont/exist
     */
    public function testAddNoneExistantSymfonyCommands()
    {
        $cl = new CommandLoader($this->app);
        $st = '/i/dont/exist';
        $commands = $cl->createSymfonyCommands([$st]);
    }

    public function testFindAndInclude()
    {
        $cl = new CommandLoader($this->app);
        $cl->setVendorExtension('/test_localVendor');
        $st = 'twhiston\tg\RoboCommand\NoLoadRoboTest';
        $commands = $cl->createRoboCommands([$st]);
        $this->assertCount(1, $commands);
        $this->assertInstanceOf('Symfony\\Component\\Console\\Command\\Command', $commands[0]);
    }


    public function testAddRoboCommands()
    {
        $cl = new CommandLoader($this->app);
        $st = 'twhiston\tg\RoboCommand\RoboTest';
        $commands = $cl->createRoboCommands([$st]);
        $this->assertCount(1, $commands);
        $this->assertInstanceOf('Symfony\\Component\\Console\\Command\\Command', $commands[0]);
    }

}
