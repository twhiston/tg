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
    protected $configFile;

    public function __construct()
    {

    }

    public function setArgs(array $argv, array $configFile)
    {
        $this->argv = $argv;
        $this->configFile = $configFile;
    }

    public function merge()
    {
        $output = $this->argv;
        if (array_key_exists(1, $this->argv)) {
            //Tokenize the input and try to find the command name
            $tokens = explode(':', $this->argv[1]);
            //check for the namespace
            if ($this->hasNamespace($tokens[0], $this->configFile)) {
                $extractedConfig = $this->processFromConfigFile($tokens);
                $argOnly = array_slice($this->argv, 2);
                //Prefer the args to the extracted content, which makes command line override of file possible
                $merged = $argOnly + $extractedConfig;
                //Remove nulls
                $merged = array_filter($merged);
                array_unshift($merged, $this->argv[0], $this->argv[1]);//merge back in the script and command
                $output = $merged;
            }
        }

        return $output;
    }

    protected function processFromConfigFile($tokens)
    {
        //merge the 3 arrays in the config into an actual config array
        $fileConfig = (count($tokens) > 1) ? $this->configFile[$tokens[0]][$tokens[1]] : $this->configFile[$tokens[0]];
        $extractedConfig = [];
        foreach ($fileConfig as $key => $set) {
            if (is_array($set)) {
                switch ($key) {
                    case "args":
                        foreach ($set as $item) {
                            $extractedConfig[] = $item;
                        }
                        break;
                    case "options":
                        foreach ($set as $okey => $oval) {
                            if ($oval !== null) {
                                $write = '';
                                if ($oval === true) {
                                    $write = '--' . $okey;
                                } else {
                                    $write = '--' . $okey . '=' . $oval;
                                }
                                $extractedConfig[] = $write;
                            } else {
                                $extractedConfig[] = null;
                            }
                        }
                        break;
                    case "pass":
                        $extractedConfig[] = '--';
                        $extractedConfig = array_merge($extractedConfig, $set);
                        break;
                }
            }
        }
        return $extractedConfig;
    }

    protected function hasNamespace($namespace, $args)
    {
        return array_key_exists($namespace, $args) ? true : false;
    }

}