<?php
/**
 * Created by PhpStorm.
 * User: tom
 * Date: 18/06/2016
 * Time: 16:00
 */

namespace twhiston\tg;

use Robo\Config;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;
use TgCommands;
use twhiston\tg\Argv\Merger;

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
     * @var Application
     */
    protected $app;


    /**
     * @var ClassCache
     */
    private $classCache;


    /**
     * Tg constructor.
     * @param $autoloader
     */
    public function __construct($vendorPath)
    {
        $this->output = new ConsoleOutput();
        $this->dir = getcwd();
        $this->vendorPath = $vendorPath;
        //Start the app here as this gives the opportunity to add extra classes by calling the add methods before run
        $this->app = new Application('Tg', self::VERSION);
        $this->classCache = new ClassCache();
    }

    /**
     * @param OutputInterface $output
     */
    public function setOutput(OutputInterface $output)
    {
        $this->output = $output;
    }

    /**
     * @param mixed $cachePath
     */
    public function setCachePath($cachePath)
    {
        $this->classCache->setCachePath($cachePath);
    }

    /**
     * @param array $paths
     */
    public function addCommandsFromPaths(array $paths)
    {
        $commandLoader = new CommandLoader();
        $this->addCommands($commandLoader, $paths);
    }

    /**
     * @return \Symfony\Component\Console\Command\Command[]
     */
    public function getRegisteredCommands()
    {
        return $this->app->all();
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

        if ($this->classCache->getCachePath() === null) {
            $this->classCache->setCachePath(__DIR__ . "/../cache/");
        }
        $hasCommandFile = $this->autoloadCommandFile();

        $this->input = $this->prepareInput($input ? $input : $_SERVER['argv']);

        //Load all our commands
        $commandLoader = new CommandLoader();
        $this->loadLocalFile($commandLoader, $hasCommandFile);//Cwd project specific
        $this->loadCoreCommands($commandLoader);//Tg vendor and core
        $this->loadLocalVendors($commandLoader);//Cwd vendor

        //Set up the robo static config class :(
        Config::setInput($this->input);
        Config::setOutput($this->output);

        $this->app->setAutoExit(false);
        return $this->app->run($this->input, $this->output);
    }

    /**
     * @param CommandLoader $commandLoader
     * @param $hasCommandFile
     */
    protected function loadLocalFile(CommandLoader $commandLoader, $hasCommandFile)
    {
        if ($hasCommandFile) {
            //Load our TxCommands file
            $commands = $commandLoader->createRoboCommands([Tg::TGCLASS], $this->passThroughArgs);
            $this->app->addCommands($commands);
        }
    }

    /**
     * @param CommandLoader $commandLoader
     */
    protected function loadCoreCommands(CommandLoader $commandLoader)
    {
        $locations = [__DIR__, $this->vendorPath];
        $this->addCommands($commandLoader, $locations);
    }

    /**
     * @param CommandLoader $commandLoader
     */
    protected function loadLocalVendors(CommandLoader $commandLoader)
    {
        //Load the dynamic paths
        if (file_exists($this->dir . '/vendor')) {
            $locations = [$this->dir . '/vendor'];
            $this->addCommands($commandLoader, $locations, true);
        }
    }

    /**
     * @param CommandLoader $commandLoader
     * @param $locations
     */
    private function addCommands(CommandLoader $commandLoader, $locations, $bypassCache = false)
    {
        $classes = $this->classCache->getClasses('RoboCommand\\', $locations, $bypassCache);
        $commands = $commandLoader->createRoboCommands($classes, $this->passThroughArgs);
        $this->app->addCommands($commands);

        $classes = $this->classCache->getClasses('Command\\', $locations, $bypassCache);
        $commands = $commandLoader->createSymfonyCommands($classes);
        $this->app->addCommands($commands);

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
    protected function autoloadCommandFile()
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