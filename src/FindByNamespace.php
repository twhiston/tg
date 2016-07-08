<?php

/**
 * Created by PhpStorm.
 * User: tom
 * Date: 18/06/2016
 * Time: 16:23
 */

namespace twhiston\tg;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RegexIterator;
use twhiston\twLib\Str\Str;

class FindByNamespace
{

    protected $path;

    protected $data;

    /**
     * FindByNamespace constructor.
     * @param $path
     */
    public function __construct($path = null)
    {
        $this->path = ($path === null || $path === '') ? __DIR__ : $path;
        $this->data = [];
        $this->data[$path] = [];
    }

    public function setPath($path)
    {
        $this->path = $path;
    }

    public function find($needle = null, $rebuild = false)
    {
        $this->buildData($rebuild);
        $filtered = $this->filterData($needle);
        return $filtered;

    }

    protected function buildData($rebuild)
    {
//        if ($rebuild === true || empty($this->data[$this->path])) {
//            $allFiles = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($this->path));
//            $phpFiles = new RegexIterator($allFiles, '/\.php$/');
//            foreach ($phpFiles as $phpFile) {
//                //tokenize file
//                if (file_exists($phpFile->getRealPath())) {
//                    $content = file_get_contents($phpFile->getRealPath());
//                    $this->processTokens(token_get_all($content), $this->path);
//                } else {
//                    $this->data[$this->path] = [];
//                }
//            }
//        }

        if ($rebuild === true || empty($this->data[$this->path])) {
            $allFiles = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($this->path));
            $phpFiles = new RegexIterator($allFiles, '/\.php$/');
//            print 'PATH: ' . $this->path . PHP_EOL;
            $this->data[$this->path] = [];
            foreach ($phpFiles as $phpFile) {
                //tokenize file
                if (Str::startsWith($this->path, ['phar'])) {
                    $realpath = $phpFile->getPath() . '/' . $phpFile->getFilename();
                } else {
                    $realpath = $phpFile->getRealPath();
                }

//                print "REALPATH: " .$realpath . PHP_EOL;

                if (file_exists($realpath)) {
//                    print 'exists' . PHP_EOL;
                    $content = file_get_contents($realpath);
                    $this->processTokens(token_get_all($content), $this->path);
                }
            }
        }
    }

    protected function processTokens(array $tokens, $realpath)
    {
        $namespace = '';
        for ($index = 0; isset($tokens[$index]); $index++) {
            if (!isset($tokens[$index][0])) {
                continue;
            }
            if (T_NAMESPACE === $tokens[$index][0]) {
                $index += 2; // Skip namespace keyword and whitespace
                while (isset($tokens[$index]) && is_array($tokens[$index])) {
                    $namespace .= $tokens[$index++][1];
                }
            }
            if (T_CLASS === $tokens[$index][0]) {
                $index += 2; // Skip class keyword and whitespace
                if (is_array($tokens[$index]) && array_key_exists(1, $tokens[$index])) {
                    $this->data[$realpath][] = $namespace . '\\' . $tokens[$index][1];
                }
            }
        }
    }


    protected function filterData($needle)
    {
        return array_values(array_filter($this->data[$this->path], function ($var) use ($needle) {
            if (strpos($var, $needle) !== false) {
                return true;
            }
            return false;
        }));
    }

}