<?php
/**
 * Created by PhpStorm.
 * User: tom
 * Date: 18/06/2016
 * Time: 22:58
 */

namespace twhiston\tg\RoboCommand;

use Robo\Tasks;
use Symfony\Component\Yaml\Yaml;

/**
 * Class Dev
 * @package twhiston\tg\RoboCommand
 *
 * Development related tasks
 */
class Dev extends Tasks
{

    public static function devLocation()
    {
        return Dev::devPath() . Dev::devFilename();
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
     * Set the library developer state with libdev true no classes will be cached and the cwd src folder will also be searched
     */
    public function libdev($on = 'true')
    {
        if ($on !== 'true' && $on !== 'false') {
            $this->yell('must be true/false');
            return 1;
        }
        $on = $on === 'true'?true:false;
        $output = ["libdev" => $on];
        $yaml = Yaml::dump($output);
        if (!file_exists(Dev::devPath())) {
            mkdir(Dev::devPath());
        }
        file_put_contents(Dev::devLocation(), $yaml);
        $output = "Lib Dev Mode: ";
        $output .= ($on)?'true':'false';
        $this->yell($output);
    }

    /**
     * Get the current lib dev state
     */
    public function libdevState()
    {

        if (file_exists(Dev::devLocation())) {
            $yaml = Yaml::parse(file_get_contents(Dev::devLocation()));
            if (array_key_exists('libdev', $yaml)) {
                $output = "Lib Dev Mode: ";
                $output .= ( $yaml['libdev'])?'true':'false';
                $this->yell($output);
                return;
            }
        }
        $this->yell('Lib Dev Mode: Off');
    }

    /**
     * Add a change to the changelog for the current version number
     */
    public function change()
    {
        $version = $this->ver();
        $this->doChange($version);
    }

    /**
     * @return mixed
     *
     * Get the current version number and yell it
     */
    public function ver()
    {
        $version = $this->getVersion();
        $this->yell('Current Version: ' . $version);
        return $version;
    }

    /**
     * @return mixed current version number
     */
    protected function getVersion()
    {
        $path = __DIR__ . '/.semver';
        return $this->taskSemVer($path)->__toString();
    }

    /**
     * @param $version
     *
     * Writes changes to the log and bumps the version number if necessary
     */
    protected function doChange($version)
    {

        $this->taskChangelog()
            ->version($version)
            ->askForChanges()
            ->run();
    }

    /**
     * bump the version number and add changes to the log
     * @throws \Robo\Exception\TaskException
     */
    public function bump()
    {
        $path = getcwd() . '/.semver';
        if (!file_exists($path)) {
            $this->makeSemverFile($path);
        }
        $this->ver();
        $level = $this->askDefault("Update Type: PATCH/minor/major", 'patch');
        $result = $this->taskSemVer($path)
            ->increment($level)
            ->run();
        $ver = $result->getMessage();
        $this->doChange($ver);
    }

    /**
     * @param $path
     * make the semver file
     */
    protected function makeSemverFile($path)
    {
        $this->taskWriteToFile($path)->lines([
            "---",
            ":major: 0",
            ":minor: 1",
            ":patch: 0",
            ":special: ''",
            ":metadata: ''"
        ])->run();
    }

}