<?php
/**
 * Created by PhpStorm.
 * User: tom
 * Date: 18/06/2016
 * Time: 23:02
 */

namespace twhiston\tg\RoboCommand;


class NoLoadRoboTest extends \Robo\Tasks
{

    public function watchChange()
    {
        $this->taskWatch()->monitor(__DIR__, function () {
            $this->yell('Im watching you');
        })->run();

    }

}