<?php

/**
 * Created by PhpStorm.
 * User: tom
 * Date: 09/07/2016
 * Time: 01:48
 */

namespace twhiston\tg;

use Robo\Result;
use Robo\TaskInfo;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Finder\Finder;

/**
 * Class CommandLoader
 * @package twhiston\tg
 */
class CommandLoader
{


    protected $vendorExtension;

    public function __construct()
    {
        $this->vendorExtension = '/vendor';
    }

    /**
     * @param string $vendorExtension
     */
    public function setVendorExtension($vendorExtension)
    {
        $this->vendorExtension = $vendorExtension;
    }


    /**
     * @param $classes
     * @param $passThroughArgs
     */
    public function createRoboCommands(array $classes, $passThroughArgs = '')
    {
        //Load our core commands
        $output = [];
        foreach ($classes as $class) {
            $commands = $this->createCommandsFromClass($class, $passThroughArgs);
            $output = array_merge($output, $this->createSymfonyCommands($commands));
        }
        return $output;
    }


    /**
     * @param $classes string|Command[]
     * @return array|string|\Symfony\Component\Console\Command\Command[]
     */
    public function createSymfonyCommands(array $classes)
    {
        foreach ($classes as $key => $class) {
            if (!is_object($class)) {
                if (class_exists($class)) {
                    $classes[$key] = new $class;
                } else {
                    throw new Exception("Command not found: $class");
                }
            }
        }
        return $classes;
    }

    /**
     * @param $namespace
     * @param null $passThrough
     * @return array
     */
    protected function createCommandsFromClass($namespace, $passThrough = null)
    {
        $roboTasks = $this->createClass($namespace);

        $commandNames = array_filter(get_class_methods($namespace), function ($m) {
            return !in_array($m, ['__construct']);
        });

        $commands = [];
        foreach ($commandNames as $commandName) {
            $classParts = explode('\\', $namespace);
            $final = array_pop($classParts);
            $cleanName = strtolower($final);
            $command = $this->createCommand($cleanName, new TaskInfo($namespace, $commandName));
            $command->setCode(function (InputInterface $input) use ($roboTasks, $commandName, $passThrough) {
                // @codeCoverageIgnoreStart
                // get passthru args
                $args = $input->getArguments();
                array_shift($args);
                if ($passThrough) {
                    $args[key(array_slice($args, -1, 1, true))] = $passThrough;
                }
                $args[] = $input->getOptions();

                $res = call_user_func_array([$roboTasks, $commandName], $args);
                if (is_int($res)) {
                    return $res;
                }
                if (is_bool($res)) {
                    return $res ? 0 : 1;
                }
                if ($res instanceof Result) {
                    return $res->getExitCode();
                }
                return 0;
                // @codeCoverageIgnoreEnd
            });
            $commands[] = $command;
        }
        return $commands;
    }

    /**
     * @param $namespace
     * @return mixed
     * @throws \Exception
     */
    protected function createClass($namespace)
    {

        if (!class_exists($namespace, true)) {
            //If the class doesnt exist then its not in our own vendor dir
            //So we need to find it ourselves in the local vendor dir
            $this->findAndInclude($namespace);
        }
        return new $namespace;
    }

    /**
     * @param $namespace
     * @throws \Exception
     */
    private function findAndInclude($namespace)
    {
        $finder = new Finder();
        $namespaceParts = explode('\\', $namespace);
        $class = array_pop($namespaceParts);
        $finder->name($class . '.php');
        $finder->in(getcwd() . $this->vendorExtension);//local vendor dir
        if (iterator_count($finder) === 0) {
            throw new \Exception('Cannot find detected class');
        }
        foreach ($finder as $file) {
            if ($file->isFile()) {
                include_once $file->getRealPath();
            }
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

}