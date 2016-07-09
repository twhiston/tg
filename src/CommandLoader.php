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
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;

/**
 * Class CommandLoader
 * @package twhiston\tg
 */
class CommandLoader
{


    protected $app;

    protected $classCache;

    public function __construct(Application &$app, ClassCache $classCache)
    {
        $this->app = $app;
        $this->classCache = $classCache;
    }


    /**
     * @param $className
     * @param $app
     * @param null $passThrough
     */
    public function addCommandsFromClass($className, $passThrough = null)
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

    public function loadCommandsFromClasses(array $locations, $bypassCache = false, $passThroughArgs = '')
    {
        $classes = $this->classCache->getClasses('RoboCommand\\', $locations, $bypassCache);
        $this->addRoboCommands($classes, $passThroughArgs);

        $classes = $this->classCache->getClasses('Command\\', $locations, $bypassCache);
        $this->addSymfonyCommands($classes);
    }


    protected function addRoboCommands($classes, $passThroughArgs)
    {
        //Load our core commands
        foreach ($classes as $class) {
            $this->addCommandsFromClass($class, $passThroughArgs);
        }
    }


    protected function addSymfonyCommands($classes)
    {
        foreach ($classes as $class) {
            $this->app->add(new $class);
        }
    }

}