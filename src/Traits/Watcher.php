<?php
/**
 * Created by PhpStorm.
 * User: tom
 * Date: 19/06/2016
 * Time: 02:05
 */

namespace twhiston\tg\Traits;


/**
 * Class Watcher
 * @package twhiston\tg\Traits
 * Add this trait to your task class if you want to create watchers
 */
trait Watcher
{

    /**
     * @param callable $task what to do when the watcher is triggered
     * @param $path
     * @throws \Exception
     */
    protected function startWatcher(callable $task, $path = __DIR__)
    {

        if (!file_exists($path)) {
            throw new \Exception("$path not found");
        }
        $this->taskWatch()->monitor($path, $task)->run();
    }

    /**
     * @return mixed
     */
    abstract public function taskWatch();

}