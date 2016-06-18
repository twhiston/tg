<?php
/**
 * Created by PhpStorm.
 * User: tom
 * Date: 18/06/2016
 * Time: 16:00
 */

namespace twhiston\tx;

use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use twhiston\twLib\Discovery\FindByNamespace;
use twhiston\tx\Commands\Init;

/**
 * Class Tx
 * @package twhiston\tx
 */
class Tx
{

    protected $output;

    protected $input;

    protected $dir;

    const VERSION = '0.1.0';

    const TXFILE = 'TxCommands.php';


    public function __construct()
    {
        $this->output = new ConsoleOutput();
        $this->dir = __DIR__;

    }


    /**
     * @param $argv
     * @return ArgvInput
     */
    protected function prepareInput($argv)
    {
        $pos = array_search('--', $argv);

        // cutting pass-through arguments
        if ($pos !== false) {
            $this->passThroughArgs = implode(' ', array_slice($argv, $pos+1));
            $argv = array_slice($argv, 0, $pos);
        }

        // loading from other directory
        $pos = array_search('--load-from', $argv);
        if ($pos !== false) {
            if (isset($argv[$pos +1])) {
                $this->dir = $argv[$pos +1];
                unset($argv[$pos +1]);
            }
            unset($argv[$pos]);
        }
        return new ArgvInput($argv);
    }

    protected function loadRoboFile()
    {
        if (!file_exists($this->dir)) {
            $this->output->writeln("Path in `{$this->dir}` is invalid, please provide valid absolute path to load Robofile");
            return false;
        }

        $this->dir = realpath($this->dir);
        chdir($this->dir);

        if (!file_exists($this->dir . DIRECTORY_SEPARATOR . Tx::TXFILE)) {
            return false;
        }

        require_once $this->dir . DIRECTORY_SEPARATOR .Tx::TXFILE;

        if (!class_exists($this->roboClass)) {
            $this->writeln("<error>Class ".$this->roboClass." was not loaded</error>");
            return false;
        }
        return true;
    }


    /**
     * @param null $input Input
     * @return int|void
     * @throws \Exception
     *
     * Run tx and return an error code
     */
    public function run($input = null)
    {
        register_shutdown_function(array($this, 'shutdown'));
        set_error_handler(array($this, 'handleError'));


        $this->input = $this->prepareInput($input ? $input : $_SERVER['argv']);
        $app = new Application('Tx', self::VERSION);

        if (!$this->loadRoboFile()) {
            $this->output->writeln("Tx is not initialized here. Will be initialized");
            $app->add(new Init('init'));
            $app->setDefaultCommand('tx:init');
            $app->run($this->input, $this->output);
            return 0;
        }

        //Load our commands
        $finder = new FindByNamespace(__DIR__.'/../vendor');

        $app->addCommandsFromClass($this->roboClass, $this->passThroughArgs);
        $app->setAutoExit(false);
        return $app->run($input);
    }

    public function shutdown()
    {
        $error = error_get_last();
        if (!is_array($error)) {
            return;
        }
        $this->output->writeln(sprintf("<error>ERROR: %s \nin %s:%d\n</error>", $error['message'], $error['file'],
            $error['line']));
    }


}