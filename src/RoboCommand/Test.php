<?php
/**
 * Created by PhpStorm.
 * User: tom
 * Date: 18/06/2016
 * Time: 23:02
 */

namespace twhiston\tg\RoboCommand;

use Robo\Tasks;


class Test extends \Robo\Tasks
{

    public function watchChange()
    {
        $this->taskWatch()->monitor(__DIR__.'/../../composer.json', function() {
            $this->yell('suck it philly');
        })->run();

    }

    public function yourself($name,$opts = ['silent|s' => false])
    {
        if($opts['silent'] === TRUE){
            $this->yell("SHHHHHHHH!!!!!!!");
        }
        $this->yell("go fuck yourself {$name}");
    }

}