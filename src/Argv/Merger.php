<?php
/**
 * Created by PhpStorm.
 * User: tom
 * Date: 25/06/2016
 * Time: 18:32
 */

namespace twhiston\tg\Argv;


class Merger implements MergerInterface
{

    protected $argv;
    protected $brgv;

    public function __construct()
    {

    }

    public function setArgs(array $argv, array $brgv)
    {
        $this->argv = $argv;
        $this->brgv = $brgv;
    }

    public function merge()
    {
        $output = $this->argv;
        if (array_key_exists(1, $this->argv)) {
            //Tokenize the input and try to find the command name
            $tokens = explode(':', $this->argv[1]);

            //TODO - fix this
            if (array_key_exists($tokens[0], $this->brgv)) {
                if (array_key_exists($tokens[1], $this->brgv[$tokens[0]])) {
                    $argOnly = array_slice($this->argv, 2);
                    $newArg = $argOnly + $this->brgv[$tokens[0]][$tokens[1]];
                    array_unshift($newArg, $this->argv[0], $this->argv[1]);
                    $output = $newArg;
                }
            }
        }

        return $output;
    }

}