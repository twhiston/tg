<?php
/**
 * Created by PhpStorm.
 * User: tom
 * Date: 09/07/2016
 * Time: 14:36
 */

namespace twhiston\tg\tests;


use twhiston\tg\ClassCache;

class ClassCacheTest extends \PHPUnit_Framework_TestCase
{

    /** @var  ClassCache */
    protected $c;

    /** @var  string */
    protected $location;


    public function setUp()
    {
        parent::setUp();
        $this->c = new ClassCache();
        $this->location = __DIR__ . '/temp/';
        $this->c->setCachePath($this->location);
    }

    public function tearDown()
    {
        parent::tearDown();
        $this->deleteDirectory();
    }

    public function testGetPath()
    {
        $this->assertEquals($this->location, $this->c->getCachePath());
        $this->assertTrue(is_dir($this->location));
    }

    public function testSanitizePath()
    {
        $location = __DIR__ . '/temp';//note we pass it without the final /
        $this->c->setCachePath($location);
        $this->assertEquals($this->location, $this->c->getCachePath()); //and here the final / exists
    }

    public function testGetClasses()
    {
        $classes = $this->c->getClasses('Command', [__DIR__ . '/assets']);
        $this->assertCount(1, $classes);
        $this->assertEquals('twhiston\tg\Command\SymfonyTest', $classes[0]);
        $this->assertFileExists($this->location . 'CommandCacheMap.yml');

        $classes = $this->c->getClasses('RoboCommand', [__DIR__ . '/assets']);
        $this->assertCount(1, $classes);
        $this->assertEquals('twhiston\tg\RoboCommand\RoboTest', $classes[0]);
        $this->assertFileExists($this->location . 'RoboCommandCacheMap.yml');
    }

    public function testClearCache()
    {
        $classes = $this->c->getClasses('Command', [__DIR__ . '/assets']);
        $this->assertFileExists($this->location . 'CommandCacheMap.yml');

        $this->c->clearCache(['Command']);
        $this->assertFileNotExists($this->location . 'CommandCacheMap.yml');
    }

    private function deleteDirectory()
    {
        system('rm -rf ' . escapeshellarg($this->location), $retval);
        return $retval == 0; // UNIX commands return zero on success
    }
}
