<?php
/**
 * Created by PhpStorm.
 * User: tom
 * Date: 25/06/2016
 * Time: 23:17
 */

namespace twhiston\tg\Argv;


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
                            'format' => null,
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
                            'pass' => null,
                        ],
                ]
        ];
    }

    public function testMerge()
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
        $argv = $merger->merge();

        $this->assertArrayHasKey('phpunit',$argv);


    }
}
