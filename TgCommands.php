<?php
/**
 * This is project's local console commands configuration for Tg Robo task runner.
 * Write this file the same as any Robo file
 * @see http://robo.li/
 */

use Symfony\Component\Finder\Finder;
use twhiston\tg\Tasks\TgTasks;

class TgCommands extends TgTasks
{

    // define public methods as commands
    public function pharBuild()
    {
        $collection = $this->collection();

        $this->taskComposerInstall()
            ->noDev()
            ->preferDist()
            ->optimizeAutoloader()
            ->printed(true)
            ->run();

        $packer = $this->taskPackPhar('tg.phar');
        $files = Finder::create()->ignoreVCS(true)
            ->files()
            ->name('*.php')
            ->name('CommandCacheMap.yml')
            ->name('RoboCommandCacheMap.yml')
            ->notName('dev.yml')//no dev modes
            ->path('src')
            ->path('cache')
            ->path('vendor')
            ->exclude('vendor/psr/log/Psr/Log/Test')
            ->exclude('vendor/symfony/config/Symfony/Component/Config/Tests')
            ->exclude('vendor/symfony/console/Tests')
            ->exclude('vendor/symfony/event-dispatcher/Tests')
            ->exclude('vendor/symfony/filesystem/Tests')
            ->exclude('vendor/symfony/finder/Tests')
            ->exclude('vendor/symfony/process/Tests')
            ->exclude('vendor/henrikbjorn/lurker/tests')
            ->exclude('vendor/symfony/yaml/Tests')
            ->exclude('vendor/twhiston/twlib/tests')
            ->exclude('vendor/codegyre/robo/tests')
            ->exclude('vendor/twig/twig/test')
            ->in(__DIR__);
        foreach ($files as $file) {
            $packer->addFile($file->getRelativePathname(), $file->getRealPath());
        }
        $packer->addFile('/src/tg', __DIR__ . '/src/tg')
            ->executable(__DIR__ . '/src/tg')
            ->addToCollection($collection);

        $this->taskComposerInstall()
            ->printed(false)
            ->addToCollection($collection);

        $collection->run();
    }
}