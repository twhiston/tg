<?php
/**
 * Created by PhpStorm.
 * User: tom
 * Date: 18/06/2016
 * Time: 16:00
 */

namespace twhiston\tg;

use Robo\Config;
use Robo\Result;
use Robo\TaskInfo;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Yaml\Yaml;
use TgCommands;
use twhiston\tg\Argv\Merger;
use twhiston\tg\Commands\Init;
use twhiston\twLib\Discovery\FindByNamespace;

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

    /**
     * @var string current working directory
     */
    protected $dir;

    protected $passThroughArgs;

    protected $autoloader;


    public function __construct($autoloader)
    {
        $this->output = new ConsoleOutput();
        $this->dir = getcwd();
        $this->autoloader = $autoloader;
    }

    protected function mergeArgv($argv)
    {
        $fileName = TgCommands::TGCONFIG . '.yml';
        //Merge our args with our config file
        if (class_exists('TgCommands') && file_exists($fileName)) {
            $configFile = Yaml::parse(file_get_contents($fileName));

            $merger = new Merger();
            $merger->setArgs($argv, $configFile);
            $argv = $merger->merge();

        }
        return $argv;
    }


    /**
     * @param $argv
     * @return ArgvInput
     */
    protected function prepareInput($argv)
    {
        //Merge the input with the config file
        $argv = $this->mergeArgv($argv);

        $argv = $this->prepareRoboInput($argv);

        return new ArgvInput($argv);
    }


    protected function prepareRoboInput($argv)
    {
        if (!is_array($argv)) {
            return $argv;
        }
        $pos = array_search('--', $argv);

        // cutting pass-through arguments
        if ($pos !== false) {
            $this->passThroughArgs = implode(' ', array_slice($argv, $pos + 1));
            $argv = array_slice($argv, 0, $pos + 1);
            $argv[$pos] = 'passthrough';//replace -- with a solid arg for the command, this will later be replaced by the passthroughs
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
        return $argv;
    }

    protected function checkProjectInitialized()
    {
        $initialized = false;
        //This should always pass if the dir is the default cwd
        if (!file_exists($this->dir)) {
            $this->output->writeln("Path in `{$this->dir}` is invalid, please provide valid absolute path to load Robofile");
            return false;
        }

        $this->dir = realpath($this->dir);
        chdir($this->dir);

        //check for a command file.
        if ($this->checkForTgFile()) {
            if ($this->loadTxCommands()) {
                $initialized = true;
            } else {
                $this->output->writeln("<error>Class " . Tg::TGCLASS . " was not loaded</error>");
            }
        }

        return $initialized;

    }

    protected function checkForTgFile()
    {
        if (file_exists($this->dir . DIRECTORY_SEPARATOR . Tg::TGFILE)) {
            return true;
        }
        return false;
    }

    protected function loadTxCommands()
    {
        require_once $this->dir . DIRECTORY_SEPARATOR . Tg::TGFILE;
        if (!class_exists(Tg::TGCLASS)) {
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
        register_shutdown_function([$this, 'shutdown']);
        set_error_handler([$this, 'handleError']);

        $app = new Application('Tx', self::VERSION);

        if (!$this->checkProjectInitialized()) {
            $this->output->writeln("Tx is not initialized here. Will be initialized");
            $app->add(new Init('init'));
            $app->setDefaultCommand('tg:init');
            $this->input = $this->prepareInput($input ? $input : $_SERVER['argv']);
            $app->run($this->input, $this->output);
            return 0;
        }

        $this->input = $this->prepareInput($input ? $input : $_SERVER['argv']);

        //Load our TxCommands file
        $this->addCommandsFromClass($app, Tg::TGCLASS, $this->passThroughArgs);

        //Load our core commands
        $this->addRoboCommands(__DIR__, $app);
        $this->addSymfonyCommands(__DIR__, $app);

        //Load our autoloaded commands
        $this->addRoboCommands($this->autoloader, $app);
        $this->addSymfonyCommands($this->autoloader, $app);

        $app->setAutoExit(false);

        //Set up the robo static config class
        Config::setInput($this->input);
        Config::setOutput($this->output);

        return $app->run($this->input, $this->output);
    }

    protected function addRoboCommands($dir, $app)
    {
        //Load our core commands
        $finder = new FindByNamespace($dir);
        $classes = $finder->find('tg\\RoboCommand');
        foreach ($classes as $class) {
            $this->addCommandsFromClass($app, $class, $this->passThroughArgs);
        }
    }

    protected function addSymfonyCommands($dir, $app)
    {
        $finder = new FindByNamespace($dir);
        $classes = $finder->find('tg\\Command');
        foreach ($classes as $class) {
            $app->add(new $class);
        }
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
        $this->output->writeln(
            sprintf("<error>ERROR: %s \nin %s:%d\n</error>", $error['message'], $error['file'], $error['line'])
        );
    }

    public function handleError()
    {
        if (error_reporting() === 0) {
            return true;
        }
        return false;
    }


}