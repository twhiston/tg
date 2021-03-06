<?php
/**
 * Created by PhpStorm.
 * User: tom
 * Date: 25/06/2016
 * Time: 23:17
 */

namespace twhiston\tg\tests\Argv;

use twhiston\tg\Argv\Merger;

class MergerTest extends \PHPUnit_Framework_TestCase
{

    protected $configfile;

    public function setUp()
    {
        $this->configfile = [
            'list' =>
                [
                    'args' =>
                        [
                            'namespace' => null,
                        ],
                    'options' =>
                        [
                            'raw' => null,
                            'format' => 'xml',
                        ],
                    'pass' => null,
                ],
            'phpunit' =>
                [
                    'watch' =>
                        [
                            'args' =>
                                [
                                    'path' => '/config/file/path',
                                    'unitArgs' => null,
                                ],
                            'options' => null,
                            'pass' => ["--configuration=phpunit.xml.dev"],
                        ],
                ]
        ];
    }

    public function testOptionMerge()
    {
        //Argv with pass through arguments
        $argv = [
            '/Users/tom/Sites/_MyCode/PHP/twRobo/src/tg',
            'list',
        ];


        $merger = new Merger();
        $merger->setArgs($argv, $this->configfile);
        $merged = $merger->merge();

        $this->assertCount(3, $merged);
        $this->assertEquals('--format=xml', $merged[2]);
    }

    public function testOptionMergeWithAdditional()
    {
        //Argv with pass through arguments
        $argv = [
            '/Users/tom/Sites/_MyCode/PHP/twRobo/src/tg',
            'list',
            '--raw'
        ];


        $merger = new Merger();
        $merger->setArgs($argv, $this->configfile);
        $merged = $merger->merge();

        $this->assertCount(4, $merged);
        $this->assertEquals('--raw', $merged[2]);
        $this->assertEquals('--format=xml', $merged[3]);
    }

    /**
     * In this instance the output should be the same as the input, as all the arguments are present in the argv, and it will override the config
     */
    public function testFullArgvSetMerge()
    {
        //Argv with pass through arguments
        $argv = [
            '/Users/tom/Sites/_MyCode/PHP/twRobo/src/tg',
            'phpunit:watch',
            './vendor/bin',
            '--',
            '--configuration=phpunit.xml.dist',
            '--coverage=clover',
        ];


        $merger = new Merger();
        $merger->setArgs($argv, $this->configfile);
        $merged = $merger->merge();

        $this->assertCount(6, $merged);
        $this->assertEquals($argv, $merged);
    }

    public function testParticalArgvSetMerge()
    {
        //Argv with pass through arguments
        $argv = [
            '/Users/tom/Sites/_MyCode/PHP/twRobo/src/tg',
            'phpunit:watch',
            './vendor/bin',
        ];


        $merger = new Merger();
        $merger->setArgs($argv, $this->configfile);
        $merged = $merger->merge();

        $this->assertCount(5, $merged);
        $this->assertEquals("--", $merged[3]);
        $this->assertEquals("--configuration=phpunit.xml.dev", $merged[4]);
    }

    public function testCommandOnlyArgvSetMerge()
    {
        //Argv with pass through arguments
        $argv = [
            '/Users/tom/Sites/_MyCode/PHP/twRobo/src/tg',
            'phpunit:watch'
        ];


        $merger = new Merger();
        $merger->setArgs($argv, $this->configfile);
        $merged = $merger->merge();

        $this->assertCount(5, $merged);
        $this->assertEquals("/config/file/path", $merged[2]);
        $this->assertEquals("--", $merged[3]);
        $this->assertEquals("--configuration=phpunit.xml.dev", $merged[4]);
    }
}
