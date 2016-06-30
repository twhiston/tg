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
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;
use TgCommands;
use twhiston\tg\Argv\Merger;
use twhiston\twLib\Discovery\FindByNamespace;

/**
 * Class Tx
 * @package twhiston\tg
 */
class Tg
{

    /**
     * App Version
     */
    const VERSION = '0.1.0';

    /**
     * Expected class for project specific Command file
     */
    const TGCLASS = 'TgCommands';

    /**
     * Filename of project specific command files
     */
    const TGFILE = Tg::TGCLASS . '.php';

    /**
     * @var OutputInterface
     */
    protected $output;

    /**
     * @var InputInterface
     */
    protected $input;

    /**
     * @var string current working directory
     */
    protected $dir;

    /**
     * @var string pass through arguments sent to command, dealt with in a 'Robo' way
     */
    protected $passThroughArgs;

    /**
     * @var string
     */
    protected $vendorPath;

    /**
     * @var FindByNamespace
     */
    protected $finder;


    /**
     * @var Application
     */
    protected $app;


    /**
     * Tg constructor.
     * @param $autoloader
     */
    public function __construct($vendorPath)
    {
        $this->output = new ConsoleOutput();
        $this->finder = new FindByNamespace();
        $this->dir = getcwd();
        $this->vendorPath = $vendorPath;
        //Start the app here as this gives the opportunity to add extra classes by calling the add methods before run
        $this->app = new Application('Tg', self::VERSION);
    }

    /**
     * @param OutputInterface $output
     */
    public function setOutput(OutputInterface $output)
    {
        $this->output = $output;
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

        $this->input = $this->prepareInput($input ? $input : $_SERVER['argv']);

        if (!$this->checkProjectInitialized()) {
            if ($this->input->getFirstArgument() !== 'tg:init') {
                $this->output->writeln(
                    "<comment>Tg is not initialized. run tg:init to create a project specific command file</comment>"
                );
            }
        } else {
            //Load our TxCommands file
            $this->addCommandsFromClass(Tg::TGCLASS, $this->passThroughArgs);
        }

        //Load our core commands that are a part of the app
        $locations = [__DIR__, $this->vendorPath];
        $this->loadCommandsFromClasses($locations);

        $this->loadDynamicPaths();

        $this->app->setAutoExit(false);

        //Set up the robo static config class :(
        Config::setInput($this->input);
        Config::setOutput($this->output);

        return $this->app->run($this->input, $this->output);
    }

    protected function loadDynamicPaths()
    {
        //Load the dynamic paths
        if (file_exists($this->dir . '/vendor')) {
            $locations = [$this->dir . '/vendor'];
            $this->loadCommandsFromClasses($locations, true);
        }
    }

    public function loadCommandsFromClasses(array $locations, $bypassCache = false)
    {
        $classes = $this->getClasses('RoboCommand', $locations, $bypassCache);
        $this->addRoboCommands($classes);

        $classes = $this->getClasses('Command', $locations, $bypassCache);
        $this->addSymfonyCommands($classes);
    }

    protected function getClasses($type, array $locations, $bypassCache = false)
    {
        $classes = null;
        if (!$bypassCache) {
            $classes = $this->hasCacheMap($type);
        }
        if (!$classes) {
            $classes = [];
            foreach ($locations as $location) {
                $classes = array_merge($classes, $this->findClasses($location, 'tg\\' . $type));
            }
            if (!$bypassCache) {
                $this->saveCacheMap($type, $classes);
            }
        }
        return $classes;
    }

    /**
     * @param $dir
     */
    protected function addRoboCommands($classes)
    {
        //Load our core commands
        foreach ($classes as $class) {
            $this->addCommandsFromClass($class, $this->passThroughArgs);
        }
    }

    /**
     * @param $dir
     */
    protected function addSymfonyCommands($classes)
    {
        foreach ($classes as $class) {
            $this->app->add(new $class);
        }
    }

    protected function hasCacheMap($cachename)
    {
        if (file_exists(__DIR__ . "/" . $cachename . 'CacheMap.yml')) {
            return $this->getCacheMap($cachename);
        }
        return null;
    }

    private function getCacheMap($cachename)
    {
        return Yaml::parse(file_get_contents(__DIR__ . "/" . $cachename . 'CacheMap.yml'));
    }

    protected function saveCacheMap($cachename, $cachemap)
    {
        $yaml = Yaml::dump($cachemap);
        file_put_contents(__DIR__ . "/" . $cachename . 'CacheMap.yml', $yaml);
    }

    public function getRegisteredCommands()
    {
        return $this->app->all();
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

    /**
     * @param $argv
     * @return array
     */
    protected function mergeArgv($argv)
    {
        //Merge our args with our config file
        if (class_exists('TgCommands')) {
            $fileName = TgCommands::TGCONFIG . '.yml';
            if (file_exists($fileName)) {
                $configFile = Yaml::parse(file_get_contents($fileName));

                $merger = new Merger();
                $merger->setArgs($argv, $configFile);
                $argv = $merger->merge();
            }


        }
        return $argv;
    }

    /**
     * @param $argv
     * @return array
     */
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

    /**
     * @return bool
     */
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

    /**
     * @return bool
     */
    protected function checkForTgFile()
    {
        if (file_exists($this->dir . DIRECTORY_SEPARATOR . Tg::TGFILE)) {
            return true;
        }
        return false;
    }

    /**
     * @return bool
     */
    protected function loadTxCommands()
    {
        require_once $this->dir . DIRECTORY_SEPARATOR . Tg::TGFILE;
        if (!class_exists(Tg::TGCLASS)) {
            return false;
        }
        return true;
    }

    /**
     * @param $dir
     * @param $namespace
     * @return array
     */
    protected function findClasses($dir, $namespace)
    {
        $this->finder->setPath($dir);
        return $this->finder->find($namespace);
    }


    /**
     * @param $className
     * @param null $passThrough
     */
    protected function addCommandsFromClass($className, $passThrough = null)
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
            $this->app->add($command);
        }
    }

    /**
     * @param $className
     * @param TaskInfo $taskInfo
     * @return Command
     */
    protected function createCommand($className, TaskInfo $taskInfo)
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

    /**
     *
     */
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

    /**
     * @return bool
     */
    public function handleError()
    {
        if (error_reporting() === 0) {
            return true;
        }
        return false;
    }


}