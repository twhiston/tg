<?php
/**
 * Created by PhpStorm.
 * User: tom
 * Date: 25/06/2016
 * Time: 03:11
 */

namespace twhiston\tg\Tasks;

/**
 * Class TgTasks
 * @package twhiston\tg\Tasks
 * Extend this class to provide your project specific commands
 */
abstract class TgTasks extends \Robo\Tasks
{

    /**
     * Optional config file name, without extension, can include path
     */
    const TGCONFIG = 'tg';

}