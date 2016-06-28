<?php
/**
 * Created by PhpStorm.
 * User: tom
 * Date: 19/06/2016
 * Time: 02:05
 */

namespace twhiston\tg\Traits;



trait Watcher
{

    abstract public function taskWatch();

    protected function startWatcher(callable $task, $path = __DIR__)
    {

        if (!file_exists($path)) {
            throw new \Exception("$path not found");
        }
        $this->taskWatch()->monitor($path, $task)->run();
    }

}