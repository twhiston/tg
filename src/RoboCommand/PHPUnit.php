<?php
/**
 * Created by PhpStorm.
 * User: tom
 * Date: 19/06/2016
 * Time: 01:52
 */

namespace twhiston\tg\RoboCommand;

use Robo\Contract\CommandInterface;
use Robo\Tasks;
use twhiston\tg\Traits\Watcher;

class PHPUnit extends Tasks
{

    use Watcher;

    public function watch($path,$unitArgs)
    {
        $func = function () use ($unitArgs) {
            $this->yell('running unit tests');
            $this->taskPhpUnit()->args($unitArgs)->run();
        };
        $this->startWatcher($func, $path, $unitArgs);
    }

}