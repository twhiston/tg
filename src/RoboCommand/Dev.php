<?php
/**
 * Created by PhpStorm.
 * User: tom
 * Date: 18/06/2016
 * Time: 22:58
 */

namespace twhiston\tg\RoboCommand;

use Robo\Tasks;

/**
 * Class Dev
 * @package twhiston\tg\RoboCommand
 *
 * Development related tasks
 */
class Dev extends Tasks
{

    /**
     * Add a change to the changelog for the current version number
     */
    public function change()
    {
        $version = $this->ver();
        $this->doChange($version);
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
     * @return mixed
     *
     * Get the current version number and yell it
     */
    public function ver()
    {
        $version = $this->getVersion();
        $this->yell('Current Version: '.$version);
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

}