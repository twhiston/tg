<?php
/**
 * Created by PhpStorm.
 * User: tom
 * Date: 09/07/2016
 * Time: 01:31
 */

namespace twhiston\tg;


use Exception;
use Symfony\Component\Yaml\Yaml;
use twhiston\twLib\Discovery\FindByNamespace;

/**
 * Class ClassCache
 * @package twhiston\tg
 */
class ClassCache
{

    /**
     * @var string path to the cache folder, must be set via setCachePath() method to ensure directory exists
     */
    private $cachePath;

    /**
     * @var FindByNamespace
     */
    protected $finder;

    public function __construct()
    {
        $this->finder = new FindByNamespace();
    }


    /**
     * @param mixed $cachePath
     */
    public function setCachePath($cachePath)
    {
        $this->makeCacheDirectory($cachePath);
        $this->cachePath = $cachePath;
    }

    /**
     * @return string
     */
    public function getCachePath()
    {
        return $this->cachePath;
    }

    /**
     * @param $cachePath string
     * @throws Exception
     */
    protected function makeCacheDirectory($cachePath)
    {
        if (!file_exists($cachePath)) {
            if (mkdir($cachePath) === false) {
                throw new Exception('could not create cache path');
            }
        }
    }

    public function getClasses($type, array $locations, $bypassCache = false)
    {
        $classes = [];
        if (!$bypassCache) {
            $classes = $this->hasCacheMap($type);
        }
        if (empty($classes)) {
            $classes = [];
            foreach ($locations as $location) {
                $classes = array_merge($classes, $this->findClasses($location, 'tg\\' . $type));
            }
            if (!$bypassCache) {
                $this->saveCacheMap($type, $classes);
            }
        }
        return $classes;
    }

    protected function hasCacheMap($cachename)
    {
        if (file_exists($this->cachePath . $cachename . 'CacheMap.yml')) {
            return $this->getCacheMap($cachename);
        }
        return null;
    }

    private function getCacheMap($cachename)
    {
        return Yaml::parse(file_get_contents($this->cachePath . $cachename . 'CacheMap.yml'));
    }

    protected function saveCacheMap($cachename, $cachemap)
    {
        $yaml = Yaml::dump($cachemap);
        file_put_contents($this->cachePath . $cachename . 'CacheMap.yml', $yaml);
    }

    /**
     * @param $dir
     * @param $namespace
     * @return array
     */
    protected function findClasses($dir, $namespace)
    {
        $this->finder->setPath($dir);
        return $this->finder->find($namespace);
    }
}