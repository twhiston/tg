<?php
/**
 * Created by PhpStorm.
 * User: tom
 * Date: 25/07/2016
 * Time: 17:55
 */

namespace twhiston\tg\Traits;

use Symfony\Component\Finder\Finder;
use Symfony\Component\Console\Output\OutputInterface;


trait CanClearCache
{

    protected function clearCache(OutputInterface $output = null)
    {
        $files = Finder::create()
                       ->files()
                       ->name('CommandCacheMap.yml')
                       ->name('RoboCommandCacheMap.yml')
                       ->in(getcwd() . '/.tg');
        foreach ($files as $file) {
            $path = $file->getPathname();
            unlink($path);
            if ($output) {
                $output->writeln("Deleted: {$path}");
            }
        }
    }
}