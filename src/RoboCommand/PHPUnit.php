<?php
/**
 * Created by PhpStorm.
 * User: tom
 * Date: 19/06/2016
 * Time: 01:52
 */

namespace twhiston\tg\RoboCommand;

use Robo\Tasks;
use twhiston\tg\Traits\Watcher;

class PHPUnit extends Tasks
{

    use Watcher;

    public function watch($path, $unitArgs)
    {
        $func = function () use ($unitArgs) {
            $this->yell('running unit tests');
            $this->taskPhpUnit()->args($unitArgs)->run();
        };
        $this->startWatcher($func, $path, $unitArgs);
    }

    public function generateXml()
    {
        $vars = $this->getVars();

        $filename = 'phpunit.xml.'.$vars['environment'];
        $filepath = getcwd() . '/';
        $saveLocation = $filepath . $filename;

        $loader = new \Twig_Loader_Filesystem(__DIR__ . '/Templates');
        $twig = new \Twig_Environment($loader);
        $filestring = $twig->render('phpunit.xml.twig', $vars);

        file_put_contents(
            $saveLocation,
            $filestring
        );

    }

    protected function getVars()
    {
        $vars = [];
        $vars['name'] = $this->ask('testsuite name');
        $vars['environment'] = $this->askDefault('environment', 'dist');
        $vars['bootstrap'] = $this->askDefault('bootstrap', "vendor/autoload.php");

        $vars['dirs'] = [];
        do {
            $state = $this->processOutput($this->ask('directory'), $vars['dirs']);
        } while ($state);

        $vars['coverage'] = $this->askDefault('code coverage', 'yes');

        if ($vars['coverage'] === 'yes') {
            $vars['whitelist'] = [];
            do {
                $state = $this->processOutput($this->ask('whitelist'), $vars['whitelist']);
            } while ($state);

            $vars['blacklist'] = [];
            do {
                $state = $this->processOutput($this->ask('blacklist'), $vars['blacklist']);
            } while ($state);

            $vars['coverage_target'] = $this->askDefault('target', 'build/logs/phpunit');
        }

        return $vars;
    }

    private function processOutput($output, &$storage)
    {
        if ($output !== null) {
            $storage[] = $output;
            return true;
        }
        return false;

    }

}