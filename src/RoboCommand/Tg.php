<?php
/**
 * Created by PhpStorm.
 * User: tom
 * Date: 18/06/2016
 * Time: 22:58
 */

namespace twhiston\tg\RoboCommand;

use Humbug\SelfUpdate\Updater;
use Robo\Tasks;
use Symfony\Component\Yaml\Yaml;
use twhiston\tg\Phar\BitbucketStrategy;
use twhiston\tg\Tg as TgApp;
use twhiston\tg\Traits\CanClearCache;

/**
 * Class Dev
 * @package twhiston\tg\RoboCommand
 *
 * Development related tasks
 */
class Tg extends Tasks
{

    use CanClearCache;

    const MODES = ['lib', 'core'];

    /**
     * Set the library developer state with libdev true no classes will be cached and the cwd src folder will also be searched
     */
    public function dev($mode, $state = 'true')
    {
        if ($this->testModeInput($mode) === false) {
            $this->yell('must be a valid Mode: ' . implode(' | ', Tg::MODES));
            return;
        }

        if ($this->testBoolInput($state) === false) {
            $this->yell('must be true/false');
            return;
        }
        $state = $this->fixBoolInput($state);

        if ($this->hasDevFile()) {
            $devfile = Yaml::parse(file_get_contents(Tg::devLocation()));
        } else {
            $devfile = [];
        }

        $devfile[$mode] = $state;
        $yaml = Yaml::dump($devfile);

        file_put_contents(Tg::devLocation(), $yaml);
        $output = "{$mode} Dev Mode: ";
        $output .= ($state) ? 'true' : 'false';
        $this->yell($output);

        $this->clearCache();

    }

    private function testModeInput($mode)
    {
        return array_search($mode, Tg::MODES);
    }

    protected function testBoolInput($input)
    {
        if ($input === 'true' || $input === 'false') {
            return true;
        }
        return false;
    }

    private function fixBoolInput($input)
    {
        return $input === 'true' ? true : false;
    }

    private function hasDevFile()
    {
        return (file_exists(Tg::devLocation()));
    }

    public static function devLocation()
    {
        return Tg::devPath() . Tg::devFilename();
    }

    public static function devPath()
    {
        return getcwd() . '/.tg/';
    }

    public static function devFilename()
    {
        return 'dev.yml';
    }

    /**
     * Get the current lib dev state
     */
    public function ds($mode)
    {

        if ($this->testModeInput($mode) === false) {
            $this->yell('must be a valid Mode: ' . implode(' | ', Tg::MODES));
            return;
        }

        if (file_exists(Tg::devLocation())) {
            $yaml = Yaml::parse(file_get_contents(Tg::devLocation()));
            if (array_key_exists($mode, $yaml)) {
                $output = "{$mode} Dev Mode: ";
                $output .= ($yaml[$mode]) ? 'true' : 'false';
                $this->yell($output);
                return;
            }
        }
        $this->yell("{$mode} Dev Mode: false");
    }

    public function selfUpdate()
    {
//        if (!extension_loaded('Phar') || !(\Phar::running(false))) {
//            $this->yell('Can only update Phar version. Update via a vcs');
//        }

        $updater = new Updater(null, false);
        $updater->setStrategyObject(new BitbucketStrategy($updater));//Yep, that sucks
        $updater->getStrategy()->setPackageName('twhiston/tg');
        $updater->getStrategy()->setPharName('tg.phar');
        $updater->getStrategy()->setCurrentLocalVersion(TgApp::VERSION);
        $remoteVersion = $updater->getStrategy()->getCurrentRemoteVersion($updater);
        try {
            $result = $updater->update();
            $result ? $this->yell('Updated', 100, 'green') : $this->yell('No update needed', 100, 'yellow');
        } catch (\Exception $e) {
            $this->yell('Error updating', 100, 'red');
        }
    }

    private function makeDevPath()
    {
        if (!file_exists(Tg::devPath())) {
            mkdir(Tg::devPath());
        }
    }

}