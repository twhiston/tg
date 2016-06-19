<?php
/**
 * Created by PhpStorm.
 * User: tom
 * Date: 19/06/2016
 * Time: 02:05
 */

namespace twhiston\tg\Traits;

use Robo\Contract\CommandInterface;

trait Watcher
{

    protected function startWatcher(callable $task, $path = null, $args = [])
    {
        //TODO remove this
        $path = (!$path)?__DIR__ . '/../../composer.json':$path;
        $this->taskWatch()->monitor($path, $task)->run();
    }

}