<?php
/**
 * Created by PhpStorm.
 * User: tom
 * Date: 25/06/2016
 * Time: 18:35
 */

namespace twhiston\tg\Argv;

interface MergerInterface
{
    public function setArgs(array $argv, array $brgv);

    public function merge();
}