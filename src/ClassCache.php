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
        $cachePath = $this->sanitizePath($cachePath);
        $this->makeCacheDirectory($cachePath);
        $this->cachePath = $cachePath;
    }

    private function sanitizePath($cachePath)
    {
        if (substr($cachePath, -1) !== '/') {
            $cachePath .= '/';
        }
        return $cachePath;
    }

    /**
     * @return string
     */
    public function getCachePath()
    {
        return $this->cachePath;
    }

    public function getClasses($classPattern, array $locations, $bypassCache = false)
    {
        $classes = [];
        $sanitized = preg_replace("/[^A-Za-z0-9 ]/", '', $classPattern);
        if (!$bypassCache) {
            $classes = $this->hasCacheMap($sanitized);
        }
        if (empty($classes)) {
            $classes = [];
            foreach ($locations as $location) {
                $classes = array_merge($classes, $this->findClasses($location, 'tg\\' . $classPattern));
            }
            if (!$bypassCache) {
                $this->saveCacheMap($sanitized, $classes);
            }
        }
        return $classes;
    }

    public function clearCache(array $caches)
    {
        foreach ($caches as $cache) {
            if ($this->hasCacheMap($cache)) {
                unlink($this->cachePath . $cache . 'CacheMap.yml');
            }
        }
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

    protected function hasCacheMap($cachename)
    {
        if (file_exists($this->cachePath . $cachename . 'CacheMap.yml')) {
            return $this->getCacheMap($cachename);
        }
        return null;
    }

    protected function saveCacheMap($cachename, $cachemap)
    {
        $yaml = Yaml::dump($cachemap);
        file_put_contents($this->cachePath . $cachename . 'CacheMap.yml', $yaml);
    }

    private function getCacheMap($cachename)
    {
        return Yaml::parse(file_get_contents($this->cachePath . $cachename . 'CacheMap.yml'));
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