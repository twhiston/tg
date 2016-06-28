<?php
/**
 * Created by PhpStorm.
 * User: tom
 * Date: 28/06/2016
 * Time: 20:19
 */

namespace twhiston\tg;

use Symfony\Component\Console\Output\Output;


class TestOutput extends Output
{
    protected $messages;

    public function __construct(
        $verbosity = self::VERBOSITY_NORMAL,
        $decorated = false,
        OutputFormatterInterface $formatter = null
    ) {
        parent::__construct($verbosity, $decorated, $formatter);
        $this->messages = [];
    }

    /**
     * @return mixed
     */
    public function getMessages()
    {
        return $this->messages;
    }

    protected function doWrite($message, $newline)
    {
        $this->messages[] = $message;
    }

}


/**
 * Class TgTest
 * @package twhiston\tg
 */
class TgTest extends \PHPUnit_Framework_TestCase
{

    protected $vendorDir;

    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);

        $this->vendorDir = substr($_SERVER['IDE_PHPUNIT_CUSTOM_LOADER'], 0,
            strrpos($_SERVER['IDE_PHPUNIT_CUSTOM_LOADER'], '/')
        );

    }

    public function tearDown()
    {
        parent::tearDown();

    }

    public function setUp()
    {
        parent::setUp(); // TODO: Change the autogenerated stub
    }


    /**
     * test that the default commands can be retrieved
     */
    public function testGetRegisteredCommands()
    {

        $tg = new Tg($this->vendorDir);
        $commands = $tg->getRegisteredCommands();
        $this->assertArrayHasKey('help', $commands);
        $this->assertArrayHasKey('list', $commands);
    }

    /**
     * Add some Robo commands
     */
    public function testAddRoboCommands()
    {
        $tg = new Tg($this->vendorDir);
        $tg->addRoboCommands(__DIR__ . '/assets');
        $commands = $tg->getRegisteredCommands();
        $this->assertArrayHasKey('robotest:watch-change', $commands);
    }

    /**
     * Add some symfony commands
     */
    public function testAddCommands()
    {
        $tg = new Tg($this->vendorDir);
        $tg->addSymfonyCommands(__DIR__ . '/assets');
        $commands = $tg->getRegisteredCommands();
        $this->assertArrayHasKey('ctest:test', $commands);
    }

    /**
     * @outputBuffering enabled
     */
    public function testRun()
    {
        $tg = new Tg($this->vendorDir);
        $testOut = new TestOutput();
        $tg->setOutput($testOut);
        $tg->addRoboCommands(__DIR__ . '/assets');
        $tg->addSymfonyCommands(__DIR__ . '/assets');
        $tg->run(['tg', 'ctest:test']);
        $messages = $testOut->getMessages();
        $this->assertEquals(
            'Tg is not initialized. run tg:init to create a project specific command file',
            $messages[0]
        );
        $this->assertEquals(
            'Testing',
            $messages[1]
        );
    }

}
