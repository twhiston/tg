<?php
/**
 * Created by PhpStorm.
 * User: tom
 * Date: 18/06/2016
 * Time: 16:00
 */

namespace twhiston\tg;

use Robo\Result;
use Robo\TaskInfo;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\ConsoleOutput;
use twhiston\twLib\Discovery\FindByNamespace;
use twhiston\tg\Commands\Init;

/**
 * Class Tx
 * @package twhiston\tg
 */
class Tg
{

    const VERSION = '0.1.0';

    const TGCLASS = 'TgCommands';

    const TGFILE = Tg::TGCLASS . '.php';

    protected $output;

    protected $input;

    protected $dir;

    protected $passThroughArgs;

    protected $autoloader;


    public function __construct($autoloader)
    {
        $this->output = new ConsoleOutput();
        $this->dir = getcwd();
        $this->autoloader = $autoloader;
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
            $this->passThroughArgs = implode(' ', array_slice($argv, $pos + 1));
            $argv = array_slice($argv, 0, $pos);
        }

        // loading from other directory
        $pos = array_search('--load-from', $argv);
        if ($pos !== false) {
            if (isset($argv[$pos + 1])) {
                $this->dir = $argv[$pos + 1];
                unset($argv[$pos + 1]);
            }
            unset($argv[$pos]);
        }
        return new ArgvInput($argv);
    }

    protected function loadTxCommands()
    {

        if (!file_exists($this->dir)) {
            $this->output->writeln("Path in `{$this->dir}` is invalid, please provide valid absolute path to load Robofile");
            return false;
        }

        $this->dir = realpath($this->dir);
        chdir($this->dir);

        if (!file_exists($this->dir . DIRECTORY_SEPARATOR . Tg::TGFILE)) {
            return false;
        }

        require_once $this->dir . DIRECTORY_SEPARATOR . Tg::TGFILE;

        if (!class_exists(Tg::TGCLASS)) {
            $this->writeln("<error>Class " . $this->roboClass . " was not loaded</error>");
            return false;
        }
        return true;
    }


    /**
     * @param null $input Input
     * @return int|void
     * @throws \Exception
     *
     * Run tg and return an error code
     */
    public function run($input = null)
    {
        register_shutdown_function(array($this, 'shutdown'));
        set_error_handler(array($this, 'handleError'));


        $this->input = $this->prepareInput($input ? $input : $_SERVER['argv']);
        $app = new Application('Tx', self::VERSION);

        if (!$this->loadTxCommands()) {
            $this->output->writeln("Tx is not initialized here. Will be initialized");
            $app->add(new Init('init'));
            $app->setDefaultCommand('tg:init');
            $app->run($this->input, $this->output);
            return 0;
        }

        //Load our TxCommands file
        $this->addCommandsFromClass($app, Tg::TGCLASS, $this->passThroughArgs);

        //Load our core commands
        $finder = new FindByNamespace(__DIR__);
        $classes = $finder->find('tg\\RoboCommand');
        foreach ($classes as $class) {
            $this->addCommandsFromClass($app, $class, $this->passThroughArgs);
        }

        //Load our autoloaded commands
        $finder = new FindByNamespace($this->autoloader);
        $classes = $finder->find('tg\\RoboCommand');
        foreach ($classes as $class) {
            $this->addCommandsFromClass($app, $class, $this->passThroughArgs);
        }

        $app->setAutoExit(false);
        return $app->run($input);
    }


    /**
     * @param Application $app
     * @param $className
     * @param null $passThrough
     */
    public function addCommandsFromClass(Application &$app, $className, $passThrough = null)
    {
        $roboTasks = new $className;

        $commandNames = array_filter(get_class_methods($className), function ($m) {
            return !in_array($m, ['__construct']);
        });

        foreach ($commandNames as $commandName) {
            $classParts = explode('\\', $className);
            $final = array_pop($classParts);
            $cleanName = strtolower($final);
            $command = $this->createCommand($cleanName, new TaskInfo($className, $commandName));
            $command->setCode(function (InputInterface $input) use ($roboTasks, $commandName, $passThrough) {
                // get passthru args
                $args = $input->getArguments();
                array_shift($args);
                if ($passThrough) {
                    $args[key(array_slice($args, -1, 1, true))] = $passThrough;
                }
                $args[] = $input->getOptions();

                $res = call_user_func_array([$roboTasks, $commandName], $args);
                if (is_int($res)) {
                    exit($res);
                }
                if (is_bool($res)) {
                    exit($res ? 0 : 1);
                }
                if ($res instanceof Result) {
                    exit($res->getExitCode());
                }
            });
            $app->add($command);
        }
    }

    public function createCommand($className, TaskInfo $taskInfo)
    {

        if ($className === strtolower(Tg::TGCLASS)) {
            $name = $taskInfo->getName();
        } else {
            $camel = preg_replace("/:/", '-', $taskInfo->getName());
            $name = $className . ':' . $camel;
        }

        $task = new Command($name);
        $task->setDescription($taskInfo->getDescription());
        $task->setHelp($taskInfo->getHelp());

        $args = $taskInfo->getArguments();
        foreach ($args as $name => $val) {
            $description = $taskInfo->getArgumentDescription($name);
            if ($val === TaskInfo::PARAM_IS_REQUIRED) {
                $task->addArgument($name, InputArgument::REQUIRED, $description);
            } elseif (is_array($val)) {
                $task->addArgument($name, InputArgument::IS_ARRAY, $description, $val);
            } else {
                $task->addArgument($name, InputArgument::OPTIONAL, $description, $val);
            }
        }
        $opts = $taskInfo->getOptions();
        foreach ($opts as $name => $val) {
            $description = $taskInfo->getOptionDescription($name);

            $fullName = $name;
            $shortcut = '';
            if (strpos($name, '|')) {
                list($fullName, $shortcut) = explode('|', $name, 2);
            }

            if (is_bool($val)) {
                $task->addOption($fullName, $shortcut, InputOption::VALUE_NONE, $description);
            } else {
                $task->addOption($fullName, $shortcut, InputOption::VALUE_OPTIONAL, $description, $val);
            }
        }

        return $task;
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

    public function handleError()
    {
        if (error_reporting() === 0) {
            return true;
        }
        return false;
    }


}